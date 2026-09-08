<?php
// templates/Pages/services.php

$this->assign('title', 'Services');
$this->Html->css('landingPage/home', ['block' => true]);
?>

<div class="landing-page">
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <p class="section-eyebrow">Installation and repairs</p>
                <h1>Lighting Services</h1>
                <p>
                    Product selection, lighting consultation, installation planning
                    and ongoing customer support.
                </p>
                <p>
                    Professional in-home installation is available at
                    $80 per item.
                    Repair bookings can be arranged with our staff.
                </p>
                <div class="hero-buttons">
                    <?= $this->Html->link(
                        'Contact us',
                        ['controller' => 'Enquiries', 'action' => 'add'],
                        ['class' => 'home-btn home-btn-primary']
                    ) ?>
                    <?= $this->Html->link(
                        'Shop',
                        '/products',
                        ['class' => 'home-btn home-btn-secondary']
                    ) ?>
                </div>
            </div>
            <div class="hero-image">
                <?= $this->Html->image('landing/interior.jpg', [
                    'class' => 'hero-photo',
                    'alt' => 'Interior lighting consultation and installation',
                ]) ?>
            </div>
        </div>
    </section>
</div>
