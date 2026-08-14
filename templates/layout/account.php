<?php
/**
 * Eco Glow Lighting customer account layout — console composition.
 *
 * Same shell composition as the staff console (templates/layout/admin.php):
 * dark rail sidebar + sticky glass topbar + card content stage. Storefront
 * chrome (header/footer) is intentionally absent; "Back to storefront" lives
 * in the rail footer. Styles scoped to .account-app in account.css.
 *
 * @var \App\View\AppView $this
 * @var string $accountUserEmail
 * @var string $accountUserName
 * @var string $accountCurrent
 */

$accountUserEmail = $accountUserEmail ?? '';
$accountUserName = $accountUserName ?? '';
$accountCurrent = $accountCurrent ?? 'index';
$accountInitial = $accountUserEmail !== '' ? mb_strtoupper(mb_substr($accountUserEmail, 0, 1)) : 'A';
$accountDisplayName = $accountUserName !== '' ? $accountUserName : $accountUserEmail;

$accountNav = [
    'index' => [
        'label' => 'Profile',
        'url' => '/account',
        'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    ],
    'addresses' => [
        'label' => 'Addresses',
        'url' => '/account/addresses',
        'icon' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
    ],
    'orders' => [
        'label' => 'Orders',
        'url' => '/account/orders',
        'icon' => '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
    ],
    'bookings' => [
        'label' => 'Bookings',
        'url' => '/account/bookings',
        'icon' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    ],
];
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
    <?= $this->Html->css(['bootstrap.min', 'fonts', 'site', 'account']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="account-app">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="account-shell">
        <aside class="account-sidebar" id="account-sidebar">
            <a class="account-brand" href="<?= $this->Url->build('/account') ?>">
                <span class="account-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-8"/><path d="M12 14C12 9 8.5 5.5 3 5c.5 5.5 4 9 9 9Z"/><path d="M12 14c0-5 3.5-8.5 9-9-.5 5.5-4 9-9 9Z"/></svg>
                </span>
                <span class="account-brand-text">
                    Eco Glow
                    <span class="brand-sub">Account</span>
                </span>
            </a>

            <nav class="account-nav-rail" aria-label="Account">
                <p class="eg-eyebrow account-nav-group">Your account</p>
                <ul class="account-nav-list">
                    <?php foreach ($accountNav as $key => $item) : ?>
                        <?php $isCurrent = $accountCurrent === $key; ?>
                        <li>
                            <a class="account-rail-link<?= $isCurrent ? ' is-current' : '' ?>"
                               href="<?= $this->Url->build($item['url']) ?>"
                               <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                                <span class="account-rail-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $item['icon'] ?></svg>
                                </span>
                                <span class="account-rail-label"><?= h($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="account-sidebar-foot">
                <a class="account-rail-link" href="<?= $this->Url->build('/') ?>">
                    <span class="account-rail-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
                    </span>
                    <span class="account-rail-label">Back to storefront</span>
                </a>
            </div>
        </aside>

        <div class="account-stage">
            <header class="account-topbar">
                <button type="button" class="account-menu-toggle" data-account-nav-toggle
                        aria-controls="account-sidebar" aria-expanded="false" aria-label="Open navigation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <div class="account-topbar-crumb">
                    <?= $this->fetch('breadcrumb') ?>
                </div>
                <div class="account-topbar-user">
                    <span class="account-user-avatar" aria-hidden="true"><?= h($accountInitial) ?></span>
                    <span class="account-user-meta">
                        <span class="account-user-name"><?= h($accountDisplayName) ?></span>
                        <span class="account-user-email"><?= h($accountUserEmail) ?></span>
                    </span>
                    <?= $this->Form->postLink(
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v2"/><path d="M19 12H9"/><path d="m16 9 3 3-3 3"/></svg> Log out',
                        '/logout',
                        ['class' => 'account-logout', 'escape' => false],
                    ) ?>
                </div>
            </header>

            <?php $flash = $this->Flash->render(); ?>
            <?php if ($flash) : ?>
                <div class="account-flash"><?= $flash ?></div>
            <?php endif; ?>

            <main id="main-content" class="account-content account-page" tabindex="-1">
                <?= $this->fetch('content') ?>
            </main>
        </div>
    </div>

    <div class="account-nav-backdrop" data-account-nav-backdrop hidden></div>

    <?= $this->Html->script(['bootstrap.bundle.min', 'account']) ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
