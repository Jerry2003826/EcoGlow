<?php
declare(strict_types=1);

namespace App\Service\Catalog;

use App\Model\Entity\Product;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Maps catalogue tables onto the array shape the storefront templates already use.
 *
 * HTML and class names stay in the templates. When the database has no published
 * products (tests without fixtures), the original hardcoded catalogue is used
 * so the page does not go blank.
 */
class CatalogService
{
    use LocatorAwareTrait;

    /**
     * Products for /shop, in the shape the listing template expects.
     *
     * @return list<array<string, mixed>>
     */
    public function shopProducts(): array
    {
        $fromDb = $this->loadShopFromDatabase();

        return $fromDb !== [] ? $fromDb : $this->fallbackShop();
    }

    /**
     * Variables for the product detail template.
     *
     * @param string|null $slug Product slug, or null for the Marlow default.
     * @return array<string, mixed>
     */
    public function productPage(?string $slug = null): array
    {
        $slug = $slug !== null && $slug !== '' ? $slug : 'marlow-floor-lamp';
        $fromDb = $this->loadProductFromDatabase($slug);
        if ($fromDb !== null) {
            return $fromDb;
        }

        return $this->fallbackProductPage();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadShopFromDatabase(): array
    {
        try {
            $rows = $this->fetchTable('Products')->find()
                ->contain([
                    'Categories',
                    'ProductVariants' => function ($query) {
                        return $query->where(['ProductVariants.is_active' => true])
                            ->orderBy(['ProductVariants.is_default' => 'DESC', 'ProductVariants.id' => 'ASC']);
                    },
                    'ProductImages' => function ($query) {
                        return $query->orderBy(['ProductImages.sort_order' => 'ASC', 'ProductImages.id' => 'ASC']);
                    },
                ])
                ->where(['Products.status' => 'active'])
                ->orderBy(['Products.id' => 'ASC'])
                ->all();
        } catch (Throwable $exception) {
            return [];
        }

        $products = [];
        foreach ($rows as $row) {
            $products[] = $this->mapListing($row);
        }

        return $products;
    }

    /**
     * @param string $slug Slug.
     * @return array<string, mixed>|null
     */
    private function loadProductFromDatabase(string $slug): ?array
    {
        try {
            /** @var \App\Model\Entity\Product|null $row */
            $row = $this->fetchTable('Products')->find()
                ->contain([
                    'Categories',
                    'ProductVariants' => function ($query) {
                        return $query->where(['ProductVariants.is_active' => true])
                            ->orderBy(['ProductVariants.is_default' => 'DESC', 'ProductVariants.id' => 'ASC']);
                    },
                    'ProductImages' => function ($query) {
                        return $query->orderBy(['ProductImages.sort_order' => 'ASC', 'ProductImages.id' => 'ASC']);
                    },
                ])
                ->where(['Products.status' => 'active', 'Products.slug' => $slug])
                ->first();
        } catch (Throwable $exception) {
            return null;
        }

        if ($row === null) {
            return null;
        }

        $listing = $this->mapListing($row);
        $meta = is_array($row->get('metadata')) ? $row->get('metadata') : [];
        $legacy = is_array($meta['legacy_frontend'] ?? null) ? $meta['legacy_frontend'] : [];
        $detailImage = $this->imageFilename($row, 'detail_hero') ?? $listing['image'];
        $detailAlt = $this->imageAlt($row, 'detail_hero') ?? $listing['alt'];

        $product = $listing;
        $product['image'] = $detailImage;
        $product['alt'] = $detailAlt;
        $variant = $this->defaultVariant($row);
        $product['variant_id'] = $variant ? (int)$variant->id : 0;
        $product['available'] = $variant ? $this->availableUnits((int)$variant->id) : 0;
        $product['in_stock'] = $this->variantIsPurchasable($variant, (int)$product['available']);

        $globes = $meta['globes'] ?? [];
        if (!is_array($globes) || $globes === []) {
            $globes = $this->fallbackGlobes();
        }

        $specs = $row->get('specifications');
        if (!is_array($specs) || $specs === []) {
            $specs = $this->fallbackSpecs();
        }

        $related = $this->mapRelated($meta['related'] ?? null);
        if ($related === []) {
            $related = $this->fallbackRelated();
        }

        return [
            'product' => $product,
            'globes' => $globes,
            'specs' => $specs,
            'related' => $related,
            'legacy' => $legacy,
        ];
    }

    /**
     * @param \App\Model\Entity\Product $row Product.
     * @return array<string, mixed>
     */
    private function mapListing(Product $row): array
    {
        $meta = is_array($row->get('metadata')) ? $row->get('metadata') : [];
        $legacy = is_array($meta['legacy_frontend'] ?? null) ? $meta['legacy_frontend'] : [];
        $tags = is_array($row->get('tags')) ? $row->get('tags') : [];
        $variant = $this->defaultVariant($row);
        $attributes = is_array($variant?->get('attributes')) ? $variant->get('attributes') : [];

        $flag = $meta['badge'] ?? $legacy['flag'] ?? null;
        if ($flag === null) {
            foreach (['New', 'Sale', 'Best seller'] as $known) {
                if (in_array($known, $tags, true)) {
                    $flag = $known;
                    break;
                }
            }
        }

        $style = $meta['style'] ?? $legacy['style'] ?? null;
        if (!is_string($style) || $style === '') {
            foreach ($tags as $tag) {
                if (is_string($tag) && $tag !== $flag) {
                    $style = $tag;
                    break;
                }
            }
        }
        if (!is_string($style) || $style === '') {
            $style = 'Warm Minimal';
        }

        $swatches = $attributes['swatches'] ?? $legacy['swatches'] ?? [['Warm white', '#FBF9F5']];
        if (!is_array($swatches) || $swatches === []) {
            $swatches = [['Warm white', '#FBF9F5']];
        }

        $priceCents = $variant ? (int)$variant->get('price_cents') : 0;
        $category = $row->category?->name ?? $legacy['category'] ?? 'Ambient Floor Lamps';

        return [
            'slug' => (string)$row->slug,
            'variant_id' => $variant ? (int)$variant->id : 0,
            'image' => $this->imageFilename($row, 'listing_primary')
                ?? (string)($legacy['image'] ?? 'marlow-floor-lamp.webp'),
            'alt' => $this->imageAlt($row, 'listing_primary')
                ?? (string)($legacy['alt'] ?? $row->name),
            'name' => (string)$row->name,
            'meta' => (string)($row->get('short_description') ?: ($legacy['meta'] ?? '')),
            'price' => $priceCents / 100,
            'flag' => is_string($flag) && $flag !== '' ? $flag : null,
            'category' => (string)$category,
            'style' => $style,
            'swatches' => $swatches,
        ];
    }

    /**
     * @param \App\Model\Entity\Product $row Product.
     * @return \App\Model\Entity\ProductVariant|null
     */
    private function defaultVariant(Product $row): mixed
    {
        $variants = $row->product_variants ?? [];
        foreach ($variants as $variant) {
            if ($variant->get('is_default')) {
                return $variant;
            }
        }

        return $variants[0] ?? null;
    }

    /**
     * @param \App\Model\Entity\Product $row Product.
     * @param string $role Image role.
     * @return string|null
     */
    private function imageFilename(Product $row, string $role): ?string
    {
        foreach ($row->product_images ?? [] as $image) {
            if ((string)$image->get('image_role') === $role) {
                return $this->basename((string)$image->get('image_url'));
            }
        }

        return null;
    }

    /**
     * @param \App\Model\Entity\Product $row Product.
     * @param string $role Image role.
     * @return string|null
     */
    private function imageAlt(Product $row, string $role): ?string
    {
        foreach ($row->product_images ?? [] as $image) {
            if ((string)$image->get('image_role') === $role) {
                $alt = trim((string)$image->get('alt_text'));

                return $alt !== '' ? $alt : null;
            }
        }

        return null;
    }

    /**
     * @param string $url Stored image url or filename.
     * @return string
     */
    private function basename(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }
        $base = basename(parse_url($url, PHP_URL_PATH) ?: $url);

        return $base !== '' ? $base : $url;
    }

