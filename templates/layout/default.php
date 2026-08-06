<?php
/**
 * Eco Glow Lighting default layout.
 *
 * @var \App\View\AppView $this
 * @var int $navUnreadCount Unread contact-message count, set in AppController::beforeRender().
 */

$identity = $this->getRequest()->getAttribute('identity');
$unreadCount = $navUnreadCount ?? 0;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="<?= $this->Url->build('/') ?>">
                <span class="text-warning">Eco&nbsp;Glow</span> Lighting
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
                                    <span class="badge text-bg-warning"><?= $unreadCount ?></span>
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

    <main class="flex-grow-1 py-4">
        <div class="container">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </div>
    </main>

    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> Eco Glow Lighting. All rights reserved.</span>
            <span class="text-secondary small">Modern lighting &amp; smart home illumination, Australia-wide.</span>
        </div>
    </footer>

    <?= $this->Html->script(['bootstrap.bundle.min']) ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
