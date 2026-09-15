<?php
// templates/Admin/Invoices/index.php

$this->assign('title', 'Invoices');
?>

<h1 class="page-title">Invoices</h1>

<div class="panel-card">
    <h2>Issued invoices</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>InvoiceId</th>
                <th>OrderId</th>
                <th>Buyer type</th>
                <th>Buyer</th>
                <th>Total (GST incl.)</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($invoices) === 0): ?>
                <tr>
                    <td colspan="6">No invoices yet. Generate one from a paid order below.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($invoices as $invoice): ?>
                <?php $order = $invoice->order; ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            (string)$invoice->InvoiceId,
                            ['action' => 'view', $invoice->InvoiceId]
                        ) ?>
                    </td>
                    <td><?= (int)$invoice->OrderId ?></td>
                    <td><?= !empty($order?->CustomerId) ? 'Customer' : 'Guest' ?></td>
                    <td>
                        <?= h(
                            !empty($order?->CustomerId)
                                ? ($order->customer->CustomerName ?? '')
                                : ($order->guest->GuestName ?? '')
                        ) ?>
                    </td>
                    <td>$<?= number_format((float)$invoice->TotalAmount, 2) ?></td>
                    <td>
                        <?= h(
                            $invoice->InvoiceDate
                                ? date('d/m/Y g:ia', strtotime((string)$invoice->InvoiceDate))
                                : ''
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card">
    <h2>Generate from order</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>OrderId</th>
                <th>Buyer type</th>
                <th>Buyer</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int)$order->OrderId ?></td>
                    <td><?= !empty($order->CustomerId) ? 'Customer' : 'Guest' ?></td>
                    <td>
                        <?= h(
                            !empty($order->CustomerId)
                                ? ($order->customer->CustomerName ?? '')
                                : ($order->guest->GuestName ?? '')
                        ) ?>
                    </td>
                    <td><?= h($order->Status) ?></td>
                    <td>
                        <?= $this->Form->postLink(
                            'Generate tax invoice',
                            ['action' => 'generate', $order->OrderId]
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
