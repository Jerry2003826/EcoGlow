<?php
// templates/layout/default.php

$controller = (string)$this->request->getParam('controller');

$loadStorefront = in_array(
    $controller,
    ['Products', 'Cart', 'Checkout', 'Orders'],
    true
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Eco Glow Lighting</title>

    <?= $this->Html->meta(
        'icon',
        '/img/icon_lamp.svg',
        ['type' => 'image/svg+xml']
    ) ?>

    <?=
    $this->Html->css(
        $loadStorefront
            ? ['normalize.min', 'milligram.min', 'fonts', 'cake', 'navbar', 'storefront']
            : ['normalize.min', 'milligram.min', 'fonts', 'cake', 'navbar'],
        ['timestamp' => true]
    )
    ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <?= $this->Html->script('landing', ['defer' => true]) ?>
</head>
<body>

<nav class="navbar">
    <button
        class="navbar-toggle"
        type="button"
        aria-controls="primary-navigation"
        aria-expanded="false"
        aria-label="Toggle navigation"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="navbar-brand">
        <?= $this->Html->link(
            $this->Html->image('logo_leaf.png', [
                'alt' => 'Eco Glow Lighting',
                'class' => 'logo',
            ]),
            $this->Url->build('/'),
            ['escape' => false]
        ) ?>
    </div>

    <div id="primary-navigation" class="navbar-links">
        <?= $this->Html->link('Services', '/services') ?>
        <?= $this->Html->link('Shop', '/products') ?>
        <?= $this->Html->link('Cart', '/cart') ?>
        <?= $this->Html->link('About Us', '/about') ?>

        <?= $this->Html->link(
            'Contact Us',
            '/enquiries/add',
            ['class' => 'nav-button contact-button']
        ) ?>

        <?php if ($this->request->getAttribute('identity')): ?>
            <?= $this->Html->link(
                'Log Out',
                '/logout',
                ['class' => 'nav-button login-button']
            ) ?>
        <?php else: ?>
            <?= $this->Html->link(
                'Log In',
                '/login',
                ['class' => 'nav-button login-button']
            ) ?>
        <?php endif; ?>
    </div>
</nav>

<main class="main">
    <div class="container">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>
</main>

<?= $this->fetch('script') ?>

</body>
</html>
