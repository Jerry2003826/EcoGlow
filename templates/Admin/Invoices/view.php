<?php

use App\Service\Money;

$this->assign('title', $invoice->invoice_number);

$customerName = trim(
    ($invoice->customer->first_name ?? '') . ' ' .
    ($invoice->customer->last_name ?? '')
);
if ($customerName === '') {
    $customerName = (string)($invoice->sales_order->guest_name ?? 'Guest');
}
?>

<h1 class="page-title"><?= h($invoice->invoice_number) ?></h1>

<div class="panel-card">
    <p><strong>Order:</strong> <?= h($invoice->sales_order->order_number ?? '') ?></p>
    <p><strong>Customer:</strong> <?= h($customerName) ?></p>
    <p><strong>Category:</strong> <?= h($invoice->source_channel) ?></p>
    <p><strong>Discount:</strong> <?= h(Money::formatAud((int)$invoice->discount_cents)) ?></p>
    <p><strong>GST:</strong> <?= h(Money::formatAud((int)$invoice->tax_cents)) ?></p>
    <p><strong>Total:</strong> <?= h(Money::formatAud((int)$invoice->grand_total_cents)) ?></p>

    <?= $this->Html->link('Back to Invoices', ['action' => 'index']) ?>
</div>
