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
                    '/admin/inventory'
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

    <div class="stat-card">
        <div class="stat-label">
            <?= $this->Html->link('Guest Orders Today', '/admin/orders') ?>
        </div>
        <div class="stat-value">
            <?= (int)($widgets['guestOrdersToday'] ?? 0) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">
            <?= $this->Html->link('Customer Orders Today', '/admin/orders') ?>
        </div>
        <div class="stat-value">
            <?= (int)($widgets['customerOrdersToday'] ?? 0) ?>
        </div>
    </div>
</div>

<div class="panel-card">
    <h2>Recent Orders</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Order Number</th>
                <th>Buyer type</th>
                <th>Buyer</th>
                <th>Status</th>
                <th>OrderDate</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($recentOrders ?? [] as $order): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            (string)$order->OrderId,
                            '/admin/orders/view/' . $order->OrderId,
                            ['class' => 'order-link']
                        ) ?>
                    </td>
                    <td><?= $order->CustomerId ? 'Customer' : 'Guest' ?></td>
                    <td>
                        <?= h(
                            $order->CustomerId
                                ? ($order->customer->CustomerName ?? '')
                                : ($order->guest->GuestName ?? '')
                        ) ?>
                    </td>
                    <td><?= h($order->Status) ?></td>
                    <td><?= h((string)$order->OrderDate) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-grid">
    <?php if ($inventory): ?>
        <div class="panel-card needs-attention-card">
            <h2>Needs Attention</h2>

            <ul class="attention-list">
                <?php foreach ($lowStockItems as $item): ?>
                    <li>
                        <span>
                            <?= h($item['name']) ?>
                            (<?= (int)$item['quantity'] ?>)
                        </span>
                        <?= $this->Form->create(null, [
                            'url' => '/admin/dashboard/update-stock',
                            'class' => 'dashboard-stock-form',
                        ]) ?>
                        <?= $this->Form->hidden('ProductId', [
                            'value' => (int)$item['productId'],
                        ]) ?>
                        <?= $this->Form->control('Quantity', [
                            'label' => false,
                            'type' => 'number',
                            'min' => 0,
                            'value' => (int)$item['quantity'],
                        ]) ?>
                        <?= $this->Form->button('Update') ?>
                        <?= $this->Form->end() ?>
                    </li>
                <?php endforeach; ?>
            </ul>
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

        <div class="panel-card panel-wide">
            <h2>Recent Payments</h2>

            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>Payment Number</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>PaymentDate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= (int)$transaction['PaymentId'] ?></td>
                            <td><?= h((string)$transaction['BuyerName']) ?></td>
                            <td>$<?= number_format((float)$transaction['Amount'], 2) ?></td>
                            <td><?= h((string)$transaction['PaymentMethod']) ?></td>
                            <td><?= h((string)$transaction['PaymentDate']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
