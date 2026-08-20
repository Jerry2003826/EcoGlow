<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$cakeDescription = 'Eco Glow Lighting';
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake', 'navbar']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <nav class="navbar">

    <div class="navbar-brand">
        <?= $this->Html->link(
            'Eco Glow Lighting',
            '/'
        ) ?>
    </div>

    <div class="navbar-links">
        <?php 
            $identity = $this->getRequest()->getAttribute('identity'); 
        ?>
        <?= $this->Html->link('Services', '#services') ?>

        <?= $this->Html->link('Shop', '#shop') ?>

        <?= $this->Html->link('About Us', '#about') ?>

        <?= $this->Html->link('Contact Us', '/enquiries/add',
            ['class' => 'nav-button contact-button']
        ) ?>
        <?php if ($identity): ?>
            <?= $this->Html->link('Log Out', '/users/logout',
            ['class' => 'nav-button login-button']
            )?>

        <?php else: ?>
            <?= $this->Html->link('Log In', '/users/login',
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
    <footer>
    </footer>
</body>
</html>
