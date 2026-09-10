<?php
// templates/Admin/Quotations/view.php

$this->assign('title', 'QT-' . $quotation->QuotationId);
?>

<h1 class="page-title">Quotation QT-<?= (int)$quotation->QuotationId ?></h1>

<div class="panel-card">
    <h2>Customer</h2>
    <dl class="admin-meta">
        <div>
            <dt>Name</dt>
            <dd><?= h($quotation->customer->CustomerName ?? '') ?></dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd><?= h($quotation->customer->Email ?? '') ?></dd>
        </div>
        <div>
            <dt>Phone</dt>
            <dd><?= h($quotation->customer->Phone ?? '—') ?></dd>
        </div>
        <div>
            <dt>Address</dt>
            <dd><?= h($quotation->customer->Address ?? '—') ?></dd>
        </div>
    </dl>
</div>

<div class="panel-card">
    <h2>Quote details</h2>
    <dl class="admin-meta">
        <div>
            <dt>Date</dt>
            <dd><?= h((string)$quotation->QuotationDate) ?></dd>
        </div>
        <div>
            <dt>Status</dt>
            <dd><?= h((string)($quotation->Status ?? 'issued')) ?></dd>
        </div>
        <div>
            <dt>Total (GST incl.)</dt>
            <dd>$<?= number_format((float)$quotation->TotalAmount, 2) ?></dd>
        </div>
        <div>
            <dt>Emailed</dt>
            <dd><?= h((string)($quotation->EmailedAt ?? 'not sent')) ?></dd>
        </div>
    </dl>
</div>

<div class="panel-card">
    <h2>Line items and notes</h2>
    <?php if (!empty($quotation->Notes)): ?>
        <p class="quotation-notes"><?= nl2br(h((string)$quotation->Notes)) ?></p>
    <?php else: ?>
        <p class="page-lead">No notes on this quotation.</p>
    <?php endif; ?>
    <?= $this->Html->link('Back to Quotations', '/admin/quotations', ['class' => 'admin-back-link']) ?>
</div>
