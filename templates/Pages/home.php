<?php
/**
 * Eco Glow Lighting landing page.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Modern Lighting & Smart Home Illumination');

$categories = [
    ['icon' => '💡', 'name' => 'LED Ceiling Lights', 'text' => 'Energy-efficient ceiling lights for every room.'],
    ['icon' => '🛋️', 'name' => 'Ambient Floor Lamps', 'text' => 'Warm, stylish floor lamps to set the mood.'],
    ['icon' => '📱', 'name' => 'Smart Bulbs', 'text' => 'App and voice controlled smart lighting.'],
    ['icon' => '☀️', 'name' => 'Outdoor Solar Lights', 'text' => 'Solar-powered garden and pathway lighting.'],
    ['icon' => '✨', 'name' => 'Decorative Accessories', 'text' => 'Finishing touches that make your space glow.'],
];
?>
<section class="hero p-5 mb-5 text-center">
    <div class="container py-5">
        <span class="badge glow-badge mb-3">Australian Owned &amp; Operated</span>
        <h1 class="display-4 fw-bold">Light Up Your Home, The Smart Way</h1>
        <p class="lead col-lg-8 mx-auto">
            Eco Glow Lighting supplies modern lighting fixtures and smart home illumination
            across Australia — with professional installation and repair services included.
        </p>
        <a class="btn btn-warning btn-lg mt-3" href="<?= $this->Url->build('/contact') ?>">Get in Touch</a>
    </div>
</section>

<section class="mb-5">
    <h2 class="text-center mb-4">Our Product Range</h2>
    <div class="row g-4 justify-content-center">
        <?php foreach ($categories as $category) : ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card category-card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="category-icon mb-3" aria-hidden="true"><?= $category['icon'] ?></div>
                        <h3 class="h5 card-title"><?= h($category['name']) ?></h3>
                        <p class="card-text text-muted"><?= h($category['text']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <h2 class="mb-3">Installation &amp; Repair Services</h2>
            <p class="text-muted">
                More than just a store — our licensed team installs and repairs what we sell.
                From a single smart bulb setup to a full-home lighting makeover, we handle
                the wiring so you can enjoy the glow.
            </p>
            <ul class="text-muted">
                <li>Professional in-home installation</li>
                <li>Smart lighting configuration and automation</li>
                <li>Fast repairs and replacements</li>
            </ul>
            <a class="btn btn-outline-dark mt-2" href="<?= $this->Url->build('/contact') ?>">Book a Service</a>
        </div>
        <div class="col-lg-6">
            <div class="hero p-5 text-center">
                <div class="display-1" aria-hidden="true">🔆</div>
                <p class="lead mt-3 mb-0">Brighter homes. Lower energy bills.</p>
            </div>
        </div>
    </div>
</section>

<section class="text-center py-4 border-top">
    <h2 class="h4 mb-3">Ready to transform your space?</h2>
    <p class="text-muted">Tell us what you need and Jordan will get back to you personally.</p>
    <a class="btn btn-warning" href="<?= $this->Url->build('/contact') ?>">Contact Us</a>
</section>
