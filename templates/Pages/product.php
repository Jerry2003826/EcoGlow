<?php
/**
 * Product detail page — warm-earth storefront theme.
 *
 * Static page: `PagesController::display()` renders this through the explicit
 * `/shop/product` route in config/routes.php. With no products table there is
 * nothing to look a slug up against, so the route carries no id yet and the
 * one product below is a local placeholder using the same field names as the
 * listing page. The real route becomes `/shop/product/{slug}` and $product
 * arrives from the controller; the markup does not change.
 *
 * Two controls on this page cannot do what their labels say until the orders
 * backend exists, so they are disabled rather than wired to something inert:
 * "Add to basket" and the basket count. Everything else is live — the finish
 * and globe pickers are real radio groups, and the quantity stepper works.
 *
 * The image well now holds the product photograph. It used to hold a line
 * drawing, and the well was tinted towards the selected finish with
 * `color-mix()` so that the swatch picker had something to show for itself. That
 * tint is gone: a colour wash over a photograph reads as a dirty print rather
 * than as a finish, and it would have sat on top of the photograph's own warm
 * greys. The picker instead names the chosen finish in text beside its legend,
 * which is legible, survives greyscale, and does not depend on the image.
 *
 * @var \App\View\AppView $this
 */
$product = [
    'image' => 'marlow-floor-lamp.webp',
    'alt' => 'Marlow floor lamp lit against a plaster wall, a turned oak column under a linen drum shade',
    'name' => 'Marlow Floor Lamp',
    'meta' => 'Turned oak, linen shade, 1.45 m',
    'price' => 249.00,
    'flag' => 'New',
    'category' => 'Ambient Floor Lamps',
    'style' => 'Warm Minimal',
    'swatches' => [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
];

$this->assign('title', $product['name']);

$globes = [
    'E27 warm white, 2700 K',
    'E27 smart tunable white',
    'No globe, fitting only',
];

$specs = [
    'Height' => '1.45 m',
    'Shade diameter' => '38 cm',
    'Materials' => 'Solid oak, natural linen',
    'Fitting' => 'E27, max 60 W equivalent',
    'Globe included' => 'Yes, 9 W LED (2700 K)',
    'Energy rating' => 'A+',
    'Cable' => '2.0 m, in-line rotary dimmer',
];

/** Reuses the listing page's card component, so the grid at the foot needs only these six fields. */
$related = [
    [
        'image' => 'odette-arc-lamp.webp',
        'alt' => 'Odette arc lamp curving out of a marble base over an oak floor',
        'name' => 'Odette Arc Lamp',
        'meta' => 'Brass arc, marble base, 2.1 m',
        'price' => 329.00,
        'swatches' => [['Charcoal', '#2F2E2C'], ['Brass', '#C9BCA9']],
    ],
    [
        'image' => 'linen-drum-shade.webp',
        'alt' => 'Linen drum shade standing on its own, an undyed cylinder with a visible weave',
        'name' => 'Linen Drum Shade',
        'meta' => 'Undyed linen, 45 cm, E27 ring',
        'price' => 59.00,
        'swatches' => [['Natural', '#E2DED2'], ['Clay', '#E2925E']],
    ],
    [
        'image' => 'aura-smart-bulbs.webp',
        'alt' => 'Four Aura globes laid in a row on brass screw bases, two of them lit',
        'name' => 'Aura Smart Bulb Set',
        'meta' => 'Four E27 globes, 2200–6500K',
        'price' => 79.00,
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'image' => 'rowan-rotary-dimmer.webp',
        'alt' => 'Rowan rotary dimmer, a brushed brass plate with a knurled knob',
        'name' => 'Rowan Rotary Dimmer',
        'meta' => 'Trailing-edge rotary, 250 W, brass',
        'price' => 39.00,
        'swatches' => [['Charcoal', '#2F2E2C'], ['Natural', '#E2DED2']],
    ],
];

$shopUrl = $this->Url->build('/shop');
$productUrl = $this->Url->build('/shop/product');
?>
<div class="container py-5" data-product-detail>
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= h($shopUrl) ?>">Shop</a></li>
            <li aria-current="page"><?= h($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5 product-layout">
        <div class="col-lg-7 product-media-col reveal">
            <span class="product-media">
                <?php if ($product['flag'] !== null) : ?>
                    <span class="product-flag"><?= h($product['flag']) ?></span>
                <?php endif; ?>
                <?php
                /* Above the fold on every viewport, so it loads eagerly and asks
                   for priority: it is the largest element on the page and
                   therefore the one Largest Contentful Paint is measured on. */
                ?>
                <?= $this->Html->image('products/' . $product['image'], [
                    'alt' => $product['alt'],
                    'width' => 800,
                    'height' => 800,
                    'fetchpriority' => 'high',
                    'decoding' => 'async',
                ]) ?>
            </span>
        </div>

        <div class="col-lg-5 product-info-col reveal" data-reveal-step="1">
            <span class="eg-eyebrow"><?= h($product['category']) ?></span>
            <h1 class="section-title h2"><?= h($product['name']) ?></h1>
            <p class="product-meta"><?= h($product['meta']) ?></p>
            <p class="product-price product-price-lg mt-3 mb-0">
                $<?= h(number_format($product['price'], 2)) ?>
            </p>
            <p class="text-muted small">Includes GST. Free delivery Australia-wide over $150.</p>

            <p class="mt-4">
                A 1.45 m oak column, turned and oiled rather than lacquered, under a 45 cm
                shade sewn from undyed linen. The shade is open top and bottom, so the light
                goes up the wall as well as onto the page &mdash; the lamp for a reading chair.
                The in-line rotary dimmer takes it down to about 10 per cent.
            </p>

            <?php
            /* The chosen finish is named in text next to the legend as well as
               drawn as a ring on the dot. The dot's own colour is the thing being
               chosen and so cannot also carry the state, and the well beside it
               is a photograph that no longer changes with the choice. */
            ?>
            <fieldset class="eg-option">
                <legend class="eg-eyebrow">
                    Finish &mdash; <span data-finish-name><?= h($product['swatches'][0][0]) ?></span>
                </legend>
                <div class="eg-swatch-row">
                    <?php foreach ($product['swatches'] as $swatchIndex => [$swatchName, $swatchHex]) : ?>
                        <input class="eg-choice-input" type="radio" name="finish"
                               id="finish-<?= $swatchIndex ?>" value="<?= h($swatchName) ?>"
                               <?= $swatchIndex === 0 ? 'checked' : '' ?>>
                        <label class="eg-swatch-label" for="finish-<?= $swatchIndex ?>">
                            <span class="eg-swatch-dot" style="background: <?= h($swatchHex) ?>;" aria-hidden="true"></span>
                            <span class="visually-hidden"><?= h($swatchName) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="eg-option">
                <legend class="eg-eyebrow">Globe</legend>
                <div class="eg-chip-row">
                    <?php foreach ($globes as $globeIndex => $globe) : ?>
                        <input class="eg-choice-input" type="radio" name="globe"
                               id="globe-<?= $globeIndex ?>" value="<?= h($globe) ?>"
                               <?= $globeIndex === 0 ? 'checked' : '' ?>>
                        <label class="eg-chip" for="globe-<?= $globeIndex ?>"><?= h($globe) ?></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="eg-option">
                <label class="eg-eyebrow d-block" for="product-qty">Quantity</label>
                <div class="eg-qty" data-qty>
                    <button type="button" data-qty-step="-1" aria-label="Decrease quantity">&minus;</button>
                    <input type="number" id="product-qty" name="quantity" value="1" min="1" max="99"
                           inputmode="numeric" autocomplete="off" data-qty-input>
                    <button type="button" data-qty-step="1" aria-label="Increase quantity">+</button>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="button" class="btn btn-eg-primary" disabled
                        aria-describedby="basket-pending">
                    Add to basket
                </button>
                <a class="btn btn-eg-ghost" href="<?= $this->Url->build('/contact') ?>">
                    Ask about installation
                </a>
            </div>
            <p class="eg-note" id="basket-pending">
                Ordering opens when the checkout backend lands. Until then, ask us to
                reserve a fixture and we will hold it.
            </p>

            <dl class="eg-kv-list mt-4 mb-0">
                <div class="eg-kv-row">
                    <dt>Delivery</dt>
                    <dd>2 &ndash; 5 business days</dd>
                </div>
                <div class="eg-kv-row">
                    <dt>Installation</dt>
                    <dd>Licensed electricians, from $89</dd>
                </div>
                <div class="eg-kv-row">
                    <dt>Warranty</dt>
                    <dd>3 years, parts and labour</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="row mt-5 pt-4">
        <div class="col-lg-7 reveal">
            <h2 class="section-title h4 mb-3">Product details</h2>
            <div class="accordion eg-accordion" id="productDetails">
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detail-about" aria-expanded="true" aria-controls="detail-about">
                            About this lamp
                        </button>
                    </h3>
                    <div class="accordion-collapse collapse show" id="detail-about"
                         data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            <p class="mb-0">
                                The Marlow is built around a single turned oak column, oiled rather
                                than lacquered so it keeps the grain. The shade is sewn from
                                undyed Australian linen, which warms the light without tinting it.
                                Assembly takes one Allen key and about five minutes.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detail-specs" aria-expanded="false" aria-controls="detail-specs">
                            Specifications
                        </button>
                    </h3>
                    <div class="accordion-collapse collapse" id="detail-specs"
                         data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            <dl class="eg-kv-list mb-0">
                                <?php foreach ($specs as $specLabel => $specValue) : ?>
                                    <div class="eg-kv-row">
                                        <dt><?= h($specLabel) ?></dt>
                                        <dd><?= h($specValue) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detail-delivery" aria-expanded="false" aria-controls="detail-delivery">
                            Delivery &amp; installation
                        </button>
                    </h3>
                    <div class="accordion-collapse collapse" id="detail-delivery"
                         data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            <p class="mb-0">
                                Flat-rate $14.95 delivery Australia-wide, free over $150. Floor
                                lamps need no wiring, but if you would rather we set it up
                                alongside other work, our electricians can add it to a visit.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detail-returns" aria-expanded="false" aria-controls="detail-returns">
                            Warranty &amp; returns
                        </button>
                    </h3>
                    <div class="accordion-collapse collapse" id="detail-returns"
                         data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            <p class="mb-0">
                                Three-year warranty covering parts and labour, on top of your
                                rights under Australian Consumer Law. Change of mind returns
                                accepted within 30 days, unused and in the original box.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="section-eg eg-band-alt">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 reveal">
            <div>
                <span class="eg-eyebrow">Goes well with</span>
                <h2 class="section-title h3 mb-0">Complete the room</h2>
            </div>
            <a class="btn btn-eg-ghost btn-sm" href="<?= h($shopUrl) ?>">
                Shop all
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
        </div>
        <div class="row g-4 reveal" data-reveal-step="1">
            <?php foreach ($related as $item) : ?>
                <div class="col-6 col-lg-3">
                    <a class="product-card" href="<?= h($productUrl) ?>">
                        <span class="product-media">
                            <?= $this->Html->image('products/' . $item['image'], [
                                'alt' => $item['alt'],
                                'width' => 800,
                                'height' => 800,
                                'loading' => 'lazy',
                                'decoding' => 'async',
                            ]) ?>
                        </span>
                        <span class="product-body">
                            <span class="product-name"><?= h($item['name']) ?></span>
                            <span class="product-meta"><?= h($item['meta']) ?></span>
                            <span class="product-price">$<?= h(number_format($item['price'], 2)) ?></span>
                            <span class="product-swatches">
                                <?php foreach ($item['swatches'] as [$swatchName, $swatchHex]) : ?>
                                    <span class="swatch" style="background: <?= h($swatchHex) ?>;" aria-hidden="true"></span>
                                <?php endforeach; ?>
                                <span class="visually-hidden">
                                    Available in <?= h(implode(', ', array_column($item['swatches'], 0))) ?>
                                </span>
                            </span>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
