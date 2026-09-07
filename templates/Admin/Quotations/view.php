<?php
// templates/Admin/Quotations/view.php

$this->assign('title', 'QT-' . $quotation->QuotationId);
?>

<div class="panel-card">
    <h1>Quotation QT-<?= (int)$quotation->QuotationId ?></h1>
    <p><strong>Customer:</strong> <?= h($quotation->customer->CustomerName ?? '') ?></p>
    <p><strong>Email:</strong> <?= h($quotation->customer->Email ?? '') ?></p>
    <p><strong>Phone:</strong> <?= h($quotation->customer->Phone ?? '') ?></p>
    <p><strong>Address:</strong> <?= h($quotation->customer->Address ?? '') ?></p>
    <p><strong>Date:</strong> <?= h((string)$quotation->QuotationDate) ?></p>
    <p><strong>Status:</strong> <?= h((string)($quotation->Status ?? 'issued')) ?></p>
    <p><strong>Total (GST incl.):</strong> $<?= number_format((float)$quotation->TotalAmount, 2) ?></p>
    <p><strong>Emailed:</strong> <?= h((string)($quotation->EmailedAt ?? 'not sent')) ?></p>
    <?php if (!empty($quotation->Notes)): ?>
        <p><?= nl2br(h((string)$quotation->Notes)) ?></p>
    <?php endif; ?>
    <?= $this->Html->link('Back to Quotations', '/admin/quotations', ['class' => 'admin-back-link']) ?>
</div>
