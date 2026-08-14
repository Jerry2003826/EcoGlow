<?php
/**
 * Eco Glow Lighting staff console layout.
 *
 * Same brand tokens as the storefront, tighter density. Do not load this
 * layout on public pages — admin.css is scoped to `.admin-app`.
 *
 * @var \App\View\AppView $this
 * @var string $adminUserEmail
 * @var array<int, string> $adminRoleNames
 * @var array<int, array<string, mixed>> $adminNav
 * @var array<string, mixed> $adminCurrent
 * @var int $unreadCount
 */

$adminUserEmail = $adminUserEmail ?? '';
$adminRoleNames = $adminRoleNames ?? [];
$adminNav = $adminNav ?? [];
$adminCurrent = $adminCurrent ?? ['controller' => '', 'action' => '', 'pass' => []];
$unreadCount = $unreadCount ?? 0;
$roleLabel = $adminRoleNames !== [] ? implode(', ', $adminRoleNames) : 'Staff';
$adminUserInitial = $adminUserEmail !== '' ? mb_strtoupper(mb_substr($adminUserEmail, 0, 1)) : 'S';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="content-language" content="en">
    <title>
        Eco Glow Lighting:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['bootstrap.min', 'fonts', 'site']) ?>
    <?= $this->Html->css('/css/admin.css?v=' . filemtime(WWW_ROOT . 'css' . DS . 'admin.css')) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="admin-app">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="admin-shell">
        <?= $this->element('admin/sidebar', compact('adminNav', 'adminCurrent', 'unreadCount')) ?>

        <div class="admin-stage">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-toggle" data-admin-nav-toggle
                        aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <div class="admin-topbar-crumb">
                    <?= $this->fetch('breadcrumb') ?>
                </div>
                <div class="admin-topbar-user">
                    <span class="admin-user-avatar" aria-hidden="true"><?= h($adminUserInitial) ?></span>
                    <span class="admin-user-meta">
                        <span class="admin-user-email"><?= h($adminUserEmail) ?></span>
                        <span class="admin-user-role"><?= h($roleLabel) ?></span>
                    </span>
                    <a class="admin-logout" href="<?= $this->Url->build('/logout') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v2"/><path d="M19 12H9"/><path d="m16 9 3 3-3 3"/></svg>
                        Log out
                    </a>
                </div>
            </header>

            <?php $flash = $this->Flash->render(); ?>
            <?php if ($flash) : ?>
                <div class="admin-flash"><?= $flash ?></div>
            <?php endif; ?>

            <main id="main-content" class="admin-content" tabindex="-1">
                <?= $this->fetch('content') ?>
            </main>
        </div>
    </div>

    <div class="admin-nav-backdrop" data-admin-nav-backdrop hidden></div>

    <?= $this->Html->script('bootstrap.bundle.min') ?>
    <?= $this->Html->script('/js/admin.js?v=' . filemtime(WWW_ROOT . 'js' . DS . 'admin.js')) ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
