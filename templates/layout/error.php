<?php
/**
 * Layout for the 4xx/5xx error templates — warm-earth storefront theme.
 *
 * This used to load CakePHP's stock normalize/milligram/cake stylesheets, so
 * an error page looked like a framework default rather than like the site. It
 * now loads the same bootstrap/fonts/site trio as the main layout, and those
 * three skeleton files have since been deleted — this was their last caller.
 *
 * The header is deliberately a bare wordmark rather than the full navigation:
 * this layout has to render when something has already gone wrong, so it
 * depends on no controller-supplied view variables at all.
 *
 * @var \App\View\AppView $this
 */
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

    <header class="eg-header">
        <nav class="navbar navbar-eg">
            <div class="container justify-content-center">
                <a class="navbar-brand" href="<?= $this->Url->build('/') ?>">
                    Eco Glow
                    <span class="brand-sub">Lighting</span>
                </a>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow-1" tabindex="-1">
        <div class="container">
            <div class="error-shell error-container">
                <div>
                    <?= $this->Flash->render() ?>
                    <?= $this->fetch('content') ?>
                    <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center">
                        <?= $this->Html->link(__('Back'), 'javascript:history.back()', ['class' => 'btn btn-eg-ghost']) ?>
                        <?= $this->Html->link(__('Go to homepage'), '/', ['class' => 'btn btn-eg-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-eg">
        <div class="container">
            <div class="footer-base border-0 mt-0 pt-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <span>&copy; <?= date('Y') ?> Eco Glow Lighting. All rights reserved.</span>
                <span>Melbourne, Australia</span>
            </div>
        </div>
    </footer>
</body>
</html>
