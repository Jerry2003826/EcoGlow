<?php

use Cake\ORM\TableRegistry;

$identity = $this->request->getAttribute('identity');

$email = '';
$roleName = 'Staff';
$unreadCount = 0;
$lowStockCount = 0;

if ($identity !== null) {
    $email = (string)($identity->get('email') ?? '');
    $userId = (int)$identity->getIdentifier();

    try {
        $userRoles = TableRegistry::getTableLocator()
            ->get('UserRoles')
            ->find()
            ->contain(['Roles'])
            ->where([
                'UserRoles.user_id' => $userId,
                'UserRoles.revoked_at IS' => null,
            ])
            ->all();

        $roleNames = [];
        foreach ($userRoles as $userRole) {
            $name = (string)($userRole->role->name ?? '');
            if ($name !== '') {
                $roleNames[] = $name;
            }
        }

        if ($roleNames !== []) {
            $roleName = implode(', ', $roleNames);
        }
    } catch (\Throwable $exception) {
        $roleName = (string)(
            $identity->get('role_name')
            ?? $identity->get('role_label')
            ?? 'Staff'
        );
    }

    try {
        $unreadRow = TableRegistry::getTableLocator()
            ->get('Users')
            ->getConnection()
            ->execute(
                'SELECT COUNT(*) AS total FROM contact_messages WHERE is_read = 0'
            )
            ->fetch('assoc');
        $unreadCount = (int)($unreadRow['total'] ?? 0);
    } catch (\Throwable $exception) {
        $unreadCount = 0;
    }

    try {
        $lowStockRow = TableRegistry::getTableLocator()
            ->get('Users')
            ->getConnection()
            ->execute(
                'SELECT COUNT(*) AS total FROM v_low_stock_items'
            )
            ->fetch('assoc');
        $lowStockCount = (int)($lowStockRow['total'] ?? 0);
    } catch (\Throwable $exception) {
        $lowStockCount = 0;
    }
}

$pageTitle = (string)($this->fetch('title') ?: 'Dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= h($pageTitle !== '' ? $pageTitle . ' · Eco Glow Admin' : 'Eco Glow Admin') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?= $this->Html->css(['bootstrap.min', 'fonts', 'admin']) ?>
</head>
<body class="admin-app">

<?= $this->Html->link(
    'Skip to main content',
    '#main-content',
    [
        'class' => 'skip-link',
    ]
) ?>

<div class="admin-shell">

    <?= $this->element('admin/sidebar', [
        'unreadCount' => $unreadCount,
        'lowStockCount' => $lowStockCount,
    ]) ?>

    <div id="admin-page" class="admin-page">

        <header class="admin-topbar">

            <button
                id="admin-menu-toggle"
                class="admin-menu-toggle"
                type="button"
                aria-controls="admin-sidebar"
                aria-expanded="false"
                aria-label="Open navigation">
                ☰
            </button>

            <div class="admin-breadcrumbs">
                <nav class="admin-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li>Admin</li>
                        <li aria-current="page"><?= h($pageTitle) ?></li>
                    </ol>
                </nav>
            </div>

            <div class="admin-user">

                <div class="admin-user-details">
                    <div class="admin-user-email">
                        <?= h($email) ?>
                    </div>

                    <div class="admin-user-role">
                        <?= h($roleName) ?>
                    </div>
                </div>

                <?= $this->Form->postLink(
                    'Logout',
                    '/logout',
                    [
                        'class' => 'btn btn-outline-secondary btn-sm admin-logout',
                    ]
                ) ?>

            </div>

        </header>

        <main id="main-content" class="admin-content">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </main>

    </div>

</div>

<div
    id="admin-overlay"
    class="admin-overlay"
    hidden
    aria-hidden="true"></div>

<?= $this->Html->script(['bootstrap.bundle.min', 'admin']) ?>

</body>
</html>
