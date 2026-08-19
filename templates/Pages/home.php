<?php
/**
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Eco Glow Lighting');
$this->Html->css('landingPage/home', ['block' => true]);

$featuredProducts = [
    [
        'name' => 'Oak Pendant Light',
        'description' => 'A warm timber pendant designed to bring soft, focused light to kitchens and dining spaces.',
        'alt' => 'Oak pendant light above a dining table',
    ],
    [
        'name' => 'Linen Table Lamp',
        'description' => 'A simple table lamp with a natural linen shade, ideal for bedside tables and reading corners.',
        'alt' => 'Linen table lamp on a side table',
    ],
    [
        'name' => 'Brass Wall Light',
        'description' => 'A compact wall light with a brushed brass finish for hallways, bedrooms and living areas.',
        'alt' => 'Brass wall light mounted on an interior wall',
    ],
    [
        'name' => 'Dome Floor Lamp',
        'description' => 'A practical floor lamp with a wide shade that provides comfortable light for reading and relaxing.',
        'alt' => 'Dome floor lamp beside an armchair',
    ],
    [
        'name' => 'Opal Ceiling Light',
        'description' => 'A clean, low-profile ceiling light that distributes an even glow throughout smaller rooms.',
        'alt' => 'Opal glass ceiling light in a modern room',
    ],
    [
        'name' => 'Smart LED Globe',
        'description' => 'An energy-efficient smart globe with adjustable brightness for a flexible home lighting setup.',
        'alt' => 'Smart LED light globe',
    ],
];
?>

<div class="landing-page">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-content">
            <p class="section-eyebrow">Lighting for everyday living</p>

            <h1 id="hero-title">All things Lighting.</h1>

            <p>
                Eco Glow Lighting offers modern lighting fixtures
                and smart home solutions designed to make your
                home feel brighter and better.
            </p>

            <p>
                Enquire about our installation and repair services,
                or explore our featured lighting range below.
            </p>

            <div class="hero-buttons">
                <?= $this->Html->link(
                    'Shop all Lighting',
                    '#featured-products',
                    ['class' => 'home-btn home-btn-primary']
                ) ?>

                <?= $this->Html->link(
                    'Make an Enquiry',
                    ['controller' => 'Enquiries', 'action' => 'add'],
                    ['class' => 'home-btn home-btn-secondary']
                ) ?>
            </div>
        </div>

        <div class="hero-image">
            <div
                class="hero-photo image-placeholder"
                role="img"
                aria-label="Warm pendant lights in a modern living space"
            >
                Hero Image
            </div>
        </div>
    </section>

    <section
        class="featured-products"
        id="featured-products"
        aria-labelledby="featured-products-title"
    >
        <div class="section-heading">
            <div>
                <p class="section-eyebrow">Our Product Range</p>

                <h2 id="featured-products-title">
                    Lighting for every space
                </h2>
            </div>

            <p class="section-description">
                Explore warm, practical and energy-efficient lighting
                selected for modern Australian homes.
            </p>
        </div>

        <div class="product-list">
            <?php foreach ($featuredProducts as $product): ?>
                <article class="product-card">
                    <div class="product-card-image">
                        <div
                            class="image-placeholder"
                            role="img"
                            aria-label="<?= h($product['alt']) ?>"
                        >
                            Product Image
                        </div>
                    </div>

                    <div class="product-card-content">
                        <h3><?= h($product['name']) ?></h3>

                        <p><?= h($product['description']) ?></p>

                        <?= $this->Html->link(
                            'Ask about this light',
                            [
                                'controller' => 'Enquiries',
                                'action' => 'add',
                            ],
                            ['class' => 'product-card-link']
                        ) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="home-service" aria-labelledby="home-service-title">
        <div class="home-service-content">
            <p class="section-eyebrow">Installation and repairs</p>

            <h2 id="home-service-title">
                Need help with your lighting?
            </h2>

            <p>
                Talk to us about product selection, installation,
                replacements and lighting repairs for your home.
            </p>
        </div>

        <?= $this->Html->link(
            'Contact us',
            ['controller' => 'Enquiries', 'action' => 'add'],
            ['class' => 'home-btn home-btn-light']
        ) ?>
    </section>
</div>
