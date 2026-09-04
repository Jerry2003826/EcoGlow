<?php

use App\Service\Money;

$this->assign('title', 'Dashboard');

$orders = (int)($widgets['orders']['orders_total'] ?? 0);
$lowStock = (int)($widgets['inventory']['total'] ?? 0);
$messages = (int)($widgets['messages']['total'] ?? 0);

$transactions = $widgets['transactions'] ?? [];
$lowStockItems = $widgets['lowStockItems'] ?? [];

$financial = $widgets['financial'] ?? [];

$pendingShipment = (int)($widgets['pendingShipment'] ?? 0);
?>

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
            <?= $this->Html->link("Today's Orders", '/admin/orders') ?>
        </div>
        <div class="stat-value"><?= $orders ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">
            <?= $this->Html->link('Pending Shipment', '/admin/orders') ?>
        </div>
        <div class="stat-value"><?= $pendingShipment ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">
            <?= $this->Html->link('Low Stock Items', '/admin/inventory') ?>
        </div>
        <div class="stat-value"><?= $lowStock ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Unread Messages</div>
        <div class="stat-value"><?= $messages ?></div>
    </div>
</div>

<div class="content-grid">
    <section class="panel-card">
        <h2>Needs Attention</h2>

        <?php if ($lowStockItems !== []): ?>
            <ul class="attention-list">
                <?php foreach ($lowStockItems as $item): ?>
                    <li>
                        <?= h($item['name']) ?>
                        (<?= h($item['sku']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                Low stock products will appear here when stock falls below reorder levels.
            </div>
        <?php endif; ?>
    </section>

    <section class="panel-card">
        <h2>Financial Snapshot</h2>

        <?php if ($financial !== []): ?>
            <ul class="attention-list">
                <li>
                    Net Sales:
                    <?= h(Money::formatAud((int)($financial['gross_sales_cents'] ?? 0))) ?>
                </li>
                <li>
                    GST:
                    <?= h(Money::formatAud((int)($financial['tax_cents'] ?? 0))) ?>
                </li>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                Financial metrics will appear here once order, payment and refund activity exists.
            </div>
        <?php endif; ?>
    </section>

    <section class="panel-card">
        <h2>Messages</h2>

        <?php if ($messages > 0): ?>
            <ul class="attention-list">
                <li>
                    <?= $messages ?>
                    unread customer messages require review.
                </li>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                Customer enquiries requiring staff attention will appear here.
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel-card mt-4">
    <h2>Recent Transactions</h2>

    <?php if ($transactions !== []): ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Occurred At</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= h((string)($transaction['transaction_type'] ?? '')) ?></td>
                        <td><?= h((string)($transaction['reference_number'] ?? '')) ?></td>
                        <td><?= h(Money::formatAud((int)($transaction['amount_cents'] ?? 0))) ?></td>
                        <td><?= h((string)($transaction['occurred_at'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            Recent orders, payments and refunds will appear here once business activity begins.
        </div>
    <?php endif; ?>
</section>
