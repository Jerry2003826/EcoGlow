<?php
/**
 * Invoice detail / print view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Invoice $invoice
 * @var \Cake\I18n\Date $today
 * @var iterable<\App\Model\Entity\Payment> $payments
 */

use App\Model\Entity\Invoice;

$business = is_array($invoice->business_snapshot) ? $invoice->business_snapshot : [];
$customerSnap = is_array($invoice->customer_snapshot) ? $invoice->customer_snapshot : [];
$this->assign('title', $invoice->invoice_number);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Invoices', 'url' => ['action' => 'index']],
        ['label' => $invoice->invoice_number],
    ],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Invoice</span>
        <h1><?= h($invoice->invoice_number) ?></h1>
    </div>
    <div class="admin-actions">
        <button type="button" class="btn btn-eg-ghost" onclick="window.print()">Print / save as PDF</button>
        <?= $this->Form->postButton(
            'Queue email',
            ['action' => 'send', $invoice->id],
            ['class' => 'btn btn-eg-ghost'],
        ) ?>
        <?= $this->element('admin/status_pill', [
            'status' => $invoice->isOverdue($today) ? Invoice::STATUS_OVERDUE : $invoice->status,
            'label' => $invoice->isOverdue($today)
                ? 'Overdue'
                : (Invoice::statusLabels()[$invoice->status] ?? $invoice->status),
            'toneOverride' => Invoice::statusTone($invoice->isOverdue($today) ? Invoice::STATUS_OVERDUE : $invoice->status),
        ]) ?>
    </div>
</div>

<div class="admin-panel admin-invoice">
    <div class="admin-invoice-meta">
        <div>
            <h2>From</h2>
            <p class="mb-0">
                <strong><?= h($business['trading_name'] ?? $business['name'] ?? 'Eco Glow Lighting') ?></strong><br>
                <?php if (!empty($business['legal_name'])) : ?>
                    <?= h($business['legal_name']) ?><br>
                <?php endif; ?>
                <?php if (!empty($business['abn'])) : ?>
                    ABN <?= h($business['abn']) ?><br>
                <?php endif; ?>
                <?= h($business['email'] ?? '') ?>
                <?= !empty($business['phone']) ? '<br>' . h($business['phone']) : '' ?>
            </p>
        </div>
        <div>
            <h2>Bill to</h2>
            <p class="mb-0">
                <strong><?= h($customerSnap['name'] ?? $invoice->customer->label ?? 'Customer') ?></strong><br>
                <?= h($customerSnap['email'] ?? '') ?>
                <?= !empty($customerSnap['phone']) ? '<br>' . h($customerSnap['phone']) : '' ?>
                <?= !empty($customerSnap['company']) ? '<br>' . h($customerSnap['company']) : '' ?>
            </p>
        </div>
    </div>
    <dl class="eg-kv-list mb-4">
        <div class="eg-kv-row">
            <dt>Issue date</dt>
            <dd><?= $invoice->issue_date ? h($invoice->issue_date->format('d M Y')) : '—' ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>Due date</dt>
            <dd><?= $invoice->due_date ? h($invoice->due_date->format('d M Y')) : '—' ?></dd>
        </div>
        <?php if ($invoice->sales_order) : ?>
            <div class="eg-kv-row">
                <dt>Order</dt>
                <dd class="cell-id"><?= $this->Html->link($invoice->sales_order->order_number, ['controller' => 'Orders', 'action' => 'view', $invoice->sales_order_id]) ?></dd>
            </div>
        <?php endif; ?>
    </dl>

    <div class="table-responsive">
        <table class="table table-eg align-middle" aria-label="Invoice lines">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Line total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice->invoice_items as $item) : ?>
                    <tr>
                        <td>
                            <?= h($item->item_name_snapshot) ?>
                            <?php if ($item->description_snapshot) : ?>
                                <div class="small text-muted"><?= h($item->description_snapshot) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="cell-sku"><?= h($item->sku_snapshot ?: '—') ?></td>
                        <td><?= (int)$item->quantity ?></td>
                        <td><?= $this->Money->aud((int)$item->unit_price_cents) ?></td>
                        <td><?= $this->Money->aud((int)$item->line_total_cents) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <dl class="eg-kv-list mt-3">
        <div class="eg-kv-row">
            <dt>Subtotal</dt>
            <dd><?= $this->Money->aud((int)$invoice->subtotal_cents) ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>Discount</dt>
            <dd><?= $this->Money->aud((int)$invoice->discount_cents) ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>GST included</dt>
            <dd><?= $this->Money->aud((int)$invoice->tax_cents) ?></dd>
        </div>
        <div class="eg-kv-row is-total">
            <dt>Total (GST inclusive)</dt>
            <dd><?= $this->Money->aud((int)$invoice->grand_total_cents) ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>Amount paid</dt>
            <dd><?= $this->Money->aud((int)$invoice->amount_paid_cents) ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>Balance due</dt>
            <dd><?= $this->Money->aud((int)$invoice->balance_due_cents) ?></dd>
        </div>
    </dl>
</div>

<section class="admin-section" aria-labelledby="payments-heading">
    <h2 id="payments-heading">Payments</h2>
    <div class="admin-panel">
        <?php if (count($payments) === 0) : ?>
            <?= $this->element('admin/empty', [
                'title' => 'No payments recorded',
                'body' => 'Manual payments against this invoice are listed here. Amounts are stored as integer cents.',
            ]) ?>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-eg align-middle" aria-label="Payments">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment) : ?>
                            <tr>
                                <td class="cell-ref"><?= h($payment->transaction_reference ?: $payment->provider_payment_id) ?></td>
                                <td><?= $this->Money->aud((int)$payment->amount_cents) ?></td>
                                <td><?= h($payment->status) ?></td>
                                <td class="text-nowrap"><?= h($payment->created?->setTimezone('Australia/Melbourne')->format('d M Y, H:i')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($invoice->status !== Invoice::STATUS_VOID && (int)$invoice->balance_due_cents > 0) : ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'recordPayment', $invoice->id], 'class' => 'mt-3']) ?>
            <div class="admin-filter-row">
                <div class="admin-field">
                    <label for="amount">Record payment (AUD)</label>
                    <input type="text" inputmode="decimal" class="form-control" id="amount" name="amount" required placeholder="0.00">
                </div>
                <?= $this->Form->button('Record payment', ['class' => 'btn btn-eg-ghost']) ?>
            </div>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </div>
</section>
