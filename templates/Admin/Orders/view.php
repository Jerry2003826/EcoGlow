<?php
// templates/Admin/Orders/view.php

$this->assign('title', 'Order #' . $order->OrderId);
?>

<div class="panel-card">
    <h1>Order #<?= h((string)$order->OrderId) ?></h1>
    <p><strong>Status:</strong> <?= h($order->Status) ?></p>
    <p><strong>Order Date:</strong> <?= h((string)$order->OrderDate) ?></p>
    <?php if (!empty($order->shopping_cart) && (int)$order->shopping_cart->InstallOptIn === 1): ?>
        <p><strong>Type:</strong> Product + installation ($<?= number_format((float)$order->shopping_cart->InstallFee, 2) ?>)</p>
    <?php else: ?>
        <p><strong>Type:</strong> Product</p>
    <?php endif; ?>
</div>

<?php if ($order->CustomerId): ?>
    <div class="panel-card">
        <h2>Customer</h2>
        <p><?= h($order->customer->CustomerName ?? '') ?></p>
        <p><?= h($order->customer->Email ?? '') ?></p>
        <p><?= h($order->customer->Address ?? '') ?></p>
        <p><?= h($order->customer->Phone ?? '') ?></p>
    </div>
<?php else: ?>
    <div class="panel-card">
        <h2>Guest</h2>
        <p><?= h($order->guest->GuestName ?? '') ?></p>
        <p><?= h($order->guest->Email ?? '') ?></p>
        <p><?= h($order->guest->Address ?? '') ?></p>
        <p><?= h($order->guest->Phone ?? '') ?></p>
    </div>
<?php endif; ?>

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
    <h2>Delivery and booking time</h2>
    <?php if (!empty($delivery)): ?>
        <?= $this->Form->create(null, [
            'url' => ['action' => 'updateDelivery', $order->OrderId],
        ]) ?>
        <?= $this->Form->control('DeliveryAddress', [
            'label' => 'Delivery address',
            'value' => $delivery->DeliveryAddress,
        ]) ?>
        <?= $this->Form->control('DeliveryStatus', [
            'label' => 'Delivery status',
            'value' => $delivery->DeliveryStatus,
            'options' => [
                'scheduled' => 'scheduled',
                'in_transit' => 'in_transit',
                'delivered' => 'delivered',
            ],
        ]) ?>
        <?= $this->Form->control('DeliveryDate', [
            'label' => 'Delivery / booking date',
            'type' => 'datetime-local',
            'value' => $delivery->DeliveryDate
                ? date('Y-m-d\TH:i', strtotime((string)$delivery->DeliveryDate))
                : '',
        ]) ?>
        <?= $this->Form->button('Update delivery') ?>
        <?= $this->Form->end() ?>
    <?php else: ?>
        <p>No delivery record.</p>
    <?php endif; ?>
</div>

<?php if (!empty($services) && count($services) > 0): ?>
    <div class="panel-card">
        <h2>Linked service bookings</h2>
        <?php foreach ($services as $service): ?>
            <p>
                <?= h($service->RequestType) ?>
                at
                <?= h((string)$service->RequestedDate) ?>
                —
                <?= h($service->Description) ?>
            </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel-card">
    <?= $this->Html->link(
        'Back to Orders',
        '/admin/orders',
        ['class' => 'admin-back-link']
    ) ?>
</div>
