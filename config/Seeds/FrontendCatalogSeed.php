<?php
declare(strict_types=1);

use Cake\Database\Connection;
use Migrations\BaseSeed;

/**
 * Imports the project's existing front-end JSON into the MySQL catalogue and
 * content tables. Source copy, prices, alt text, swatches and filenames are
 * preserved exactly; only internal slugs and SKUs are derived.
 */
final class FrontendCatalogSeed extends BaseSeed
{
    private \PDO $pdo;

    public function run(): void
    {
        $path = $this->findSourceFile();
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Unable to read front-end seed JSON: ' . $path);
        }
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('frontend-seed-data.json must decode to a JSON object.');
        }

        $products = $this->listValue($data, ['products'], 'products', true);
        $collections = $this->listValue($data, ['collections', 'categories'], 'collections', true);
        $bestSellers = $this->listValue($data, ['bestSellers', 'best_sellers'], 'bestSellers', true);
        $materials = $this->listValue($data, ['materials'], 'materials', true);
        $steps = $this->listValue($data, ['steps', 'serviceSteps', 'service_steps'], 'steps', true);

        $this->assertCount($products, 12, 'products');
        $this->assertCount($collections, 6, 'collections');
        $this->assertCount($bestSellers, 4, 'bestSellers');
        $this->assertCount($materials, 5, 'materials');
        $this->assertCount($steps, 4, 'steps');

        $this->pdo = $this->mysqlPdo();

        $detail = $this->firstObject($data, ['productDetails', 'product_details', 'product']);
        $globes = $this->listValue($data, ['globes'], 'globes', false);
        $specs = $this->mapValue($data, ['specs']);
        $related = $this->listValue($data, ['related'], 'related', false);
        $bestSellerNames = $this->names($bestSellers);

        // CakePHP 5 already wraps seeds in a transaction; nested PDO begin/commit
        // would either fail or commit the wrapper too early (before cake_seeds).
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $categoryIds = [];
            foreach ($collections as $index => $value) {
                $row = $this->object($value, "collections[$index]");
                $categoryIds[$this->required($row, 'name', "collections[$index].name")] =
                    $this->upsertCategory($row, $index);
            }

            $productIds = [];
            foreach ($products as $index => $value) {
                $row = $this->object($value, "products[$index]");
                $name = $this->required($row, 'name', "products[$index].name");
                $categoryName = $this->required($row, 'category', "products[$index].category");
                if (!isset($categoryIds[$categoryName])) {
                    $categoryIds[$categoryName] = $this->upsertCategory(
                        ['name' => $categoryName, 'text' => null, 'image' => null],
                        count($categoryIds)
                    );
                }
                $detailForProduct = $detail !== null
                    && trim((string)($detail['name'] ?? '')) === $name ? $detail : null;
                $productIds[$name] = $this->upsertProduct(
                    $row,
                    $categoryIds[$categoryName],
                    in_array($name, $bestSellerNames, true),
                    $detailForProduct,
                    $globes,
                    $specs,
                    $related,
                    $index
                );
            }

            if ($detail !== null) {
                $this->attachDetailImage($detail, $productIds);
            }
            foreach ($materials as $index => $value) {
                $this->upsertMaterial($this->object($value, "materials[$index]"), $index);
            }
            $this->seedHomeContent($collections, $materials, $steps, $data);
            $this->seedSettings($data['settings'] ?? []);
            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        fwrite(STDOUT, sprintf(
            "Imported %d products, %d collections, %d best sellers, %d materials and %d service steps from %s.\n",
            count($products), count($collections), count($bestSellers), count($materials), count($steps), $path
        ));
    }

    /**
     * CakePHP 5 migrations expose Cake\Database\Connection, not PDO.
     *
     * @return \PDO
     */
    private function mysqlPdo(): \PDO
    {
        $connection = $this->getAdapter()->getConnection();
        if (!$connection instanceof Connection) {
            throw new RuntimeException('FrontendCatalogSeed requires a CakePHP MySQL connection.');
        }
        $driver = $connection->getDriver();
        $method = new \ReflectionMethod($driver, 'getPdo');
        $pdo = $method->invoke($driver);
        if (!$pdo instanceof \PDO) {
            throw new RuntimeException('FrontendCatalogSeed requires CakePHP/Phinx MySQL PDO.');
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    private function findSourceFile(): string
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            __DIR__ . '/data/frontend-seed-data.json',
            __DIR__ . '/frontend-seed-data.json',
            $root . '/frontend-seed-data.json',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        throw new RuntimeException(
            'frontend-seed-data.json was not found. Use the real project file; do not invent prices, copy, alt text, swatches or WebP filenames.'
        );
    }

    private function listValue(array $data, array $keys, string $label, bool $required): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                if (!is_array($data[$key]) || !array_is_list($data[$key])) {
                    throw new RuntimeException($label . ' must be a JSON array.');
                }
                return $data[$key];
            }
        }
        if ($required) {
            throw new RuntimeException('Missing required JSON array: ' . $label);
        }
        return [];
    }

    private function mapValue(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key]) && !array_is_list($data[$key])) {
                return $data[$key];
            }
        }
        return [];
    }

    private function firstObject(array $data, array $keys): ?array
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                continue;
            }
            if (!array_is_list($data[$key])) {
                return $data[$key];
            }
            foreach ($data[$key] as $entry) {
                if (is_array($entry)) {
                    return $entry;
                }
            }
        }
        return null;
    }

    private function assertCount(array $rows, int $expected, string $label): void
    {
        if (count($rows) !== $expected) {
            throw new RuntimeException(sprintf('%s must contain exactly %d rows; found %d.', $label, $expected, count($rows)));
        }
    }

    private function object(mixed $value, string $label): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException($label . ' must be a JSON object.');
        }
        return $value;
    }

    private function required(array $row, string $key, string $label): string
    {
        $value = isset($row[$key]) ? trim((string)$row[$key]) : '';
        if ($value === '') {
            throw new RuntimeException($label . ' is required.');
        }
        return $value;
    }

    private function nullable(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row) || $row[$key] === null) {
            return null;
        }
        $value = trim((string)$row[$key]);
        return $value === '' ? null : $value;
    }

    private function slug(string $value): string
    {
        $lower = function_exists('mb_strtolower') ? mb_strtolower(trim($value), 'UTF-8') : strtolower(trim($value));
        $slug = trim((string)preg_replace('/[^a-z0-9]+/u', '-', $lower), '-');
        return $slug !== '' ? $slug : 'item-' . substr(hash('sha256', $value), 0, 12);
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function cents(array $row, string $label): int
    {
        if (isset($row['price_cents'])) {
            $rawCents = $row['price_cents'];
            if (!is_int($rawCents) && !(is_string($rawCents) && preg_match('/^\d+$/', $rawCents))) {
                throw new RuntimeException($label . '.price_cents must be a non-negative integer.');
            }
            $value = (int)$rawCents;
        } else {
            if (!array_key_exists('price', $row)) {
                throw new RuntimeException($label . '.price is required.');
            }
            $clean = preg_replace('/[^0-9.\-]/', '', (string)$row['price']);
            if ($clean === null || !preg_match('/^-?\d+(?:\.\d{1,2})?$/', $clean)) {
                throw new RuntimeException($label . '.price must be a normal AUD amount with at most two decimal places.');
            }
            $negative = str_starts_with($clean, '-');
            $unsigned = ltrim($clean, '-');
            [$dollars, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
            $value = ((int)$dollars * 100) + (int)str_pad($fraction, 2, '0');
            if ($negative) {
                $value *= -1;
            }
        }
        if ($value < 0) {
            throw new RuntimeException($label . '.price may not be negative.');
        }
        return $value;
    }

    private function names(array $rows): array
    {
        $result = [];
        foreach ($rows as $index => $row) {
            if (is_string($row) && trim($row) !== '') {
                $result[] = trim($row);
            } elseif (is_array($row) && trim((string)($row['name'] ?? '')) !== '') {
                $result[] = trim((string)$row['name']);
            } else {
                throw new RuntimeException("bestSellers[$index] must be a name or an object containing name.");
            }
        }
        return array_values(array_unique($result));
    }

    private function upsertCategory(array $row, int $order): int
    {
        $name = $this->required($row, 'name', "collections[$order].name");
        $sql = <<<'SQL'
INSERT INTO categories (parent_id, name, slug, description, image_url, is_active, sort_order, created, modified)
VALUES (NULL, ?, ?, ?, ?, 1, ?, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), name = VALUES(name), description = VALUES(description),
image_url = VALUES(image_url), is_active = 1, sort_order = VALUES(sort_order), modified = UTC_TIMESTAMP(6)
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name, $this->slug($name), $this->nullable($row, 'text'), $this->nullable($row, 'image'), $order]);
        return (int)$this->pdo->lastInsertId();
    }

    private function upsertProduct(
        array $row,
        int $categoryId,
        bool $featured,
        ?array $detail,
        array $globes,
        array $topSpecs,
        array $related,
        int $order
    ): int {
        $label = "products[$order]";
        $name = $this->required($row, 'name', $label . '.name');
        $image = $this->required($row, 'image', $label . '.image');
        $alt = $this->required($row, 'alt', $label . '.alt');
        $category = $this->required($row, 'category', $label . '.category');
        $style = $this->required($row, 'style', $label . '.style');
        $meta = $this->required($row, 'meta', $label . '.meta');
        $swatches = $row['swatches'] ?? [];
        if (!is_array($swatches)) {
            throw new RuntimeException($label . '.swatches must be an array.');
        }
        $flag = $this->nullable($row, 'flag');
        $installationAvailable = filter_var(
            $row['installation_available'] ?? $row['installationAvailable'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        $smartCompatible = filter_var(
            $row['smart_compatible'] ?? $row['smartCompatible'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if ($installationAvailable === null || $smartCompatible === null) {
            throw new RuntimeException($label . ' contains an invalid boolean capability field.');
        }
        $specs = $detail !== null && isset($detail['specs']) && is_array($detail['specs']) ? $detail['specs'] : $topSpecs;
        $tags = array_values(array_filter([$style, $flag], static fn($v): bool => $v !== null && $v !== ''));
        $metadata = [
            'legacy_frontend' => $row,
            'badge' => $flag,
            'style' => $style,
            'globes' => $detail !== null ? $globes : [],
            'related' => $detail !== null ? $related : [],
        ];
        $slug = $this->slug($name);

        $sql = <<<'SQL'
INSERT INTO products (category_id, brand_id, slug, name, product_type, short_description, description,
status, installation_available, smart_compatible, specifications, tags, is_featured, published_at, metadata, created, modified)
VALUES (?, NULL, ?, ?, ?, ?, NULL, 'active', ?, ?, ?, ?, ?, UTC_TIMESTAMP(6), ?, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), category_id = VALUES(category_id), name = VALUES(name),
product_type = VALUES(product_type), short_description = VALUES(short_description), status = 'active',
installation_available = VALUES(installation_available), smart_compatible = VALUES(smart_compatible),
specifications = VALUES(specifications), tags = VALUES(tags), is_featured = VALUES(is_featured),
metadata = VALUES(metadata), modified = UTC_TIMESTAMP(6)
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $categoryId, $slug, $name, $this->slug($category), $meta,
            $installationAvailable ? 1 : 0, $smartCompatible ? 1 : 0,
            $this->json($specs), $this->json($tags), $featured ? 1 : 0, $this->json($metadata),
        ]);
        $productId = (int)$this->pdo->lastInsertId();

        $variantSql = <<<'SQL'
INSERT INTO product_variants (product_id, sku, name, attributes, price_cents, tax_rate, is_default,
is_active, track_inventory, allow_backorder, metadata, created, modified)
VALUES (?, ?, ?, ?, ?, 0.10000, 1, 1, 1, 0, ?, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), product_id = VALUES(product_id), name = VALUES(name),
attributes = VALUES(attributes), price_cents = VALUES(price_cents), is_default = 1, is_active = 1,
metadata = VALUES(metadata), modified = UTC_TIMESTAMP(6)
SQL;
        $variant = $this->pdo->prepare($variantSql);
        $variant->execute([
            $productId,
            'LEGACY-' . strtoupper(substr($slug, 0, 80)),
            (string)($row['variant'] ?? 'Default'),
            $this->json(['style' => $style, 'swatches' => $swatches, 'globes' => $detail !== null ? $globes : []]),
            $this->cents($row, $label),
            $this->json(['source' => 'frontend-seed-data.json']),
        ]);

        $assign = $this->pdo->prepare(
            'INSERT INTO product_category_assignments (product_id, category_id, is_primary, sort_order, created) ' .
            'VALUES (?, ?, 1, ?, UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE is_primary = 1, sort_order = VALUES(sort_order)'
        );
        $assign->execute([$productId, $categoryId, $order]);
        $this->upsertImage($productId, $image, $alt, 0, true, 'listing_primary', '1:1');

        if ($detail !== null) {
            $detailImage = $this->nullable($detail, 'image');
            if ($detailImage !== null && $detailImage !== $image) {
                $this->upsertImage($productId, $detailImage, $this->nullable($detail, 'alt') ?? $alt, 10, false, 'detail_hero', '3:2');
            }
        }
        return $productId;
    }

    private function upsertImage(int $productId, string $image, string $alt, int $order, bool $primary, string $role, ?string $aspectRatio): void
    {
        if ($primary) {
            $this->pdo->prepare("UPDATE product_images SET is_primary = 0, image_role = CASE WHEN image_role = 'listing_primary' THEN 'gallery' ELSE image_role END WHERE product_id = ?")->execute([$productId]);
        } elseif ($role === 'detail_hero') {
            $this->pdo->prepare(
                "UPDATE product_images SET image_role = 'gallery' WHERE product_id = ? AND image_role = 'detail_hero' AND image_url <> ?"
            )->execute([$productId, $image]);
        }
        $find = $this->pdo->prepare('SELECT id FROM product_images WHERE product_id = ? AND image_url = ? LIMIT 1');
        $find->execute([$productId, $image]);
        $id = $find->fetchColumn();
        if ($id !== false) {
            $this->pdo->prepare(
                'UPDATE product_images SET alt_text = ?, sort_order = ?, is_primary = ?, image_role = ?, aspect_ratio = ?, metadata = ? WHERE id = ?'
            )->execute([
                $alt, $order, $primary ? 1 : 0, $role, $aspectRatio,
                $this->json(['source' => 'frontend-seed-data.json']), (int)$id,
            ]);
        } else {
            $this->pdo->prepare(
                'INSERT INTO product_images (product_id, product_variant_id, image_url, alt_text, sort_order, is_primary, image_role, aspect_ratio, metadata, created) ' .
                'VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(6))'
            )->execute([$productId, $image, $alt, $order, $primary ? 1 : 0, $role, $aspectRatio, $this->json(['source' => 'frontend-seed-data.json'])]);
        }
    }

    private function attachDetailImage(array $detail, array $productIds): void
    {
        $name = trim((string)($detail['name'] ?? ''));
        $image = trim((string)($detail['image'] ?? ''));
        if ($name === '' || $image === '' || !isset($productIds[$name])) {
            return;
        }
        $this->upsertImage($productIds[$name], $image, trim((string)($detail['alt'] ?? $name)), 10, false, 'detail_hero', '3:2');
    }

    private function upsertMaterial(array $row, int $order): void
    {
        $name = $this->required($row, 'name', "materials[$order].name");
        $sql = <<<'SQL'
INSERT INTO materials (name, slug, description, image_url, image_alt_text, is_active, sort_order, created, modified)
VALUES (?, ?, ?, ?, ?, 1, ?, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), image_url = VALUES(image_url),
image_alt_text = VALUES(image_alt_text), is_active = 1, sort_order = VALUES(sort_order), modified = UTC_TIMESTAMP(6)
SQL;
        $this->pdo->prepare($sql)->execute([
            $name,
            $this->slug($name),
            $this->required($row, 'text', "materials[$order].text"),
            $this->required($row, 'image', "materials[$order].image"),
            $this->nullable($row, 'alt') ?? $name,
            $order,
        ]);
    }

    private function seedHomeContent(array $collections, array $materials, array $steps, array $data): void
    {
        $this->pdo->exec(
            "INSERT INTO content_pages (slug, title, body, status, published_at, created, modified) " .
            "VALUES ('home', 'Home', JSON_OBJECT(), 'published', UTC_TIMESTAMP(6), UTC_TIMESTAMP(6), UTC_TIMESTAMP(6)) " .
            "ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), status = 'published', modified = UTC_TIMESTAMP(6)"
        );
        $pageId = (int)$this->pdo->lastInsertId();
        $this->replaceSection($pageId, 'collections', 'cards', 'Collections', $collections, 10);
        $this->replaceSection($pageId, 'materials', 'cards', 'Materials', $materials, 20);
        $this->replaceSection($pageId, 'service_steps', 'steps', 'How it works', $steps, 30);
        if (isset($data['hero']) && is_array($data['hero'])) {
            $this->replaceSection($pageId, 'hero', 'hero', null, [$data['hero']], 0);
        }
    }

    private function replaceSection(int $pageId, string $key, string $type, ?string $heading, array $rows, int $order): void
    {
        $sql = <<<'SQL'
INSERT INTO content_sections (content_page_id, section_key, section_type, heading, settings, sort_order, status, created, modified)
VALUES (?, ?, ?, ?, JSON_OBJECT(), ?, 'published', UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), section_type = VALUES(section_type), heading = VALUES(heading),
sort_order = VALUES(sort_order), status = 'published', modified = UTC_TIMESTAMP(6)
SQL;
        $this->pdo->prepare($sql)->execute([$pageId, $key, $type, $heading, $order]);
        $sectionId = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare('DELETE FROM content_items WHERE content_section_id = ?')->execute([$sectionId]);
        $insert = $this->pdo->prepare(
            'INSERT INTO content_items (content_section_id, item_key, title, body, image_url, image_alt_text, metadata, sort_order, is_active, created, modified) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))'
        );
        foreach ($rows as $index => $value) {
            $row = $this->object($value, $key . '[' . $index . ']');
            $title = $this->nullable($row, 'name') ?? $this->nullable($row, 'title');
            $insert->execute([
                $sectionId,
                $this->slug(($title ?? 'item') . '-' . $index),
                $title,
                $this->nullable($row, 'text') ?? $this->nullable($row, 'body'),
                $this->nullable($row, 'image'),
                $this->nullable($row, 'alt') ?? $title,
                $this->json(['legacy_frontend' => $row]),
                $index,
            ]);
        }
    }

    private function seedSettings(mixed $settings): void
    {
        if (!is_array($settings)) {
            return;
        }
        $values = [];
        if (isset($settings['freeShippingThreshold']) && is_numeric($settings['freeShippingThreshold'])) {
            $values['shipping.free_threshold_cents'] = (int)round((float)$settings['freeShippingThreshold'] * 100);
        }
        if (isset($settings['standardShipping']) && is_numeric($settings['standardShipping'])) {
            $values['shipping.standard_flat_rate_cents'] = (int)round((float)$settings['standardShipping'] * 100);
        }
        if (isset($settings['gstRate']) && is_numeric($settings['gstRate'])) {
            $values['tax.gst_rate'] = (float)$settings['gstRate'];
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_settings (setting_key, setting_value, description, modified) VALUES (?, ?, ?, UTC_TIMESTAMP(6)) ' .
            'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), modified = UTC_TIMESTAMP(6)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute([$key, $this->json($value), 'Imported from frontend-seed-data.json']);
        }
    }
}
