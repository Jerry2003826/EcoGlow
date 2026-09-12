<?php
// templates/layout/admin.php

use Cake\Datasource\ConnectionManager;

$title = (string)$this->fetch('title');
$identity = $this->request->getAttribute('identity');

$email = (string)($identity?->get('Email') ?? '');
$role = (string)($identity?->get('UserRole') ?? '');

$lowStockCount = 0;
$OutOfStockCount = 0;

try {
    $row = ConnectionManager::get('default')
        ->execute(
            'SELECT COUNT(*) AS total
             FROM Inventory i
             INNER JOIN Product p ON p.ProductId = i.ProductId
             WHERE i.Quantity <= i.LowStockThreshold
             AND i.Quantity > 0'
        )
        ->fetch('assoc');

    $lowStockCount = (int)($row['total'] ?? 0);
} catch (\Throwable $e) {
    $lowStockCount = 0;
}
try {
    $row = ConnectionManager::get('default')
        ->execute(
            'SELECT COUNT(*) AS total
             FROM Inventory i
             INNER JOIN Product p ON p.ProductId = i.ProductId
             WHERE i.Quantity = 0'
        )
        ->fetch('assoc');

    $OutOfStockCount = (int)($row['total'] ?? 0);
} catch (\Throwable $e) {
    $OutOfStockCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> | <?= $role === 'staff' ? 'Eco Glow Staff' : 'Eco Glow Admin' ?></title>
    <?= $this->Html->css(['bootstrap.min', 'fonts', 'admin'], ['timestamp' => true]) ?>
</head>
<body class="admin-app">

<?= $this->Html->link('Skip to main content', '#main-content', ['class' => 'skip-link']) ?>

<div class="admin-shell">

    <?= $this->element('admin/sidebar', ['lowStockCount' => $lowStockCount]) ?>

    <div id="admin-page" class="admin-page">
        <header class="admin-topbar">

            <button
                id="admin-menu-toggle"
                class="admin-menu-toggle"
                type="button"
                aria-controls="admin-sidebar"
                aria-expanded="false"
                aria-label="Open navigation"
            >☰</button>
            
            <nav class="admin-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <?php if ($role === 'admin'): ?>
                        <li>
                            Admin
                        </li>
                    <?php else: ?>
                        <li>
                            Staff
                        </li>
                    <?php endif; ?>
                    
                    <li aria-current="page"><?= h($title) ?></li>
                </ol>
            </nav>

            <div class="admin-user">
                <div class="admin-user-details">
                    <div class="admin-user-email"><?= h($email) ?></div>
                </div>

                <?php if ($role === 'admin' || $role === 'staff'): ?>
                <span class="admin-low-stock">
                    Low Stock: <?= (int)$lowStockCount ?>
                </span>

                <span class="admin-out-of-stock">
                    Out of Stock: <?= (int)$OutOfStockCount ?>
                </span>
                <?php endif; ?>

                

                <?= $this->Html->link(
                    'Logout',
                    '/logout',
                    ['class' => 'btn btn-outline-secondary btn-sm admin-logout']
                ) ?>
            </div>

        </header>

        <main id="main-content" class="admin-content">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </main>
    </div>

</div>

<div id="admin-overlay" class="admin-overlay" hidden></div>

<?= $this->Html->script(['bootstrap.bundle.min', 'admin'], ['timestamp' => true]) ?>
<?= $this->fetch('script') ?>

</body>
</html>
