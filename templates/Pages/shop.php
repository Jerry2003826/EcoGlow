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
 * webroot/js/glow.js), so this template holds no selection logic that a
 * controller will later have to take back. A ?category= or ?style= in the
 * address bar is read there too, which is how a link from another page hands a
 * filter over; either way this template renders the whole catalogue. Every
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
 *
 * `image` replaces the `icon` key the line-art marks used to be chosen by, and
 * `alt` travels with it: on a catalogue page the photograph is the product's own
 * picture and the thing a visitor is scanning, so it describes the fitting
 * rather than sitting empty. `meta` states the fitting, the size and the colour
 * temperature instead of an adjective, which is also what makes two similar
 * sconces tellable apart without opening either.
 */
$products = [
    [
        'image' => 'marlow-floor-lamp.webp',
        'alt' => 'Marlow floor lamp lit against a plaster wall, oak column under a linen drum shade',
        'name' => 'Marlow Floor Lamp',
        'meta' => 'Turned oak, linen shade, 1.45 m',
        'price' => 249.00,
        'flag' => 'New',
        'category' => 'Ambient Floor Lamps',
        'style' => 'Warm Minimal',
        'swatches' => [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
    ],
    [
        'image' => 'halden-pendant.webp',
        'alt' => 'Halden pendant hanging on a slim brass stem, its opal glass globe lit',
        'name' => 'Halden Pendant',
        'meta' => 'Opal glass globe, 20 cm, E27',
        'price' => 189.00,
        'flag' => null,
        'category' => 'LED Ceiling Lights',
        'style' => 'Sculptural',
        'swatches' => [['Opal', '#E2DED2'], ['Forest', '#124C24']],
    ],
    [
        'image' => 'aura-smart-bulbs.webp',
        'alt' => 'Four Aura globes laid in a row on brass screw bases, two of them lit',
        'name' => 'Aura Smart Bulb Set',
        'meta' => 'Four E27 globes, 2200–6500K',
        'price' => 79.00,
        'flag' => 'Best seller',
        'category' => 'Smart Bulbs',
        'style' => 'Smart & Connected',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'image' => 'fernway-solar-path.webp',
        'alt' => 'Three Fernway path lights spiked along a gravel path, lit at dusk',
        'name' => 'Fernway Solar Path Light',
        'meta' => 'Set of six spikes, IP65, 3000K',
        'price' => 129.00,
        'flag' => null,
        'category' => 'Outdoor Solar Lights',
        'style' => 'Warm Minimal',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
    [
        /* The photograph is an opal glass dome with no brass anywhere on it, so
           the finish in this line reads off the picture. It said "brushed brass"
           while the well held a line drawing, which nothing contradicted. */
        'image' => 'brindle-wall-sconce.webp',
        'alt' => 'Brindle wall sconce, an opal glass dome lit flush against a plaster wall',
        'name' => 'Brindle Wall Sconce',
        'meta' => 'Opal glass dome, 18 cm, E14',
        'price' => 99.00,
        'flag' => null,
        'category' => 'Wall Sconces',
        'style' => 'Heritage',
        'swatches' => [['Opal', '#E2DED2'], ['Charcoal', '#2F2E2C']],
    ],
    [
        'image' => 'linen-drum-shade.webp',
        'alt' => 'Linen drum shade standing on its own, an undyed cylinder with a visible weave',
        'name' => 'Linen Drum Shade',
        'meta' => 'Undyed linen, 45 cm, E27 ring',
        'price' => 59.00,
        'flag' => null,
        'category' => 'Decorative Accessories',
        'style' => 'Warm Minimal',
        'swatches' => [['Natural', '#E2DED2'], ['Clay', '#E2925E']],
    ],
    [
        'image' => 'corva-ceiling-disc.webp',
        'alt' => 'Corva ceiling disc mounted flush, an opal diffuser inside a brushed brass rim',
        'name' => 'Corva Ceiling Disc',
        'meta' => 'Flush mount, 40 cm, 3000K',
        'price' => 219.00,
        'flag' => null,
        'category' => 'LED Ceiling Lights',
        'style' => 'Warm Minimal',
        'swatches' => [['Warm white', '#FBF9F5'], ['Charcoal', '#2F2E2C']],
    ],
    [
        'image' => 'odette-arc-lamp.webp',
        'alt' => 'Odette arc lamp curving out of a marble base over an oak floor',
        'name' => 'Odette Arc Lamp',
        'meta' => 'Brass arc, marble base, 2.1 m',
        'price' => 329.00,
        'flag' => 'New',
        'category' => 'Ambient Floor Lamps',
        'style' => 'Sculptural',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Brass', '#C9BCA9']],
    ],
    [
        'image' => 'nimbus-smart-downlight.webp',
        'alt' => 'Two Nimbus downlights recessed in a ceiling, throwing overlapping pools of warm light',
        'name' => 'Nimbus Smart Downlight',
        'meta' => 'Tunable 2700–5000K, 90 mm cut-out',
        'price' => 45.00,
        'flag' => null,
        'category' => 'Smart Bulbs',
        'style' => 'Smart & Connected',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'image' => 'kelso-solar-bollard.webp',
        'alt' => 'Kelso solar bollard lighting a sand path beside dry grasses',
        'name' => 'Kelso Solar Bollard',
        'meta' => 'Powder-coated, 40 cm, IP65',
        'price' => 179.00,
        'flag' => null,
        'category' => 'Outdoor Solar Lights',
        'style' => 'Sculptural',
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
    [
        'image' => 'ashby-twin-sconce.webp',
        'alt' => 'Ashby twin sconce on a plaster wall, two brass arms under small linen shades',
        'name' => 'Ashby Twin Sconce',
        'meta' => 'Twin brass arms, linen shades, E14',
        'price' => 145.00,
        'flag' => null,
        'category' => 'Wall Sconces',
        'style' => 'Heritage',
        'swatches' => [['Brass', '#C9BCA9'], ['Natural', '#E2DED2']],
    ],
    [
        'image' => 'rowan-rotary-dimmer.webp',
        'alt' => 'Rowan rotary dimmer, a brushed brass plate with a knurled knob',
        'name' => 'Rowan Rotary Dimmer',
        'meta' => 'Trailing-edge rotary, 250 W, brass',
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
            Twelve fittings in oak, undyed linen, opal glass, brushed brass and powder-coated
            aluminium. Everything indoors runs at 2700&ndash;3000K and dims on a trailing-edge
            circuit; everything outdoors is IP65. Our licensed electricians install any of it.
        </p>
    </div>

    <div data-shop>
        <div class="eg-shop-bar reveal">
            <?php
            /* Collapsed by default, like the Nook reference the client
               supplied: eleven chips laid flat read as a tag cloud rather than
               as a filter.

               <details> rather than a scripted panel, so the trigger arrives
               with keyboard operation and the right expanded state already in
               place, and so the panel still opens when scripting does not run.
               Whatever is applied also shows as a removable tag below, because
               a shut panel would otherwise be the only record of it. */
            ?>
            <details class="eg-filters" data-shop-filters>
                <summary class="eg-filter-toggle">
                    <svg class="eg-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h8"/><path d="M16 7h4"/><circle cx="12" cy="7" r="2.2"/><path d="M4 17h4"/><path d="M12 17h8"/><circle cx="8" cy="17" r="2.2"/></svg>
                    Filters<span class="eg-filter-tally" data-shop-tally hidden></span>
                    <svg class="eg-filter-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>

                <div class="eg-filter-panel">
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
            </details>

            <?php
            /* Straight after the disclosure, so it is the next thing read out
               and the next thing seen once the bar stacks. Empty and hidden
               until something is applied: the tags are the one part of this bar
               that mirrors browser-side state, so webroot/js/glow.js fills the
               list and unhides the row. */
            ?>
            <div class="eg-active" data-shop-active hidden>
                <span class="eg-eyebrow">Filtering by</span>
                <ul class="eg-tag-row" data-shop-tags></ul>
                <button type="button" class="eg-active-clear" data-shop-clear>Clear all filters</button>
            </div>

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
                            <?php
                            /* The first row is above the fold on every viewport this
                               grid has, so those four load eagerly and the rest wait.
                               `width`/`height` are the intrinsic pixel size: the well
                               has its own aspect-ratio, but the attributes are what
                               reserve the box before the stylesheet arrives. */
                            ?>
                            <?= $this->Html->image('products/' . $product['image'], [
                                'alt' => $product['alt'],
                                'width' => 800,
                                'height' => 800,
                                'loading' => $index < 4 ? 'eager' : 'lazy',
                                'decoding' => 'async',
                            ]) ?>
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
