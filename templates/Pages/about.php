<?php
// templates/Pages/about.php

$this->assign('title', 'About Us');
$this->Html->css('landingPage/home', ['block' => true]);
?>

<div class="landing-page">
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <p class="section-eyebrow">Lighting for everyday living</p>
                <h1>About Eco Glow Lighting</h1>
                <p>
                    We provide premium decorative lighting, expert advice and
                    dependable support for residential and commercial projects.
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
                <?= $this->Html->image('landing/pendants.jpg', [
                    'class' => 'hero-photo',
                    'alt' => 'Pendant lights in a modern interior',
                ]) ?>
            </div>
        </div>
    </section>
</div>
