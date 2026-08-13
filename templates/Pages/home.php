<?php
/**
 * Eco Glow Lighting landing page — night-glow brand theme.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Modern Lighting & Smart Home Illumination');

$icons = [
    'ceiling' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v3"/><path d="M12 19v3"/><path d="M2 12h3"/><path d="M19 12h3"/><path d="M4.9 4.9l2.1 2.1"/><path d="M17 17l2.1 2.1"/><path d="M4.9 19.1L7 17"/><path d="M17 7l2.1-2.1"/></svg>',
    'floor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l-1.5 6h-5L8 3z"/><path d="M12 9v10"/><path d="M8 21h8"/></svg>',
    'smart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.8.6 1.5 1.6 1.5 2.6V17h4v-.5c0-1 .7-2 1.5-2.6A6 6 0 0 0 12 3z"/><path d="M12 9v3"/><path d="M12 13.5v.01"/></svg>',
    'solar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="3.2"/><path d="M12 2.5v1.5"/><path d="M5.6 5.6l1 1"/><path d="M18.4 5.6l-1 1"/><path d="M3 9h1.5"/><path d="M19.5 9H21"/><path d="M12 13v3"/><path d="M6 21l2-5h8l2 5"/><path d="M8 18.5h8"/></svg>',
    'decor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 4.6L18.5 9l-4.7 1.4L12 15l-1.8-4.6L5.5 9l4.7-1.4L12 3z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15z"/><path d="M5 16l.7 1.8L7.5 18.5l-1.8.7L5 21l-.7-1.8L2.5 18.5l1.8-.7L5 16z"/></svg>',
];

$categories = [
    ['icon' => $icons['ceiling'], 'name' => 'LED Ceiling Lights', 'text' => 'Energy-efficient ceiling lights for every room.'],
    ['icon' => $icons['floor'], 'name' => 'Ambient Floor Lamps', 'text' => 'Warm, stylish floor lamps to set the mood.'],
    ['icon' => $icons['smart'], 'name' => 'Smart Bulbs', 'text' => 'App and voice controlled smart lighting.'],
    ['icon' => $icons['solar'], 'name' => 'Outdoor Solar Lights', 'text' => 'Solar-powered garden and pathway lighting.'],
    ['icon' => $icons['decor'], 'name' => 'Decorative Accessories', 'text' => 'Finishing touches that make your space glow.'],
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
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="hero-badge reveal">Australian Owned &amp; Operated</span>
                <h1 class="hero-title mt-4 reveal" data-reveal-step="1">
                    Light Up Your Home,<br><span class="shine">The Smart Way</span>
                </h1>
                <p class="hero-lead mx-auto mt-4 reveal" data-reveal-step="2">
                    Eco Glow Lighting supplies modern lighting fixtures and smart home illumination
                    across Australia — with professional installation and repair services included.
                </p>
                <div class="mt-4 reveal" data-reveal-step="3">
                    <a class="btn btn-glow btn-lg" href="<?= $this->Url->build('/contact') ?>">Get in Touch</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-eg">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-eyebrow">Our Range</div>
            <h2 class="section-title">Our Product Range</h2>
            <p class="text-muted col-lg-6 mx-auto">Five curated collections, from statement ceilings to solar gardens.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($categories as $index => $category) : ?>
                <div class="col-sm-6 col-lg-4 reveal" data-reveal-step="<?= $index ?>">
                    <div class="product-item">
                        <div class="lamp-visual">
                            <span class="halo" aria-hidden="true"></span>
                            <?= $category['icon'] ?>
                        </div>
                        <h3><?= h($category['name']) ?></h3>
                        <p><?= h($category['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-eg pt-0">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <div class="section-eyebrow">See the Difference</div>
            <h2 class="section-title">Drag to Compare</h2>
            <p class="text-muted col-lg-6 mx-auto">The same living room — before and after an Eco Glow makeover.</p>
        </div>
        <div class="compare-band reveal" data-compare style="--split: 50%;">
            <div class="compare-scene"></div>
            <div class="compare-dark"></div>
            <span class="compare-tag compare-tag-dark">Before</span>
            <span class="compare-tag compare-tag-light">After</span>
            <div class="compare-divider" role="slider" tabindex="0"
                 aria-label="Drag to compare before and after"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"></div>
        </div>
    </div>
</section>

<section class="section-eg pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-6 reveal">
                <div class="section-eyebrow">Services</div>
                <h2 class="section-title mb-4">Installation &amp; Repair Services</h2>
                <p class="text-muted">
                    More than just a store — our licensed team installs and repairs what we sell.
                    From a single smart bulb setup to a full-home lighting makeover, we handle
                    the wiring so you can enjoy the glow.
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
                <a class="btn btn-ghost-glow mt-4" href="<?= $this->Url->build('/contact') ?>">Book a Service</a>
            </div>
            <div class="col-lg-6 reveal" data-reveal-step="2">
                <div class="service-art">
                    <span class="bulb-glow" aria-hidden="true"></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18h6"/>
                        <path d="M10 21h4"/>
                        <path d="M12 2a7 7 0 0 0-4.1 12.7c.9.7 1.6 1.8 1.6 2.9V18h5v-.4c0-1.1.7-2.2 1.6-2.9A7 7 0 0 0 12 2z"/>
                        <path d="M12 6.5v2"/>
                        <path d="M9.8 8.5l1 1"/>
                        <path d="M14.2 8.5l-1 1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-eg pt-0">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5 reveal">
                <div class="section-eyebrow">How It Works</div>
                <h2 class="section-title mb-4">From Hello to Glow</h2>
                <p class="text-muted">
                    A simple, transparent process from the first phone call to the final switch-on.
                </p>
                <a class="btn btn-glow mt-2" href="<?= $this->Url->build('/contact') ?>">Start the Process</a>
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

<section class="section-eg pt-0">
    <div class="container">
        <div class="cta-band reveal">
            <div class="section-eyebrow mb-2">Ready When You Are</div>
            <h2 class="section-title">Ready to transform your space?</h2>
            <p class="text-muted col-lg-6 mx-auto">
                Tell us what you need and Jordan will get back to you personally.
            </p>
            <a class="btn btn-glow btn-lg mt-3" href="<?= $this->Url->build('/contact') ?>">Contact Us</a>
        </div>
    </div>
</section>
