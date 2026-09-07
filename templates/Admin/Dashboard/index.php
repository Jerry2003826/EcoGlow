<?php
// templates/Admin/Dashboard/index.php

$this->assign('title', 'Dashboard');

$orders = $widgets['orders'] ?? null;
$pendingShipment = $widgets['pendingShipment'] ?? null;
$inventory = $widgets['inventory'] ?? null;
$lowStockItems = $widgets['lowStockItems'] ?? [];
$financial = $widgets['financial'] ?? null;
$transactions = $widgets['transactions'] ?? [];
?>

<h1 class="page-title">Dashboard</h1>

<div class="dashboard-grid">
    <?php if ($orders): ?>
        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link('Orders Today', '/admin/orders') ?>
            </div>
            <div class="stat-value">
                <?= (int)$orders['orders_total'] ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($pendingShipment !== null): ?>
        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link('Pending Shipment', '/admin/orders') ?>
            </div>
            <div class="stat-value">
                <?= (int)$pendingShipment ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($inventory): ?>
        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link(
                    'Low Stock Items',
                    '/admin/coming-soon/inventory'
                ) ?>
            </div>
            <div class="stat-value">
                <?= (int)$inventory['total'] ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($financial): ?>
        <div class="stat-card">
            <div class="stat-label">Revenue</div>
            <div class="stat-value">
                $<?= number_format((float)$financial['revenue'], 2) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="content-grid">
    <?php if ($inventory): ?>
        <div class="panel-card">
            <h2>Needs Attention</h2>

            <?php if ($lowStockItems): ?>
                <ul>
                    <?php foreach ($lowStockItems as $item): ?>
                        <li>
                            <?= h($item['name']) ?>
                            (<?= (int)$item['quantity'] ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">No low stock items.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($financial): ?>
        <div class="panel-card">
            <h2>Financial Snapshot</h2>

            <p>
                Revenue:
                <strong>
                    $<?= number_format((float)$financial['revenue'], 2) ?>
                </strong>
            </p>
        </div>

        <div class="panel-card">
            <h2>Recent Payments</h2>

            <table class="table admin-table">
                <thead>
                <tr>
                    <th>PaymentId</th>
                    <th>OrderId</th>
                    <th>Amount</th>
                    <th>PaymentDate</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= (int)$transaction['PaymentId'] ?></td>
                        <td><?= (int)$transaction['OrderId'] ?></td>
                        <td>$<?= number_format((float)$transaction['Amount'], 2) ?></td>
                        <td><?= h((string)$transaction['PaymentDate']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
