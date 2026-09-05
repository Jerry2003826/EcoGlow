<?php

use App\Service\Money;

$this->assign('title', $quotation->quotation_number);
?>

<h1 class="page-title"><?= h($quotation->quotation_number) ?></h1>

<div class="panel-card">
    <p><strong>Customer:</strong> <?= h(trim(($quotation->customer->first_name ?? '') . ' ' . ($quotation->customer->last_name ?? ''))) ?></p>
    <p><strong>Email:</strong> <?= h((string)$quotation->customer->email) ?></p>
    <p><strong>Phone:</strong> <?= h((string)$quotation->customer->phone) ?></p>
    <p><strong>Status:</strong> <?= h($quotation->status) ?></p>
    <p><strong>Valid Until:</strong> <?= h((string)$quotation->valid_until) ?></p>
    <p><strong>Notes:</strong> <?= h((string)$quotation->notes) ?></p>
</div>

<div class="panel-card">
    <table class="table admin-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Line Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($quotation->quotation_items as $item): ?>
            <tr>
                <td><?= h($item->product_name) ?></td>
                <td><?= h($item->sku) ?></td>
                <td><?= h((string)$item->quantity) ?></td>
                <td><?= h(Money::formatAud((int)$item->unit_price_cents)) ?></td>
                <td><?= h(Money::formatAud((int)$item->line_total_cents)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="panel-card">
    <p><strong>Subtotal:</strong> <?= h(Money::formatAud((int)$quotation->subtotal_cents)) ?></p>
    <p><strong>GST:</strong> <?= h(Money::formatAud((int)$quotation->tax_cents)) ?></p>
    <p><strong>Total:</strong> <?= h(Money::formatAud((int)$quotation->grand_total_cents)) ?></p>

    <?= $this->Html->link('Back to Quotations', ['action' => 'index']) ?>
</div>
