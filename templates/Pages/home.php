<?php

$this->assign('title', 'Eco Glow Lighting');
$this->Html->css('landingPage/home', ['block' => true, 'timestamp' => true]);

$landingTexts = $landingTexts ?? [];
$carouselBlocks = $carouselBlocks ?? [];
$featureBlocks = $featureBlocks ?? [];
?>

<div class="landing-page">
    <?php if ($carouselBlocks !== []) : ?>
    <section class="home-carousel" aria-label="Featured lighting" data-carousel>
        <div class="carousel-track">
            <?php foreach ($carouselBlocks as $index => $slide) : ?>
                <article
                    class="carousel-slide<?= $index === 0 ? ' is-active' : '' ?>"
                    data-slide
                    aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                >
                    <?= $this->Html->image(
                        $slide->ImagePath,
                        [
                            'alt' => $slide->AltText,
                            'class' => 'carousel-image',
                        ],
                    ) ?>
                    <div class="carousel-caption">
                        <p class="section-eyebrow"><?= h($landingTexts['carousel.eyebrow'] ?? '') ?></p>
                        <h2><?= h($slide->Title) ?></h2>
                        <p><?= h($slide->Description) ?></p>
                        <?= $this->Html->link(
                            $slide->ButtonLabel,
                            $slide->ButtonUrl,
                            ['class' => 'home-btn home-btn-light'],
                        ) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="carousel-footer">
            <div class="carousel-dots" role="tablist" aria-label="Choose featured slide">
                <?php foreach ($carouselBlocks as $index => $slide) : ?>
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
    <?php endif; ?>

    <section class="hero" id="about" aria-labelledby="hero-title">
        <div class="hero-inner">
            <div class="hero-content">
                <p class="section-eyebrow"><?= h($landingTexts['hero.eyebrow'] ?? '') ?></p>

                <h1 id="hero-title"><?= h($landingTexts['hero.title'] ?? '') ?></h1>

                <p>
                    <?= h($landingTexts['hero.body1'] ?? '') ?>
                </p>

                <p>
                    <?= h($landingTexts['hero.body2'] ?? '') ?>
                </p>

                <div class="hero-buttons">
                    <?= $this->Html->link(
                        $landingTexts['hero.button_primary_label'] ?? '',
                        $landingTexts['hero.button_primary_url'] ?? '/products',
                        ['class' => 'home-btn home-btn-primary'],
                    ) ?>

                    <?= $this->Html->link(
                        $landingTexts['hero.button_secondary_label'] ?? '',
                        $landingTexts['hero.button_secondary_url'] ?? '/enquiries/add',
                        ['class' => 'home-btn home-btn-secondary'],
                    ) ?>
                </div>
            </div>
            <div class="hero-image">
                <?= $this->Html->image(
                    $landingTexts['hero.image'] ?? 'landing/pendants.jpg',
                    [
                        'class' => 'hero-photo',
                        'alt' => $landingTexts['hero.image_alt'] ?? '',
                    ],
                ) ?>
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
                    <p class="section-eyebrow"><?= h($landingTexts['products.eyebrow'] ?? '') ?></p>

                    <h2 id="featured-products-title">
                        <?= h($landingTexts['products.title'] ?? '') ?>
                    </h2>
                </div>

                <p class="section-description">
                    <?= h($landingTexts['products.description'] ?? '') ?>
                </p>
            </div>

            <?php if ($featureBlocks !== []) : ?>
            <div class="product-list">
                <?php foreach ($featureBlocks as $product) : ?>
                    <article class="product-card">
                        <div class="product-card-image">
                            <?= $this->Html->image(
                                $product->ImagePath,
                                ['alt' => $product->AltText],
                            ) ?>
                        </div>

                        <div class="product-card-content">
                            <h3><?= h($product->Title) ?></h3>

                            <p><?= h($product->Description) ?></p>

                            <?= $this->Html->link(
                                $product->ButtonLabel,
                                $product->ButtonUrl,
                                ['class' => 'product-card-link'],
                            ) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-service" id="services" aria-labelledby="home-service-title">
        <div class="home-service-inner">
            <div class="home-service-content">
                <p class="section-eyebrow"><?= h($landingTexts['service.eyebrow'] ?? '') ?></p>

                <h2 id="home-service-title">
                    <?= h($landingTexts['service.title'] ?? '') ?>
                </h2>

                <p>
                    <?= h($landingTexts['service.body'] ?? '') ?>
                </p>
            </div>

            <?= $this->Html->link(
                $landingTexts['service.button_label'] ?? '',
                $landingTexts['service.button_url'] ?? '/enquiries/add',
                ['class' => 'home-btn home-btn-light'],
            ) ?>
        </div>
    </section>
</div>
