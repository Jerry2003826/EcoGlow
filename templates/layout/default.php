<?php
/**
 * Eco Glow Lighting default layout — night-glow brand theme.
 *
 * @var \App\View\AppView $this
 */

use Cake\Datasource\FactoryLocator;

$identity = $this->getRequest()->getAttribute('identity');

$unreadCount = 0;
if ($identity) {
    $unreadCount = FactoryLocator::get('Table')
        ->get('ContactMessages')
        ->find()
        ->where(['is_read' => false])
        ->count();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        Eco Glow Lighting:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['bootstrap.min', 'fonts', 'site']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <div id="glow-spot" aria-hidden="true"></div>
    <div id="power-on" aria-hidden="true"></div>

    <nav class="navbar navbar-expand-lg navbar-eg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= $this->Url->build('/') ?>">
                <svg class="brand-bulb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 18h6"/>
                    <path d="M10 21h4"/>
                    <path d="M12 3a6 6 0 0 0-3.5 10.9c.8.6 1.5 1.6 1.5 2.6V17h4v-.5c0-1 .7-2 1.5-2.6A6 6 0 0 0 12 3z"/>
                    <path d="M12 7v2"/>
                </svg>
                <span class="brand-word">Eco&nbsp;Glow</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build('/contact') ?>">Contact</a>
                    </li>
                    <?php if ($identity) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $this->Url->build('/admin/contact-messages') ?>">
                                Messages
                                <?php if ($unreadCount > 0) : ?>
                                    <span class="badge-glow"><?= $unreadCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $this->Url->build('/logout') ?>">Logout</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $this->Url->build('/login') ?>">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main id="main-content" class="flex-grow-1" tabindex="-1" style="padding-top: 4.5rem;">
        <?php $flash = $this->Flash->render(); ?>
        <?php if ($flash) : ?>
            <div class="container flash-stack"><?= $flash ?></div>
        <?php endif; ?>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="footer-eg">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> Eco Glow Lighting. All rights reserved.</span>
            <span class="small" style="color: var(--eg-text-dim);">Modern lighting &amp; smart home illumination, Australia-wide.</span>
        </div>
    </footer>

    <?= $this->Html->script(['bootstrap.bundle.min', 'glow']) ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
