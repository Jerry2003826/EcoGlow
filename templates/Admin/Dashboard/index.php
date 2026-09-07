<?php
// templates/Admin/Dashboard/index.php

$orders = $widgets['orders'] ?? null;
$inventory = $widgets['inventory'] ?? null;
$financial = $widgets['financial'] ?? null;
$transactions = $widgets['transactions'] ?? [];
$lowStockItems = $widgets['lowStockItems'] ?? [];
$pendingShipment = $widgets['pendingShipment'] ?? null;
?>

<h1 class="page-title">Dashboard</h1>

<div class="panel-card">
    <?php if ($orders): ?>
        <h2>Orders Today</h2>
        <p><?= (int)$orders['orders_total'] ?></p>
    <?php endif; ?>

    <?php if ($pendingShipment !== null): ?>
        <h2>Pending Shipment</h2>
        <p><?= (int)$pendingShipment ?></p>
    <?php endif; ?>
</div>

<?php if ($inventory): ?>
<div class="panel-card">
    <h2>Low Inventory Alert</h2>

    <p>
        <?= (int)$inventory['total'] ?>
        low stock item(s)
    </p>

    <?php if (!empty($lowStockItems)): ?>
        <ul>
            <?php foreach ($lowStockItems as $item): ?>
                <li>
                    <?= h($item['name']) ?>
                    (<?= (int)$item['quantity'] ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?= $this->Html->link('Inventory', [
        'controller' => 'Inventory',
        'action' => 'index',
    ]) ?>
</div>
<?php endif; ?>

<?php if ($financial): ?>
<div class="panel-card">
    <h2>Financial Snapshot</h2>

    <p>
        Revenue:
        $<?= number_format((float)$financial['revenue'], 2) ?>
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
