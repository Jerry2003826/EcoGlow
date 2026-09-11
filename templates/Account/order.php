<?php
// templates/Account/order.php
?>

<div class="checkout-card">
    <h1>Order #<?= h((string)$order->OrderId) ?></h1>
    <p><?= $this->Html->link('Back to my orders', '/account/orders') ?></p>

    <p><strong>Status:</strong> <?= h((string)$order->Status) ?></p>
    <p><strong>Order date:</strong> <?= h((string)$order->OrderDate) ?></p>

    <h2>Items</h2>
    <table class="receipt-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit Price</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($order->order_items ?? [] as $item): ?>
            <tr>
                <td><?= h($item->product->ProductName ?? '') ?></td>
                <td><?= (int)$item->Quantity ?></td>
                <td>$<?= number_format((float)$item->UnitPrice, 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($order->payment)): ?>
        <h2>Payment</h2>
        <p>Amount: $<?= number_format((float)$order->payment->Amount, 2) ?></p>
        <p><?= h((string)$order->payment->PaymentMethod) ?></p>
    <?php endif; ?>

    <?php if (!empty($order->delivery)): ?>
        <h2>Delivery</h2>
        <p><?= h((string)$order->delivery->DeliveryAddress) ?></p>
        <p><?= h((string)$order->delivery->DeliveryStatus) ?></p>
    <?php endif; ?>
</div>
