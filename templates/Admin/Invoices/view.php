<?php
// templates/Admin/Invoices/view.php

$this->assign('title', 'Invoice #' . $invoice->InvoiceId);
$order = $invoice->order;
?>

<div class="panel-card">
    <h1>Tax invoice #<?= (int)$invoice->InvoiceId ?></h1>
    <p><strong>Order:</strong> <?= (int)$invoice->OrderId ?></p>
    <p><strong>Issued:</strong> <?= h((string)$invoice->InvoiceDate) ?></p>
</div>

<?php if (!empty($order?->CustomerId)): ?>
    <div class="panel-card">
        <h2>Customer</h2>
        <p><?= h($order->customer->CustomerName ?? '') ?></p>
        <p><?= h($order->customer->Email ?? '') ?></p>
        <p><?= h($order->customer->Address ?? '') ?></p>
    </div>
<?php else: ?>
    <div class="panel-card">
        <h2>Guest</h2>
        <p><?= h($order->guest->GuestName ?? '') ?></p>
        <p><?= h($order->guest->Email ?? '') ?></p>
        <p><?= h($order->guest->Address ?? '') ?></p>
    </div>
<?php endif; ?>

<div class="panel-card">
    <h2>Line items</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($order->order_items ?? []) as $item): ?>
                <tr>
                    <td><?= h($item->product->ProductName ?? '') ?></td>
                    <td><?= (int)$item->Quantity ?></td>
                    <td>$<?= number_format((float)$item->UnitPrice, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p>Amount excluding GST: $<?= number_format((float)$exGst, 2) ?></p>
    <p>GST (10% inclusive): $<?= number_format((float)$gst, 2) ?></p>
    <p><strong>Total: $<?= number_format((float)$invoice->TotalAmount, 2) ?></strong></p>
    <?= $this->Html->link('Back to Invoices', '/admin/invoices', ['class' => 'admin-back-link']) ?>
</div>
