<?php

$this->assign('title', 'Eco Glow Lighting');
$this->Html->css('landingPage/home', ['block' => true, 'timestamp' => true]);

$carouselSlides = [
    [
        'image' => 'interior.jpg',
        'title' => 'A brighter space. A warmer welcome.',
        'description' => 'Eco Glow Lighting offers a range of modern lighting solutions for your home, including pendant, wall, table and floor lighting.',
        'button' => 'Explore lighting', 'url' => '#shop',
        'alt' => 'Clear pendant lights hanging in a row',
    ],
    [
        'image' => 'opal-lamp.jpg',
        'title' => 'Great lighting starts with a conversation',
        'description' => 'Get in touch with our team to discuss your lighting needs.',
        'button' => 'Talk to our team', 
        'url' => ['controller' => 'Enquiries', 'action' => 'add'],
        'alt' => 'Opal table lamp on a side table in a living room',
    ],
    [
        'image' => 'pendants.jpg',
        'title' => 'Lighting for every space',
        'description' => 'Explore our range of pendant, wall, table and floor lighting for your home.',
        'button' => 'View our products', 'url' => '/products',
        'alt' => 'Pendant lights in a modern interior',
    ],
];

$featuredProducts = [
    [
        'name' => 'Pendant Lights',
        'description' => 'Ceiling-mounted and pendant lighting for indoor spaces.',
        'alt' => 'Oak pendant light above a dining table',
    ],
    [
        'name' => 'Table Lamps',
        'description' => 'A simple table lamp with a natural linen shade, ideal for bedside tables and reading corners.',
        'alt' => 'Linen table lamp on a side table',
    ],
    [
        'name' => 'Wall Lights',
        'description' => 'Wall-mounted lighting for indoor and decorative use.',
        'alt' => 'Brass wall light mounted on an interior wall',
    ],
    [
        'name' => 'Floor Lamps',
        'description' => 'Freestanding lighting for living and reading areas.',
        'alt' => 'Dome floor lamp beside an armchair',
    ],
    [
        'name' => 'Outdoor Lights',
        'description' => 'Weather-resistant lighting for gardens and outdoor areas.',
        'alt' => 'Outdoor lights in a garden setting',
    ],
    [
        'name' => 'Smart Lights',
        'description' => 'An energy-efficient smart globe with adjustable brightness for a flexible home lighting setup.',
        'alt' => 'Smart LED light globe',
    ],
];
?>

<div class="landing-page">
    <section class="home-carousel" aria-label="Featured lighting" data-carousel>
        <div class="carousel-track">
            <?php foreach ($carouselSlides as $index => $slide): ?>
                <article
                    class="carousel-slide<?= $index === 0 ? ' is-active' : '' ?>"
                    data-slide
                    aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                >
                    <?= $this->Html->image(
                        'landing/' . $slide['image'],
                        [
                            'alt' => h($slide['alt']),
                            'class' => 'carousel-image',
                        ]
                    ) ?>
                    <div class="carousel-caption">
                        <p class="section-eyebrow">Featured collection</p>
                        <h2><?= h($slide['title']) ?></h2>
                        <p><?= h($slide['description']) ?></p>
                        <?= $this->Html->link($slide['button'], $slide['url'], ['class' => 'home-btn home-btn-light']) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="carousel-footer">
            <div class="carousel-dots" role="tablist" aria-label="Choose featured slide">
                <?php foreach ($carouselSlides as $index => $slide): ?>
                    <button
                        class="carousel-dot<?= $index === 0 ? ' is-active' : '' ?>"
                        type="button"
                        data-carousel-dot="<?= $index ?>"
                        role="tab"
                        aria-label="Show slide <?= $index + 1 ?>"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="hero" id="about" aria-labelledby="hero-title">
        <div class="hero-inner">
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
                        '/products',
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
                <?= $this->Html->image('landing/pendants.jpg',
                [
                    'class' => 'hero-photo',
                    'alt' => 'Pendant lights in a modern interior',
                ]) ?>
            </div>
        </div>            
    </section>

    <section
        class="featured-products"
        id="shop"
        aria-labelledby="featured-products-title"
    >   
        <div class="products-inner">
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
                            <?= $this->Html->image(
                                'landing/' . [
                                    'interior.jpg',
                                    'table-lamp.jpg',
                                    'wall-light.jpg',
                                    'floor-lamp.jpg',
                                    'outdoor-lighting.jpg',
                                    'globe.jpg',
                                ][array_search($product['name'], array_column($featuredProducts, 'name'), true)],
                                ['alt' => h($product['alt'])]
                            ) ?>
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
        </div>
    </section>

    <section class="home-service" id="services" aria-labelledby="home-service-title">
        <div class="home-service-inner">
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
        </div>
    </section>
</div>
