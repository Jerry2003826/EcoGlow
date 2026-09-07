<?php
// templates/Admin/Quotations/index.php

$this->assign('title', 'Quotations');
?>

<h1 class="page-title">Quotations</h1>

<div class="panel-card">
    <p>
        <?= $this->Html->link('Create quotation', '/admin/quotations/add') ?>
    </p>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Quotation</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Total</th>
                <th>Emailed</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($quotations) === 0): ?>
                <tr>
                    <td colspan="5">No quotations yet.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($quotations as $quotation): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            'QT-' . $quotation->QuotationId,
                            ['action' => 'view', $quotation->QuotationId]
                        ) ?>
                    </td>
                    <td><?= h($quotation->customer->CustomerName ?? '') ?></td>
                    <td><?= h($quotation->customer->Email ?? '') ?></td>
                    <td>$<?= number_format((float)$quotation->TotalAmount, 2) ?></td>
                    <td><?= h((string)($quotation->EmailedAt ?? 'not sent')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
