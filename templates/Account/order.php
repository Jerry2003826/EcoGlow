<?php
/**
 * Customer order detail — own orders only.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\SalesOrder $order
 */

use App\Model\Entity\SalesOrder;

$this->assign('title', $order->order_number);
$this->Html->css('account', ['block' => true]);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Account', 'url' => '/account'],
        ['label' => 'Orders', 'url' => '/account/orders'],
        ['label' => $order->order_number],
    ],
]));
$labels = SalesOrder::statusLabels();
?>
<div class="eg-page-head eg-page-head-start">
    <span class="eg-eyebrow">Order</span>
    <h1 class="section-title"><?= h($order->order_number) ?></h1>
    <p class="account-order-status mb-0">
        <?= h($labels[$order->status] ?? $order->status) ?>
        <?php if ($order->promised_delivery_date) : ?>
            · Promised delivery <?= h($order->promised_delivery_date->format('d M Y')) ?>
        <?php endif; ?>
    </p>
</div>

<div class="eg-card p-4 mb-4">
        <h2 class="h5 mb-3">Items</h2>
        <?php if (empty($order->sales_order_items)) : ?>
            <p class="text-muted mb-0">No line items on this order.</p>
        <?php else : ?>
            <dl class="eg-kv-list mb-0">
                <?php foreach ($order->sales_order_items as $item) : ?>
                    <div class="eg-kv-row">
                        <dt>
                            <?= h($item->item_name_snapshot) ?>
                            <?php if ($item->variant_name_snapshot) : ?>
                                <span class="product-meta"> · <?= h($item->variant_name_snapshot) ?></span>
                            <?php endif; ?>
                            <span class="product-meta"> × <?= (int)$item->quantity ?></span>
                        </dt>
                        <dd><?= $this->Money->aud($item->line_total_cents) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
</div>

<div class="eg-card eg-summary p-4" style="max-width: 24rem;">
    <h2 class="h5 mb-3">Totals</h2>
    <dl class="eg-kv-list mb-0">
        <div class="eg-kv-row">
            <dt>Subtotal</dt>
            <dd><?= $this->Money->aud($order->subtotal_cents) ?></dd>
        </div>
        <div class="eg-kv-row">
            <dt>Delivery</dt>
            <dd><?= $this->Money->aud($order->shipping_cents) ?></dd>
        </div>
        <div class="eg-kv-row is-total">
            <dt>Total</dt>
            <dd><?= $this->Money->aud($order->grand_total_cents) ?></dd>
        </div>
    </dl>
</div>
