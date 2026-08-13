<?php
/**
 * Eco Glow Lighting home page — warm-earth storefront theme.
 *
 * The catalogue is not modelled yet, so the collections and best-sellers below
 * are local placeholder arrays, exactly as the category list was before. When
 * the products table lands these two loops become the only things that change.
 *
 * The tiles and cards used to point at anchors further down this page because
 * there was nowhere else to send anyone. They now link to /shop and
 * /shop/product. Category tiles all land on the unfiltered listing: filtering
 * is applied in the browser there, so there is no /shop?category=… URL to
 * deep-link to until the products table makes one real.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Modern Lighting & Smart Home Illumination');

/**
 * The lamp marks live in templates/element/lamp_icon.php so that the six
 * drawings are declared once rather than in each of the five storefront
 * templates. `icon` therefore holds a key, and `price` a number — the shapes an
 * `icon`/`price` column would have — with formatting done at output.
 */
$collections = [
    ['icon' => 'ceiling', 'name' => 'LED Ceiling Lights', 'text' => 'Energy-efficient ceiling fixtures for every room.'],
    ['icon' => 'floor', 'name' => 'Ambient Floor Lamps', 'text' => 'Warm, sculptural floor lamps that set the mood.'],
    ['icon' => 'smart', 'name' => 'Smart Bulbs', 'text' => 'App and voice controlled smart lighting.'],
    ['icon' => 'solar', 'name' => 'Outdoor Solar Lights', 'text' => 'Solar-powered garden and pathway lighting.'],
    ['icon' => 'decor', 'name' => 'Decorative Accessories', 'text' => 'Shades, dimmers and finishing touches.'],
    ['icon' => 'wall', 'name' => 'Wall Sconces', 'text' => 'Soft, indirect light for halls and bedsides.'],
];

$bestSellers = [
    [
        'icon' => 'floor',
        'name' => 'Marlow Floor Lamp',
        'meta' => 'Oak & linen shade',
        'price' => 249.00,
        'flag' => 'New',
        'swatches' => [['Oak', '#C9BCA9'], ['Charcoal', '#2F2E2C'], ['Terracotta', '#E2925E']],
    ],
    [
        'icon' => 'ceiling',
        'name' => 'Halden Pendant',
        'meta' => 'Opal glass, dimmable LED',
        'price' => 189.00,
        'flag' => null,
        'swatches' => [['Opal', '#E2DED2'], ['Forest', '#124C24']],
    ],
    [
        'icon' => 'smart',
        'name' => 'Aura Smart Bulb Set',
        'meta' => 'Four bulbs, warm to cool',
        'price' => 79.00,
        'flag' => 'Best seller',
        'swatches' => [['Warm white', '#FBF9F5']],
    ],
    [
        'icon' => 'solar',
        'name' => 'Fernway Solar Path Light',
        'meta' => 'Set of six, weatherproof',
        'price' => 129.00,
        'flag' => null,
        'swatches' => [['Charcoal', '#2F2E2C'], ['Sand', '#C9BCA9']],
    ],
];

