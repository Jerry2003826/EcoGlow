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
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>">
        Eco Glow
        <span class="brand-sub">Staff</span>
    </a>

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
                            <?= h($item['label']) ?>
                            <?= $badge ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <p class="admin-sidebar-foot">
        <a href="<?= $this->Url->build('/') ?>">View storefront</a>
    </p>
</aside>
