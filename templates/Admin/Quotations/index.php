<?php

use App\Service\Money;

$this->assign('title', 'Quotations');
?>

<h1 class="page-title">Quotations</h1>

<div class="panel-card">
    <?= $this->Html->link('Generate quotation', ['action' => 'add']) ?>
</div>

<?php if (count($quotations) === 0): ?>
    <div class="empty-state">
        No quotations available.
    </div>
<?php else: ?>
    <div class="panel-card">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Quotation #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Valid Until</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($quotations as $quotation): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $quotation->quotation_number,
                            ['action' => 'view', $quotation->id]
                        ) ?>
                    </td>
                    <td>
                        <?= h(trim(
                            ($quotation->customer->first_name ?? '') . ' ' .
                            ($quotation->customer->last_name ?? '')
                        )) ?>
                    </td>
                    <td><?= h($quotation->status) ?></td>
                    <td><?= h((string)$quotation->valid_until) ?></td>
                    <td><?= h(Money::formatAud((int)$quotation->grand_total_cents)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