$steps = [
    ['title' => 'Consultation', 'text' => 'Tell us about your space and the mood you want.'],
    ['title' => 'On-site assessment', 'text' => 'We measure, plan circuits and design the lighting layout.'],
    ['title' => 'Installation & setup', 'text' => 'Licensed electricians install and configure every fixture.'],
    ['title' => 'Aftercare & repairs', 'text' => 'Fast repairs, replacements and smart-home tune-ups.'],
];
?>
<section class="hero-eg">
    <div class="container py-5">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 reveal">
                <span class="eg-eyebrow">Australian owned &amp; operated</span>
                <h1 class="hero-title">Light that feels like home.</h1>
                <p class="hero-lead mt-3">
                    Modern lighting fixtures and smart home illumination, chosen for warmth
                    and efficiency &mdash; with licensed installation and repairs included.
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-eg-primary" href="<?= $this->Url->build('/shop') ?>">
                        Shop collections
                        <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </a>
                    <a class="btn btn-eg-ghost" href="<?= $this->Url->build('/contact') ?>">Book a consultation</a>
                </div>
                <div class="hero-stats">
                    <div>
                        <span class="stat-num">12 yrs</span>
                        <span class="stat-label">Lighting Melbourne homes</span>
                    </div>
                    <div>
                        <span class="stat-num">6</span>
                        <span class="stat-label">Curated collections</span>
                    </div>
                    <div>
                        <span class="stat-num">A+</span>
                        <span class="stat-label">Energy-rated range</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal" data-reveal-step="2">
                <div class="hero-figure">
                    <span class="eg-wash" aria-hidden="true"></span>
                    <svg class="hero-lamp" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.7"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 1.5v4"/>
                        <path d="M3.5 13.5a8.5 8.5 0 0 1 17 0z"/>
                        <path d="M8.6 13.5a3.4 3.4 0 0 0 6.8 0"/>
                        <path d="M6 18.5h12"/>
                        <path d="M7.5 21h9"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-eg eg-band-alt" id="collections">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="eg-eyebrow">Our range</span>
            <h2 class="section-title">Shop by collection</h2>
            <p class="section-lead mx-auto mt-3">
                Six curated collections, from statement ceilings to solar gardens.
            </p>
        </div>
        <div class="row g-3 g-lg-4">
            <?php foreach ($collections as $index => $collection) : ?>
                <div class="col-sm-6 col-lg-4 reveal" data-reveal-step="<?= $index % 3 ?>">
                    <a class="category-tile h-100" href="<?= $this->Url->build('/shop') ?>">
                        <?= $this->element('lamp_icon', ['name' => $collection['icon']]) ?>
                        <span class="tile-name"><?= h($collection['name']) ?></span>
                        <p class="tile-text"><?= h($collection['text']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-eg" id="bestsellers">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 reveal">
            <div>
                <span class="eg-eyebrow">Loved this season</span>
                <h2 class="section-title">Best sellers</h2>
            </div>
            <a class="btn btn-eg-ghost btn-sm" href="<?= $this->Url->build('/shop') ?>">
                View all
                <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
        </div>

        <div class="eg-chip-row mb-4 reveal">
            <a class="eg-chip is-active" href="#bestsellers">All</a>
            <?php foreach (array_slice($collections, 0, 4) as $collection) : ?>
                <a class="eg-chip" href="<?= $this->Url->build('/shop') ?>"><?= h($collection['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($bestSellers as $index => $product) : ?>
                <div class="col-6 col-lg-3 reveal" data-reveal-step="<?= $index ?>">
                    <a class="product-card" href="<?= $this->Url->build('/shop/product') ?>">
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
    </div>
</section>

<section class="section-eg eg-band-dark" id="services">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-5 reveal">
                <span class="eg-eyebrow">Services</span>
                <h2 class="section-title mb-3">Installation &amp; repair</h2>
                <p>
                    More than a store &mdash; our licensed team installs and repairs
                    everything we sell. From a single smart bulb to a whole-home
                    lighting plan, we handle the wiring.
                </p>
                <div class="mt-4">
                    <?php foreach ($steps as $index => $step) : ?>
                        <div class="service-step">
                            <span class="step-no"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <div>
                                <h4><?= h($step['title']) ?></h4>
                                <p><?= h($step['text']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="btn btn-eg-primary mt-4" href="<?= $this->Url->build('/contact') ?>">
                    Book a service
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>
            </div>
            <div class="col-lg-7 reveal" data-reveal-step="2">
                <div class="compare-band" data-compare style="--split: 50%;">
                    <div class="compare-scene"></div>
                    <div class="compare-dark"></div>
                    <span class="compare-tag compare-tag-dark">Before</span>
                    <span class="compare-tag compare-tag-light">After</span>
                    <div class="compare-divider" role="slider" tabindex="0"
                         aria-label="Drag to compare before and after"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"></div>
                </div>
                <p class="small mt-3 mb-0">
                    The same living room, before and after an Eco Glow makeover.
                    Drag the handle, or focus it and use the arrow keys.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section-eg" id="about">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5 reveal">
                <span class="eg-eyebrow">About us</span>
                <h2 class="section-title mb-3">From hello to glow</h2>
                <p class="section-lead">
                    Eco Glow Lighting is a small Melbourne team with a simple idea:
                    good light should be warm, efficient and easy to live with. A
                    transparent process from the first call to the final switch-on.
                </p>
                <a class="btn btn-eg-ghost mt-2" href="<?= $this->Url->build('/contact') ?>">Talk to us</a>
            </div>
            <div class="col-lg-7 reveal" data-reveal-step="2">
                <ul class="timeline">
                    <?php foreach ($steps as $step) : ?>
                        <li>
                            <span class="node" aria-hidden="true"></span>
                            <h4><?= h($step['title']) ?></h4>
                            <p><?= h($step['text']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section-eg eg-band-alt">
    <div class="container text-center reveal">
        <span class="eg-eyebrow">Ready when you are</span>
        <h2 class="section-title">Ready to transform your space?</h2>
        <p class="section-lead mx-auto mt-3">
            Tell us what you need and Jordan will get back to you personally.
        </p>
        <a class="btn btn-eg-primary btn-lg mt-3" href="<?= $this->Url->build('/contact') ?>">
            Contact us
            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
    </div>
</section>
