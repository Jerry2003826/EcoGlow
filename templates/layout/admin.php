<?php
// templates/layout/admin.php

use Cake\Datasource\ConnectionManager;

$identity = $this->request->getAttribute('identity');

$email = '';
$role = '';

if ($identity) {
    $email = (string)(
        $identity->get('Email')
        ?? ($identity->getOriginalData()->Email ?? '')
    );

    $role = (string)(
        $identity->get('UserRole')
        ?? ($identity->getOriginalData()->UserRole ?? '')
    );
}

$lowStockCount = 0;

try {
    $connection = ConnectionManager::get('default');

    $row = $connection->execute(
        '
        SELECT COUNT(*) AS total
        FROM Inventory i
        INNER JOIN Product p
            ON p.ProductId = i.ProductId
        WHERE i.Quantity <= i.LowStockThreshold
        '
    )->fetch('assoc');

    $lowStockCount = (int)($row['total'] ?? 0);
} catch (\Throwable $exception) {
    $lowStockCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= h($this->fetch('title')) ?> | Eco Glow Admin</title>

    <?= $this->Html->css('bootstrap.min') ?>
    <?= $this->Html->css('admin') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar-wrap">
        <?= $this->element('admin/sidebar') ?>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <strong>Eco Glow Admin</strong>
            </div>

            <div class="admin-topbar-right">
                <span><?= h($email) ?></span>
                <span><?= h($role) ?></span>
                <span>Low Stock: <?= h((string)$lowStockCount) ?></span>

                <?= $this->Html->link(
                    'Logout',
                    '/logout',
                    ['class' => 'btn btn-outline-secondary']
                ) ?>
            </div>
        </header>

        <section class="admin-content">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </section>
    </main>
</div>
</body>
</html>
