<?php
// templates/Admin/Dashboard/index.php

use App\Service\Money;

$orders = (int)($widgets['orders']['orders_total'] ?? 0);
$pendingShipment = (int)($widgets['pendingShipment'] ?? 0);
$lowStock = (int)($widgets['inventory']['total'] ?? 0);
$messages = (int)($widgets['messages']['total'] ?? 0);
$lowStockItems = $widgets['lowStockItems'] ?? [];
$financial = $widgets['financial'] ?? [];
$transactions = $widgets['transactions'] ?? [];
?>

<section>
    <h1 class="page-title">Dashboard</h1>

    <?php if ($lowStock > 0): ?>
        <div class="panel-card">
            <strong>Low inventory alert:</strong>
            <?= h((string)$lowStock) ?>
            product variant(s) are at or below reorder levels.
            <?= $this->Html->link('View low stock inventory', '/admin/inventory') ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link('Today\'s Orders', '/admin/orders') ?>
            </div>
            <div class="stat-value">
                <?= h((string)$orders) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link('Pending Shipment', '/admin/orders') ?>
            </div>
            <div class="stat-value">
                <?= h((string)$pendingShipment) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">
                <?= $this->Html->link('Low Stock Items', '/admin/inventory') ?>
            </div>
            <div class="stat-value">
                <?= h((string)$lowStock) ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">
                Unread Messages
            </div>
            <div class="stat-value">
                <?= h((string)$messages) ?>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="panel-card">
            <h2>Needs Attention</h2>

            <?php if (!empty($lowStockItems)): ?>
                <ul class="attention-list">
                    <?php foreach ($lowStockItems as $item): ?>
                        <li>
                            <?= h($item['name'] ?? '') ?>
                            (<?= h($item['sku'] ?? '') ?>)
                            — <?= h((string)($item['quantity_available'] ?? 0)) ?> on hand
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    No items require attention.
                </div>
            <?php endif; ?>
        </div>

        <div class="panel-card">
            <h2>Financial Snapshot</h2>

            <p>
                Gross Sales:
                <?= h(Money::formatAud((int)($financial['gross_sales_cents'] ?? 0))) ?>
            </p>

            <p>
                Tax:
                <?= h(Money::formatAud((int)($financial['tax_cents'] ?? 0))) ?>
            </p>
        </div>
        <div class="panel-card">
            <h2>Messages</h2>

            <p>
                Unread Messages:
                <?= h((string)$messages) ?>
            </p>
        </div>
    </div>

    <div class="panel-card">
        <h2>Recent Transactions</h2>

        <?php if (!empty($transactions)): ?>
            <table class="table admin-table">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Occurred</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= h($transaction['transaction_type'] ?? '') ?></td>
                        <td><?= h($transaction['reference_number'] ?? '') ?></td>
                        <td>
                            <?= h(Money::formatAud((int)($transaction['amount_cents'] ?? 0))) ?>
                        </td>
                        <td><?= h((string)($transaction['occurred_at'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                No recent transactions.
            </div>
        <?php endif; ?>
    </div>
</section>