    /**
     * @param mixed $related Seeded related list.
     * @return list<array<string, mixed>>
     */
    private function mapRelated(mixed $related): array
    {
        if (!is_array($related)) {
            return [];
        }
        $out = [];
        foreach ($related as $item) {
            if (!is_array($item) || empty($item['name'])) {
                continue;
            }
            $out[] = [
                'image' => (string)($item['image'] ?? ''),
                'alt' => (string)($item['alt'] ?? $item['name']),
                'name' => (string)$item['name'],
                'meta' => (string)($item['meta'] ?? ''),
                'price' => (float)($item['price'] ?? 0),
                'swatches' => is_array($item['swatches'] ?? null)
                    ? $item['swatches']
                    : [['Warm white', '#FBF9F5']],
                'slug' => $this->slugFromName((string)$item['name']),
            ];
        }

        return $out;
    }

    /**
     * @param string $name Product name.
     * @return string
     */
    private function slugFromName(string $name): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));

        return $slug !== '' ? $slug : 'item';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackShop(): array
    {
        return [
            $this->card(
                'marlow-floor-lamp.webp',
                'Marlow floor lamp lit against a plaster wall, oak column under a linen drum shade',
                'Marlow Floor Lamp',
                'Turned oak, linen shade, 1.45 m',
                249.00,
                'New',
                'Ambient Floor Lamps',
                'Warm Minimal',
                [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
            ),
            $this->card(
                'halden-pendant.webp',
                'Halden pendant hanging on a slim brass stem, its opal glass globe lit',
                'Halden Pendant',
                'Opal glass globe, 20 cm, E27',
                189.00,
                null,
                'LED Ceiling Lights',
                'Sculptural',
                [['Opal', '#E2DED2'], ['Forest', '#124C24']],
            ),
            $this->card(
                'aura-smart-bulbs.webp',
                'Four Aura globes laid in a row on brass screw bases, two of them lit',
                'Aura Smart Bulb Set',
                'Four E27 globes, 2200–6500K',
                79.00,
                'Best seller',
                'Smart Bulbs',
                'Smart & Connected',
                [['Warm white', '#FBF9F5']],
            ),
            $this->card(
                'fernway-solar-path.webp',
                'Three Fernway path lights spiked along a gravel path, lit at dusk',
                'Fernway Solar Path Light',
                'Set of six spikes, IP65, 3000K',
                129.00,
                null,
                'Outdoor Solar Lights',
                'Warm Minimal',
                [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
            ),
            $this->card(
                'brindle-wall-sconce.webp',
                'Brindle wall sconce, an opal glass dome lit flush against a plaster wall',
                'Brindle Wall Sconce',
                'Opal glass dome, 18 cm, E14',
                99.00,
                null,
                'Wall Sconces',
                'Heritage',
                [['Opal', '#E2DED2'], ['Charcoal', '#2F2E2C']],
            ),
            $this->card(
                'linen-drum-shade.webp',
                'Linen drum shade standing on its own, an undyed cylinder with a visible weave',
                'Linen Drum Shade',
                'Undyed linen, 45 cm, E27 ring',
                59.00,
                null,
                'Decorative Accessories',
                'Warm Minimal',
                [['Natural', '#E2DED2'], ['Clay', '#E2925E']],
            ),
            $this->card(
                'corva-ceiling-disc.webp',
                'Corva ceiling disc mounted flush, an opal diffuser inside a brushed brass rim',
                'Corva Ceiling Disc',
                'Flush mount, 40 cm, 3000K',
                219.00,
                null,
                'LED Ceiling Lights',
                'Warm Minimal',
                [['Warm white', '#FBF9F5'], ['Charcoal', '#2F2E2C']],
            ),
            $this->card(
                'odette-arc-lamp.webp',
                'Odette arc lamp curving out of a marble base over an oak floor',
                'Odette Arc Lamp',
                'Brass arc, marble base, 2.1 m',
                329.00,
                'New',
                'Ambient Floor Lamps',
                'Sculptural',
                [['Charcoal', '#2F2E2C'], ['Brass', '#C9BCA9']],
            ),
            $this->card(
                'nimbus-smart-downlight.webp',
                'Two Nimbus downlights recessed in a ceiling, throwing overlapping pools of warm light',
                'Nimbus Smart Downlight',
                'Tunable 2700–5000K, 90 mm cut-out',
                45.00,
                null,
                'Smart Bulbs',
                'Smart & Connected',
                [['Warm white', '#FBF9F5']],
            ),
            $this->card(
                'kelso-solar-bollard.webp',
                'Kelso solar bollard lighting a sand path beside dry grasses',
                'Kelso Solar Bollard',
                'Powder-coated, 40 cm, IP65',
                179.00,
                null,
                'Outdoor Solar Lights',
                'Sculptural',
                [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
            ),
            $this->card(
                'ashby-twin-sconce.webp',
                'Ashby twin sconce on a plaster wall, two brass arms under small linen shades',
                'Ashby Twin Sconce',
                'Twin brass arms, linen shades, E14',
                145.00,
                null,
                'Wall Sconces',
                'Heritage',
                [['Brass', '#C9BCA9'], ['Natural', '#E2DED2']],
            ),
            $this->card(
                'rowan-rotary-dimmer.webp',
                'Rowan rotary dimmer, a brushed brass plate with a knurled knob',
                'Rowan Rotary Dimmer',
                'Trailing-edge rotary, 250 W, brass',
                39.00,
                null,
                'Decorative Accessories',
                'Smart & Connected',
                [['Charcoal', '#2F2E2C'], ['Natural', '#E2DED2']],
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackProductPage(): array
    {
        $product = $this->card(
            'marlow-detail-wide.webp',
            'Marlow floor lamp lit against a plaster wall, a turned oak column under a linen drum shade',
            'Marlow Floor Lamp',
            'Turned oak, linen shade, 1.45 m',
            249.00,
            'New',
            'Ambient Floor Lamps',
            'Warm Minimal',
            [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
        );
        $product['variant_id'] = 0;
        $product['available'] = 0;
        $product['in_stock'] = false;

        return [
            'product' => $product,
            'globes' => $this->fallbackGlobes(),
            'specs' => $this->fallbackSpecs(),
            'related' => $this->fallbackRelated(),
        ];
    }

    /**
     * @return list<string>
     */
    private function fallbackGlobes(): array
    {
        return [
            'Warm white, 2700 K',
            'Smart tunable white',
            'Fitting only',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function fallbackSpecs(): array
    {
        return [
            'Height' => '1.45 m',
            'Shade diameter' => '38 cm',
            'Materials' => 'Solid oak, natural linen',
            'Fitting' => 'E27, max 60 W equivalent',
            'Globe included' => 'Yes, 9 W LED (2700 K)',
            'Energy rating' => 'A+',
            'Cable' => '2.0 m, in-line rotary dimmer',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackRelated(): array
    {
        return [
            $this->card(
                'odette-arc-lamp.webp',
                'Odette arc lamp curving out of a marble base over an oak floor',
                'Odette Arc Lamp',
                'Brass arc, marble base, 2.1 m',
                329.00,
                null,
                '',
                '',
                [['Charcoal', '#2F2E2C'], ['Brass', '#C9BCA9']],
            ),
            $this->card(
                'linen-drum-shade.webp',
                'Linen drum shade standing on its own, an undyed cylinder with a visible weave',
                'Linen Drum Shade',
                'Undyed linen, 45 cm, E27 ring',
                59.00,
                null,
                '',
                '',
                [['Natural', '#E2DED2'], ['Clay', '#E2925E']],
            ),
            $this->card(
                'aura-smart-bulbs.webp',
                'Four Aura globes laid in a row on brass screw bases, two of them lit',
                'Aura Smart Bulb Set',
                'Four E27 globes, 2200–6500K',
                79.00,
                null,
                '',
                '',
                [['Warm white', '#FBF9F5']],
            ),
            $this->card(
                'rowan-rotary-dimmer.webp',
                'Rowan rotary dimmer, a brushed brass plate with a knurled knob',
                'Rowan Rotary Dimmer',
                'Trailing-edge rotary, 250 W, brass',
                39.00,
                null,
                '',
                '',
                [['Charcoal', '#2F2E2C'], ['Natural', '#E2DED2']],
            ),
        ];
    }

    /**
     * @param string $image Filename.
     * @param string $alt Alt text.
     * @param string $name Name.
     * @param string $meta Meta line.
     * @param float $price Price in dollars.
     * @param string|null $flag Badge.
     * @param string $category Category.
     * @param string $style Style.
     * @param list<array{0: string, 1: string}> $swatches Swatches.
     * @return array<string, mixed>
     */
    private function card(
        string $image,
        string $alt,
        string $name,
        string $meta,
        float $price,
        ?string $flag,
        string $category,
        string $style,
        array $swatches,
    ): array {
        return [
            'slug' => $this->slugFromName($name),
            'variant_id' => 0,
            'image' => $image,
            'alt' => $alt,
            'name' => $name,
            'meta' => $meta,
            'price' => $price,
            'flag' => $flag,
            'category' => $category,
            'style' => $style,
            'swatches' => $swatches,
            'available' => 0,
            'in_stock' => false,
        ];
    }

    /**
     * Sellable units across locations. Matches the cart's stock check.
     *
     * @param int $variantId Variant id.
     * @return int
     */
    private function availableUnits(int $variantId): int
    {
        try {
            $row = $this->fetchTable('InventoryBalances')->getConnection()->execute(
                'SELECT COALESCE(SUM(quantity_available), 0) AS available
                   FROM inventory_balances
                  WHERE product_variant_id = ?',
                [$variantId],
                ['integer'],
            )->fetch('assoc');
        } catch (Throwable) {
            return 0;
        }

        return is_array($row) ? (int)$row['available'] : 0;
    }

    /**
     * @param \App\Model\Entity\ProductVariant|null $variant Variant.
     * @param int $available Units on hand.
     * @return bool
     */
    private function variantIsPurchasable(mixed $variant, int $available): bool
    {
        if ($variant === null) {
            return false;
        }
        if (!$variant->get('track_inventory') || $variant->get('allow_backorder')) {
            return true;
        }

        return $available > 0;
    }
}
