<?php
/**
 * Eco Glow Lighting default layout — warm-earth storefront theme.
 *
 * The unread count comes from AppController::beforeRender, which is now the
 * only place it is counted. The layout used to repeat that query itself through
 * a FactoryLocator lookup, and the admin message list used to run it a third
 * time for its own heading.
 *
 * @var \App\View\AppView $this
 * @var int $unreadCount
 */

$identity = $this->getRequest()->getAttribute('identity');
$isStaff = $isStaff ?? false;
$isCustomer = $isCustomer ?? false;
$home = $this->Url->build('/');

/**
 * Shop now points at the real catalogue. Best Sellers stays an anchor into the
 * home page's own section: there is no products table to mark a subset as
 * best-selling, so a `/shop?featured=1` link would be a URL with nothing behind
 * it. The remaining entries are home-page sections by design.
 */
$navLinks = [
    'Shop' => $this->Url->build('/shop'),
    'Best Sellers' => $home . '#bestsellers',
    'Services' => $home . '#services',
    'About' => $home . '#about',
    'Contact' => $this->Url->build('/contact'),
];
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

    <div class="eg-announce">
        <div class="container text-center">
            Free delivery Australia-wide over $150
            <span class="eg-announce-sep" aria-hidden="true">&bull;</span>
            Licensed installation &amp; repairs
        </div>
    </div>

    <header class="eg-header sticky-top">
        <nav class="navbar navbar-expand-lg navbar-eg">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                        aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand" href="<?= $home ?>">
                    Eco Glow
                    <span class="brand-sub">Lighting</span>
                </a>

                <div class="nav-utils">
                    <button type="button" class="eg-icon-btn" disabled aria-label="Search (coming soon)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </button>
                    <?php if ($identity) : ?>
                        <?php if ($isCustomer) : ?>
                            <a class="eg-icon-btn" href="<?= $this->Url->build('/account') ?>" aria-label="Account">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </a>
                        <?php endif; ?>
                        <a class="eg-icon-btn" href="<?= $this->Url->build('/logout') ?>" aria-label="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v2"/><path d="M19 12H9"/><path d="m16 9 3 3-3 3"/></svg>
                        </a>
                    <?php else : ?>
                        <a class="eg-icon-btn" href="<?= $this->Url->build('/account/login') ?>" aria-label="Account">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </a>
                    <?php endif; ?>
                    <a class="eg-icon-btn" href="<?= $this->Url->build('/cart') ?>" aria-label="Basket">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </a>
                </div>

                <div class="collapse navbar-collapse nav-primary" id="mainNav">
                    <ul class="navbar-nav">
                        <?php foreach ($navLinks as $label => $url) : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= h($url) ?>"><?= h($label) ?></a>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($isStaff) : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $this->Url->build('/admin/contact-messages') ?>">
                                    Messages
                                    <?php if ($unreadCount > 0) : ?>
                                        <span class="badge-count"><?= $unreadCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow-1" tabindex="-1">
        <?php $flash = $this->Flash->render(); ?>
        <?php if ($flash) : ?>
            <div class="container flash-stack"><?= $flash ?></div>
        <?php endif; ?>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="footer-eg">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <span class="footer-brand">Eco Glow Lighting</span>
                    <p class="mt-2 mb-0" style="max-width: 22rem;">
                        Modern lighting fixtures, smart home illumination and licensed
                        installation, Australia-wide.
                    </p>
                </div>
                <div class="col-6 col-lg-2">
                    <h2>Shop</h2>
                    <ul>
                        <li><a href="<?= $this->Url->build('/shop') ?>">All Lighting</a></li>
                        <li><a href="<?= $home ?>#bestsellers">Best Sellers</a></li>
                        <li><a href="<?= $this->Url->build('/cart') ?>">Your Basket</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h2>Support</h2>
                    <ul>
                        <li><a href="<?= $home ?>#services">Installation &amp; Repairs</a></li>
                        <li><a href="<?= $this->Url->build('/contact') ?>">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h2>Company</h2>
                    <ul>
                        <li><a href="<?= $home ?>#about">About Us</a></li>
                        <li><a href="<?= $this->Url->build('/register') ?>">Create Account</a></li>
                        <li><a href="<?= $this->Url->build('/login') ?>">Staff Login</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-base d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <span>&copy; <?= date('Y') ?> Eco Glow Lighting. All rights reserved.</span>
                <span>Melbourne, Australia</span>
            </div>
        </div>
    </footer>

    <?= $this->Html->script(['bootstrap.bundle.min', 'glow']) ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
