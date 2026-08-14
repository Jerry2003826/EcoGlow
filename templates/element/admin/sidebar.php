<?php
/**
 * Admin sidebar navigation.
 *
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $adminNav
 * @var array<string, mixed> $adminCurrent
 * @var int $unreadCount
 */

$controller = (string)($adminCurrent['controller'] ?? '');
$action = (string)($adminCurrent['action'] ?? '');
$pass = $adminCurrent['pass'] ?? [];
$currentModule = is_array($pass) && isset($pass[0]) ? (string)$pass[0] : '';

// Presentation-only icon set, keyed by controller (ComingSoon resolves by module).
$navIcons = [
    'Dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'Orders' => '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
    'Appointments' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    'Inventory' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="M12 12 4 7.5M12 12l8-4.5M12 12v9"/>',
    'Customers' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5a3.5 3.5 0 0 1 0 6.6M21.5 20a6.5 6.5 0 0 0-4.5-6.2"/>',
    'ContactMessages' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'Invoices' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    'Reports' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'Users' => '<path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6l-7-3Z"/><path d="m9.5 12 2 2 3.5-3.5"/>',
    'products' => '<path d="m20.6 13.4-7.2 7.2a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12.2V5a2 2 0 0 1 2-2h7.2a2 2 0 0 1 1.4.6l7 7a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1"/>',
    'quotations' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5M9 12h6M9 16h4"/>',
    'feature-flags' => '<path d="M5 21V4"/><path d="M5 4h12.5l-2.5 4 2.5 4H5"/>',
];
$navIconDefault = '<circle cx="12" cy="12" r="4"/>';

$navIcon = static function (array $item) use ($navIcons, $navIconDefault): string {
    $itemController = (string)($item['controller'] ?? '');
    if ($itemController === 'ComingSoon') {
        return $navIcons[(string)($item['module'] ?? '')] ?? $navIconDefault;
    }

    return $navIcons[$itemController] ?? $navIconDefault;
};
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>">
        <span class="admin-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-8"/><path d="M12 14C12 9 8.5 5.5 3 5c.5 5.5 4 9 9 9Z"/><path d="M12 14c0-5 3.5-8.5 9-9-.5 5.5-4 9-9 9Z"/></svg>
        </span>
        <span class="admin-brand-text">
            Eco Glow
            <span class="brand-sub">Staff</span>
        </span>
    </a>
    <div class="admin-sidebar-scroll" id="admin-nav-scroll" data-admin-nav-scroll>
    <nav class="admin-nav" aria-label="Staff">
        <?php foreach ($adminNav as $group) : ?>
            <p class="eg-eyebrow admin-nav-group"><?= h($group['label']) ?></p>
            <ul class="admin-nav-list">
                <?php foreach ($group['items'] as $item) : ?>
                    <?php
                    $isComing = ($item['controller'] ?? '') === 'ComingSoon';
                    $isCurrent = $isComing
                        ? ($controller === 'ComingSoon' && $currentModule === ($item['module'] ?? ''))
                        : ($controller === ($item['controller'] ?? '') && (
                            ($item['action'] ?? 'index') === 'index'
                                ? true
                                : $action === ($item['action'] ?? '')
                        ));
                    $itemClass = 'admin-nav-link' . ($isCurrent ? ' is-current' : '');
                    $badge = '';
                    if (($item['controller'] ?? '') === 'ContactMessages' && $unreadCount > 0) {
                        $badge = '<span class="badge-count">' . (int)$unreadCount . '</span>';
                    }
                    ?>
                    <li>
                        <a class="<?= h($itemClass) ?>"
                           href="<?= $this->Url->build($item['url']) ?>"
                           <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                            <span class="admin-nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $navIcon($item) ?></svg>
                            </span>
                            <span class="admin-nav-label"><?= h($item['label']) ?></span>
                            <?= $badge ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>
    </div>

    <div class="admin-sidebar-foot">
        <a class="admin-nav-link" href="<?= $this->Url->build('/') ?>">
            <span class="admin-nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
            </span>
            <span class="admin-nav-label">View storefront</span>
        </a>
    </div>
</aside>
