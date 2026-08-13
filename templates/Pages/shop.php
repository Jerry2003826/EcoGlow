<?php
/**
 * Product listing page — warm-earth storefront theme.
 *
 * Static page: `PagesController::display()` renders this through the explicit
 * `/shop` route in config/routes.php. There is no products table yet, so the
 * catalogue below is a local placeholder array, exactly as the home page's
 * $bestSellers is. It uses the same field names, so when the table lands the
 * only thing that changes here is where $products comes from — the foreach and
 * the markup stay as they are.
 *
 * Filtering, sorting and paging run in the browser over the rendered grid (see
 * webroot/js/glow.js) rather than through query strings, so this template holds
 * no selection logic that a controller will later have to take back. Every
 * control on the page does what its label says.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Shop All Lighting');

/**
 * Same shape as home.php's $bestSellers, plus the two facets the filters need.
 * `price` is a number rather than a formatted string: it is what a DECIMAL
 * column holds, it is what the sort control compares, and it is what the cart
 * does arithmetic on. Formatting happens at the point of output.
 */
$products = [
    [
        'icon' => 'floor',
        'name' => 'Marlow Floor Lamp',
        'meta' => 'Oak & linen shade',
        'price' => 249.00,
        'flag' => 'New',
        'category' => 'Ambient Floor Lamps',
        'style' => 'Warm Minimal',
        'swatches' => [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
    ],
    [
        'icon' => 'ceiling',
        'name' => 'Halden Pendant',
        'meta' => 'Opal glass, dimmable LED',
        'price' => 189.00,
        'flag' => null,
        'category' => 'LED Ceiling Lights',
        'style' => 'Sculptural',
        'swatches' => [['Opal', '#E2DED2'], ['Forest', '#124C24']],
    ],
    [
        'icon' => 'smart',
        'name' => 'Aura Smart Bulb Set',
        'meta' => 'Four bulbs, warm to cool',
        'price' => 79.00,
        'flag' => 'Best seller',
        'category' => 'Smart Bulbs',
        'style' => 'Smart & Connected',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'icon' => 'solar',
        'name' => 'Fernway Solar Path Light',
        'meta' => 'Set of six, weatherproof',
        'price' => 129.00,
        'flag' => null,
        'category' => 'Outdoor Solar Lights',
        'style' => 'Warm Minimal',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
    [
        'icon' => 'wall',
        'name' => 'Brindle Wall Sconce',
        'meta' => 'Brushed brass, E14 fitting',
        'price' => 99.00,
        'flag' => null,
        'category' => 'Wall Sconces',
        'style' => 'Heritage',
        'swatches' => [['Brass', '#C9BCA9'], ['Charcoal', '#2F2E2C']],
    ],
    [
        'icon' => 'decor',
        'name' => 'Linen Drum Shade',
        'meta' => 'Natural linen, 45 cm',
        'price' => 59.00,
        'flag' => null,
        'category' => 'Decorative Accessories',
        'style' => 'Warm Minimal',
        'swatches' => [['Natural', '#E2DED2'], ['Clay', '#E2925E']],
    ],
    [
        'icon' => 'ceiling',
        'name' => 'Corva Ceiling Disc',
        'meta' => 'Flush mount, 3000 K',
        'price' => 219.00,
        'flag' => null,
        'category' => 'LED Ceiling Lights',
        'style' => 'Warm Minimal',
        'swatches' => [['Warm white', '#FBF9F5'], ['Charcoal', '#2F2E2C']],
    ],
    [
        'icon' => 'floor',
        'name' => 'Odette Arc Lamp',
        'meta' => 'Curved steel, marble base',
        'price' => 329.00,
        'flag' => 'New',
        'category' => 'Ambient Floor Lamps',
        'style' => 'Sculptural',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Brass', '#C9BCA9']],
    ],
    [
        'icon' => 'smart',
        'name' => 'Nimbus Smart Downlight',
        'meta' => 'Tunable white, app control',
        'price' => 45.00,
        'flag' => null,
        'category' => 'Smart Bulbs',
        'style' => 'Smart & Connected',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'icon' => 'solar',
        'name' => 'Kelso Solar Bollard',
        'meta' => 'Powder-coated, 40 cm',
        'price' => 179.00,
        'flag' => null,
        'category' => 'Outdoor Solar Lights',
        'style' => 'Sculptural',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
    [
        'icon' => 'wall',
        'name' => 'Ashby Twin Sconce',
        'meta' => 'Twin arm, fabric shades',
        'price' => 145.00,
        'flag' => null,
        'category' => 'Wall Sconces',
        'style' => 'Heritage',
        'swatches' => [['Brass', '#C9BCA9'], ['Natural', '#E2DED2']],
    ],
    [
        'icon' => 'decor',
        'name' => 'Rowan Rotary Dimmer',
        'meta' => 'Trailing-edge, 250 W',
        'price' => 39.00,
        'flag' => null,
        'category' => 'Decorative Accessories',
        'style' => 'Smart & Connected',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Natural', '#E2DED2']],
    ],
];

/** Facet values, derived from the catalogue so a new product cannot fall outside the filters. */
$categories = array_values(array_unique(array_column($products, 'category')));
$styles = array_values(array_unique(array_column($products, 'style')));
sort($categories);
sort($styles);

$productUrl = $this->Url->build('/shop/product');
?>
<div class="container py-5">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Shop</li>
        </ol>
    </nav>

    <div class="eg-page-head reveal">
        <span class="eg-eyebrow">Our range</span>
        <h1 class="section-title">All lighting</h1>
        <p class="section-lead mx-auto mt-3">
            Every fixture we stock, from statement ceilings to solar gardens. Each one is
            energy-rated, and our licensed team can install anything on this page.
        </p>
    </div>

    <div data-shop>
        <div class="eg-shop-bar reveal">
            <p class="eg-shop-count" aria-live="polite">
                Showing <span data-shop-shown><?= count($products) ?></span>
                of <span data-shop-total><?= count($products) ?></span> products
            </p>
            <div class="eg-sort">
                <label for="shop-sort">Sort by</label>
                <select class="form-select" id="shop-sort" data-shop-sort>
                    <option value="featured">Featured</option>
                    <option value="price-asc">Price: low to high</option>
                    <option value="price-desc">Price: high to low</option>
                    <option value="name-asc">Name: A &ndash; Z</option>
                </select>
            </div>
        </div>

        <div class="eg-filters reveal">
            <div class="eg-filter-group" role="group" aria-labelledby="filter-category">
                <span class="eg-eyebrow" id="filter-category">Category</span>
                <div class="eg-chip-row">
                    <button type="button" class="eg-chip is-active" aria-pressed="true"
                            data-shop-filter="category" data-value="">All categories</button>
                    <?php foreach ($categories as $category) : ?>
                        <button type="button" class="eg-chip" aria-pressed="false"
                                data-shop-filter="category" data-value="<?= h($category) ?>">
                            <?= h($category) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="eg-filter-group" role="group" aria-labelledby="filter-style">
                <span class="eg-eyebrow" id="filter-style">Style</span>
                <div class="eg-chip-row">
                    <button type="button" class="eg-chip is-active" aria-pressed="true"
                            data-shop-filter="style" data-value="">All styles</button>
                    <?php foreach ($styles as $style) : ?>
                        <button type="button" class="eg-chip" aria-pressed="false"
                                data-shop-filter="style" data-value="<?= h($style) ?>">
                            <?= h($style) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 reveal" data-shop-grid>
            <?php foreach ($products as $index => $product) : ?>
                <div class="col-6 col-lg-3" data-product
                     data-order="<?= $index ?>"
                     data-name="<?= h($product['name']) ?>"
                     data-price="<?= h((string)$product['price']) ?>"
                     data-category="<?= h($product['category']) ?>"
                     data-style="<?= h($product['style']) ?>">
                    <a class="product-card" href="<?= h($productUrl) ?>">
                        <span class="product-media">
                            <?php if ($product['flag'] !== null) : ?>
                                <span class="product-flag"><?= h($product['flag']) ?></span>
                            <?php endif; ?>
                            <?= $this->element('lamp_icon', ['name' => $product['icon']]) ?>
                        </span>
                        <span class="product-body">
                            <span class="product-name"><?= h($product['name']) ?></span>
                            <span class="product-meta"><?= h($product['meta']) ?></span>
                            <span class="product-price">$<?= h(number_format($product['price'], 2)) ?></span>
                            <span class="product-swatches">
                                <?php foreach ($product['swatches'] as [$swatchName, $swatchHex]) : ?>
                                    <span class="swatch" style="background: <?= h($swatchHex) ?>;" aria-hidden="true"></span>
                                <?php endforeach; ?>
                                <span class="visually-hidden">
                                    Available in <?= h(implode(', ', array_column($product['swatches'], 0))) ?>
                                </span>
                            </span>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="eg-shop-empty" data-shop-empty hidden>
            Nothing matches that combination yet. Clear a filter to see more.
        </p>

        <nav aria-label="Product pages">
            <ul class="pagination justify-content-center mt-5 mb-0" data-shop-pages></ul>
        </nav>
    </div>
</div>
