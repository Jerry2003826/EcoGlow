<?php
// templates/Admin/Orders/view.php

$this->assign('title', 'Order #' . $order->OrderId);
?>

<h1 class="page-title">
    Order #<?= h((string)$order->OrderId) ?>
</h1>

<div class="panel-card">
    <p><strong>OrderId:</strong> <?= h((string)$order->OrderId) ?></p>
    <p><strong>Status:</strong> <?= h((string)$order->Status) ?></p>
    <p><strong>Order Date:</strong> <?= h((string)$order->OrderDate) ?></p>
</div>

<div class="panel-card">
    <h2>Guest</h2>

    <p><strong>Name:</strong> <?= h($order->guest->GuestName ?? '') ?></p>
    <p><strong>Email:</strong> <?= h($order->guest->Email ?? '') ?></p>
    <p><strong>Address:</strong> <?= h($order->guest->Address ?? '') ?></p>
    <p><strong>Phone:</strong> <?= h($order->guest->Phone ?? '') ?></p>
</div>

<div class="panel-card">
    <h2>Line Items</h2>

    <table class="table admin-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
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

    <?php if (
        isset($order->shopping_cart) &&
        (int)$order->shopping_cart->InstallOptIn === 1
    ): ?>
        <p>
            <strong>Installation Fee:</strong>
            $<?= number_format((float)$order->shopping_cart->InstallFee, 2) ?>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($payment)): ?>
    <div class="panel-card">
        <h2>Payment</h2>

        <p><strong>Amount:</strong> $<?= number_format((float)$payment->Amount, 2) ?></p>
        <p><strong>Method:</strong> <?= h($payment->PaymentMethod) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($receipt)): ?>
    <div class="panel-card">
        <h2>Receipt</h2>

        <p>
            <strong>Receipt Number:</strong>
            <?= h($receipt->ReceiptNumber) ?>
        </p>
    </div>
<?php endif; ?>

<?php if (!empty($delivery)): ?>
    <div class="panel-card">
        <h2>Delivery</h2>

        <p><strong>Address:</strong> <?= h($delivery->DeliveryAddress) ?></p>
        <p><strong>Status:</strong> <?= h($delivery->DeliveryStatus) ?></p>
    </div>
<?php endif; ?>

<div class="panel-card">
    <?= $this->Html->link(
        'Back to Orders',
        '/admin/orders'
    ) ?>
</div>
