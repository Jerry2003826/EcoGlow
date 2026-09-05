<?php

use App\Service\Money;

$this->assign('title', 'Invoices');
?>

<h1 class="page-title">Invoices</h1>

<div class="panel-card">
    <?= $this->Html->link('Issue Invoice', ['action' => 'add']) ?>
</div>

<?php if (count($invoices) === 0): ?>
<div class="empty-state">No invoices found.</div>
<?php else: ?>
<div class="panel-card">
    <table class="table admin-table">
        <thead>
        <tr>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Order</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $invoice): ?>
        <?php
            $customerName = trim(
                ($invoice->customer->first_name ?? '') . ' ' .
                ($invoice->customer->last_name ?? '')
            );
            if ($customerName === '') {
                $customerName = (string)($invoice->sales_order->guest_name ?? 'Guest');
            }
        ?>
        <tr>
            <td><?= $this->Html->link($invoice->invoice_number, ['action' => 'view', $invoice->id]) ?></td>
            <td><?= h($customerName) ?></td>
            <td><?= h($invoice->sales_order->order_number ?? '') ?></td>
            <td><?= h($invoice->status) ?></td>
            <td><?= h(Money::formatAud((int)$invoice->grand_total_cents)) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
