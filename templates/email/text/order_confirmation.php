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
Hello <?= $customerName ?>,

Your Eco Glow order #<?= (int)$order->OrderId ?> is confirmed.

Items:
<?php foreach ($order->order_items ?? [] as $item): ?>
- <?= (string)($item->product->ProductName ?? 'Product') ?>
  Qty: <?= (int)$item->Quantity ?>
  Unit Price: $<?= number_format((float)$item->UnitPrice, 2) ?>
  Line Total: $<?= number_format((float)$item->UnitPrice * (int)$item->Quantity, 2) ?>
<?php endforeach; ?>

Products Total: $<?= number_format($productsTotal, 2) ?>
Installation Fee: $<?= number_format($installFee, 2) ?>
Grand Total: $<?= number_format($grandTotal, 2) ?>

Thank you,
Eco Glow Lighting
