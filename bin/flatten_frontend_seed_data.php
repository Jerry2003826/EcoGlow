#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Flatten docs/frontend-seed-data.json for FrontendCatalogSeed.
 *
 * The source file is grouped by template (shop.php, home.php, product.php,
 * cart.php). FrontendCatalogSeed expects a single top-level object with
 * products, collections, bestSellers, materials, steps, product/globes/specs/
 * related, and optional settings/hero.
 *
 * Catalogue copy, prices, alt text, swatches and filenames are copied
 * unchanged. The only value mapping is cart.php._pricingRules → settings:
 * freeShippingThreshold, standardShipping (AUD, because the seed stores cents
 * as round(amount * 100)), and gstRate 0.1 (Australian GST, not 1/11).
 *
 * Usage: php bin/flatten_frontend_seed_data.php
 */

$root = dirname(__DIR__);
$sourcePath = $root . '/docs/frontend-seed-data.json';
$outputPath = $root . '/config/Seeds/data/frontend-seed-data.json';

$raw = file_get_contents($sourcePath);
if ($raw === false) {
    fwrite(STDERR, "Unable to read {$sourcePath}\n");
    exit(1);
}

/** @var array<string, mixed> $grouped */
$grouped = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($grouped)) {
    fwrite(STDERR, "Source JSON must decode to an object.\n");
    exit(1);
}

$shop = $grouped['shop.php'] ?? null;
$home = $grouped['home.php'] ?? null;
$productPage = $grouped['product.php'] ?? null;
$cart = $grouped['cart.php'] ?? null;
if (!is_array($shop) || array_is_list($shop)) {
    throw new RuntimeException('shop.php must be a JSON object.');
}
if (!is_array($home) || array_is_list($home)) {
    throw new RuntimeException('home.php must be a JSON object.');
}
if (!is_array($productPage) || array_is_list($productPage)) {
    throw new RuntimeException('product.php must be a JSON object.');
}
if (!is_array($cart) || array_is_list($cart)) {
    throw new RuntimeException('cart.php must be a JSON object.');
}

$pricing = $cart['_pricingRules'] ?? null;
if (!is_array($pricing) || array_is_list($pricing)) {
    throw new RuntimeException('_pricingRules must be a JSON object.');
}

$requiredLists = [
    'products' => [$shop['products'] ?? null, 12],
    'collections' => [$home['collections'] ?? null, 6],
    'bestSellers' => [$home['bestSellers'] ?? null, 4],
    'materials' => [$home['materials'] ?? null, 5],
    'steps' => [$home['steps'] ?? null, 4],
    'globes' => [$productPage['globes'] ?? null, 3],
    'related' => [$productPage['related'] ?? null, 4],
];
$lists = [];
foreach ($requiredLists as $key => [$value, $expected]) {
    if (!is_array($value) || !array_is_list($value)) {
        throw new RuntimeException($key . ' must be a JSON array.');
    }
    if (count($value) !== $expected) {
        throw new RuntimeException(sprintf(
            '%s must contain exactly %d rows; found %d.',
            $key,
            $expected,
            count($value),
        ));
    }
    $lists[$key] = $value;
}

$product = $productPage['product'] ?? null;
$specs = $productPage['specs'] ?? null;
if (!is_array($product) || array_is_list($product)) {
    throw new RuntimeException('product must be a JSON object.');
}
if (!is_array($specs) || array_is_list($specs)) {
    throw new RuntimeException('specs must be a JSON object.');
}

if (!isset($pricing['freeDeliveryFrom']) || !is_numeric($pricing['freeDeliveryFrom'])) {
    throw new RuntimeException('freeDeliveryFrom must be numeric.');
}
if (!isset($pricing['deliveryFlat']) || !is_numeric($pricing['deliveryFlat'])) {
    throw new RuntimeException('deliveryFlat must be numeric.');
}

$flat = [
    'products' => $lists['products'],
    'collections' => $lists['collections'],
    'bestSellers' => $lists['bestSellers'],
    'materials' => $lists['materials'],
    'steps' => $lists['steps'],
    'product' => $product,
    'globes' => $lists['globes'],
    'specs' => $specs,
    'related' => $lists['related'],
    'settings' => [
        'freeShippingThreshold' => $pricing['freeDeliveryFrom'] + 0,
        'standardShipping' => $pricing['deliveryFlat'] + 0,
        'gstRate' => 0.1,
    ],
];

$directory = dirname($outputPath);
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    fwrite(STDERR, "Unable to create {$directory}\n");
    exit(1);
}

$encoded = json_encode(
    $flat,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
);
if (file_put_contents($outputPath, $encoded . "\n") === false) {
    fwrite(STDERR, "Unable to write {$outputPath}\n");
    exit(1);
}

fwrite(STDOUT, "Wrote flattened seed JSON to {$outputPath}\n");
exit(0);
