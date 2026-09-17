<?php
// templates/Account/order.php
$this->assign('title', 'Order #' . $order->OrderId);
?>

<div class="account-page">
    <header class="account-heading">
        <?= $this->Html->link('Back to my orders', '/account/orders', ['class' => 'account-back-link']) ?>
        <h1>Order #<?= h((string)$order->OrderId) ?></h1>
        <div class="account-order-meta">
            <span class="account-status"><?= h(ucfirst((string)$order->Status)) ?></span>
            <span>Placed <?= h((string)$order->OrderDate) ?></span>
        </div>
    </header>

    <section class="account-card">
    <h2>Items</h2>
    <div class="account-table-wrap" tabindex="0" role="region" aria-label="Order items, scroll horizontally if needed">
    <table class="account-table">
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
    </div>
    </section>

    <div class="account-summary-grid">
    <?php if (!empty($order->payment)): ?>
        <section class="account-card">
        <h2>Payment</h2>
        <p class="account-amount">$<?= number_format((float)$order->payment->Amount, 2) ?></p>
        <p class="account-muted"><?= h(ucwords(str_replace('-', ' ', (string)$order->payment->PaymentMethod))) ?></p>
        </section>
    <?php endif; ?>

    <?php if (!empty($order->delivery)): ?>
        <section class="account-card">
        <h2>Delivery</h2>
        <p><?= h((string)$order->delivery->DeliveryAddress) ?></p>
        <p class="account-muted"><?= h(ucfirst((string)$order->delivery->DeliveryStatus)) ?></p>
        </section>
    <?php endif; ?>
    </div>
</div>
