<?php
// templates/Admin/Orders/view.php

$this->assign('title', 'Order #' . $order->OrderId);
?>

<div class="panel-card">
    <h1>Order #<?= h((string)$order->OrderId) ?></h1>

    <p><strong>Status:</strong> <?= h($order->Status) ?></p>
    <p><strong>Order Date:</strong> <?= h((string)$order->OrderDate) ?></p>
</div>

<div class="panel-card">
    <h2>Guest</h2>

    <p><?= h($order->guest->GuestName ?? '') ?></p>
    <p><?= h($order->guest->Email ?? '') ?></p>
    <p><?= h($order->guest->Address ?? '') ?></p>
    <p><?= h($order->guest->Phone ?? '') ?></p>
</div>

<div class="panel-card">
    <h2>Line Items</h2>

    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>UnitPrice</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($order->order_items as $item): ?>
                <tr>
                    <td><?= h($item->product->ProductName ?? '') ?></td>
                    <td><?= (int)$item->Quantity ?></td>
                    <td>$<?= number_format((float)$item->UnitPrice, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (
        !empty($order->shopping_cart) &&
        (int)$order->shopping_cart->InstallOptIn === 1
    ): ?>
        <p>
            <strong>InstallFee:</strong>
            $<?= number_format((float)$order->shopping_cart->InstallFee, 2) ?>
        </p>
    <?php endif; ?>
</div>

<div class="panel-card">
    <h2>Payment</h2>

    <?php if (!empty($payment)): ?>
        <p>Amount: $<?= number_format((float)$payment->Amount, 2) ?></p>
        <p><?= h($payment->PaymentMethod) ?></p>
    <?php endif; ?>
</div>

<div class="panel-card">
    <h2>Receipt</h2>

    <?php if (!empty($receipt)): ?>
        <p><?= h($receipt->ReceiptNumber) ?></p>
    <?php endif; ?>
</div>

<div class="panel-card">
    <h2>Delivery</h2>

    <?php if (!empty($delivery)): ?>
        <p><?= h($delivery->DeliveryAddress) ?></p>
        <p><?= h($delivery->DeliveryStatus) ?></p>
    <?php endif; ?>
</div>

<div class="panel-card">
    <?= $this->Html->link(
        'Back to Orders',
        '/admin/orders',
        ['class' => 'admin-back-link']
    ) ?>
</div>
