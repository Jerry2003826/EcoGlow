<?php
/**
 * Order detail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SalesOrder $salesOrder
 * @var \Cake\I18n\Date $today
 * @var array<int, string> $nextStatuses
 */

use App\Model\Entity\SalesOrder;

$channels = SalesOrder::channelLabels();
$labels = SalesOrder::statusLabels();
$this->assign('title', $salesOrder->order_number);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Orders', 'url' => ['action' => 'index']],
        ['label' => $salesOrder->order_number],
    ],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Order</span>
        <h1><?= h($salesOrder->order_number) ?></h1>
    </div>
    <?= $this->element('admin/status_pill', ['status' => $salesOrder->status]) ?>
</div>

<?php if (!empty($salesOrder->metadata['stock_warnings'])) : ?>
    <p class="eg-note" role="status">
        Recorded with short stock:
        <?= h(implode('; ', $salesOrder->metadata['stock_warnings'])) ?>
    </p>
<?php endif; ?>

<div class="admin-detail">
    <div>
        <section class="admin-section" aria-labelledby="lines-heading">
            <h2 id="lines-heading">Line items</h2>
            <div class="admin-panel">
                <div class="table-responsive">
                    <table class="table table-eg align-middle" aria-label="Order lines">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Unit price</th>
                                <th>Qty</th>
                                <th>Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesOrder->sales_order_items as $item) : ?>
                                <tr>
                                    <td>
                                        <?= h($item->item_name_snapshot) ?>
                                        <?php if ($item->variant_name_snapshot) : ?>
                                            <div class="small text-muted"><?= h($item->variant_name_snapshot) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item->metadata['stock_shortfall'])) : ?>
                                            <div class="admin-stock-warn" role="status">
                                                Short <?= (int)$item->metadata['stock_shortfall'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($item->sku_snapshot ?: '—') ?></td>
                                    <td><?= $this->Money->aud((int)$item->unit_price_cents) ?></td>
                                    <td><?= (int)$item->quantity ?></td>
                                    <td><?= $this->Money->aud((int)$item->line_total_cents) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <dl class="eg-kv-list mt-3">
                    <div class="eg-kv-row">
                        <dt>Subtotal</dt>
                        <dd><?= $this->Money->aud((int)$salesOrder->subtotal_cents) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Shipping</dt>
                        <dd><?= $this->Money->aud((int)$salesOrder->shipping_cents) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>GST included</dt>
                        <dd><?= $this->Money->aud((int)$salesOrder->tax_cents) ?></dd>
                    </div>
                    <div class="eg-kv-row is-total">
                        <dt>Total</dt>
                        <dd><?= $this->Money->aud((int)$salesOrder->grand_total_cents) ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="notes-heading">
            <h2 id="notes-heading">Notes</h2>
            <div class="admin-panel">
                <?php if (!$salesOrder->order_notes) : ?>
                    <p class="admin-empty">No notes yet.</p>
                <?php else : ?>
                    <ul class="admin-need-list">
                        <?php foreach ($salesOrder->order_notes as $note) : ?>
                            <li>
                                <div>
                                    <div><?= nl2br(h($note->body)) ?></div>
                                    <div class="small text-muted"><?= h($note->created->format('d M Y, H:i')) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?= $this->Form->create(null, ['url' => ['action' => 'addNote', $salesOrder->id], 'class' => 'mt-3']) ?>
                <div class="admin-field">
                    <?= $this->Form->control('body', [
                        'type' => 'textarea',
                        'label' => 'Add a note',
                        'rows' => 3,
                        'class' => 'form-control',
                        'required' => true,
                    ]) ?>
                </div>
                <?= $this->Form->button('Save note', ['class' => 'btn btn-eg-ghost mt-2']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section" aria-labelledby="customer-heading">
            <h2 id="customer-heading">Customer</h2>
            <div class="admin-panel">
                <dl class="eg-kv-list">
                    <div class="eg-kv-row">
                        <dt>Name</dt>
                        <dd><?= h($salesOrder->customer_label) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Email</dt>
                        <dd><?= h($salesOrder->customer->email ?? $salesOrder->guest_email ?? '—') ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Phone</dt>
                        <dd><?= h($salesOrder->customer->phone ?? $salesOrder->guest_phone ?? '—') ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="channel-heading">
            <h2 id="channel-heading">Channel</h2>
            <div class="admin-panel">
                <dl class="eg-kv-list">
                    <div class="eg-kv-row">
                        <dt>Source</dt>
                        <dd><?= h($channels[$salesOrder->source_channel] ?? $salesOrder->source_channel) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>External ref</dt>
                        <dd><?= h($salesOrder->external_source_reference ?: '—') ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="promise-heading">
            <h2 id="promise-heading">Promised delivery</h2>
            <div class="admin-panel">
                <?= $this->Form->create(null, ['url' => ['action' => 'updatePromisedDate', $salesOrder->id]]) ?>
                <div class="admin-field">
                    <?= $this->Form->control('promised_delivery_date', [
                        'type' => 'date',
                        'label' => 'Promised delivery date',
                        'class' => 'form-control',
                        'value' => $salesOrder->promised_delivery_date
                            ? $salesOrder->promised_delivery_date->format('Y-m-d')
                            : '',
                    ]) ?>
                </div>
                <?php if ($salesOrder->isDeliveryOverdue($today)) : ?>
                    <p class="eg-note" role="status">
                        Overdue <?= (int)$salesOrder->overdueDays($today) ?> days
                    </p>
                <?php endif; ?>
                <?= $this->Form->button('Update date', ['class' => 'btn btn-eg-ghost mt-2']) ?>
                <?= $this->Form->end() ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="advance-heading">
            <h2 id="advance-heading">Advance status</h2>
            <div class="admin-panel">
                <?php if ($nextStatuses === []) : ?>
                    <p class="admin-empty mb-0">This order is closed.</p>
                <?php else : ?>
                    <div class="admin-actions">
                        <?php foreach ($nextStatuses as $next) : ?>
                            <?= $this->Form->postButton(
                                $labels[$next] ?? $next,
                                ['action' => 'updateStatus', $salesOrder->id],
                                [
                                    'class' => $next === SalesOrder::STATUS_CANCELLED
                                        ? 'btn btn-eg-danger'
                                        : 'btn btn-eg-ghost',
                                    'data' => ['status' => $next],
                                ],
                            ) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="history-heading">
            <h2 id="history-heading">Status history</h2>
            <div class="admin-panel">
                <?php if (!$salesOrder->order_status_history) : ?>
                    <p class="admin-empty mb-0">No history yet.</p>
                <?php else : ?>
                    <ol class="admin-timeline">
                        <?php foreach ($salesOrder->order_status_history as $event) : ?>
                            <li>
                                <strong><?= h($labels[$event->to_status] ?? $event->to_status) ?></strong>
                                <div class="small text-muted">
                                    <?= h($event->created->format('d M Y, H:i')) ?>
                                    <?php if ($event->user) : ?>
                                        · <?= h($event->user->email) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($event->note) : ?>
                                    <div><?= h($event->note) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
