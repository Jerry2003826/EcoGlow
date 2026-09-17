<?php
/** @var object $order */
/** @var string $customerName */

$installFee = 0.00;
if (!empty($order->shopping_cart) && (int)$order->shopping_cart->InstallOptIn === 1) {
    $installFee = (float)$order->shopping_cart->InstallFee;
}

$productsTotal = 0.00;
foreach ($order->order_items ?? [] as $item) {
    $productsTotal += (float)$item->UnitPrice * (int)$item->Quantity;
}

$grandTotal = (float)($order->payment->Amount ?? ($productsTotal + $installFee));
?>
<p>Hello <?= h($customerName) ?>,</p>
<p>Your Eco Glow order <strong>#<?= (int)$order->OrderId ?></strong> is confirmed.</p>
<table cellpadding="6" cellspacing="0" border="1" width="100%">
    <thead>
    <tr>
        <th align="left">Product</th>
        <th align="right">Qty</th>
        <th align="right">Unit Price</th>
        <th align="right">Line Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($order->order_items ?? [] as $item): ?>
        <tr>
            <td><?= h($item->product->ProductName ?? 'Product') ?></td>
            <td align="right"><?= (int)$item->Quantity ?></td>
            <td align="right">$<?= number_format((float)$item->UnitPrice, 2) ?></td>
            <td align="right">$<?= number_format((float)$item->UnitPrice * (int)$item->Quantity, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>Products Total: $<?= number_format($productsTotal, 2) ?></p>
<p>Installation Fee: $<?= number_format($installFee, 2) ?></p>
<p>Grand Total: <strong>$<?= number_format($grandTotal, 2) ?></strong></p>
<p>Thank you,<br>Eco Glow Lighting</p>
