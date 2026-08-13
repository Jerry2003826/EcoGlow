<?php
/**
 * Order confirmation after Stripe return_url.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\SalesOrder $order
 */

use App\Model\Entity\SalesOrder;

$this->assign('title', 'Order ' . $order->order_number);
$this->Html->css(['account', 'checkout'], ['block' => true]);
$labels = SalesOrder::statusLabels();
$shipping = null;
foreach ($order->order_addresses ?? [] as $row) {
    if ($row->address_type === 'shipping') {
        $shipping = $row;
        break;
    }
}
$paid = $order->payment_status === 'paid'
    || $order->status === SalesOrder::STATUS_CONFIRMED
    || $order->status === SalesOrder::STATUS_PROCESSING
    || $order->status === SalesOrder::STATUS_DISPATCHED
    || $order->status === SalesOrder::STATUS_COMPLETED;
?>
<div class="container py-5 checkout-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= $this->Url->build('/account/orders') ?>">Orders</a></li>
            <li aria-current="page"><?= h($order->order_number) ?></li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow"><?= $paid ? 'Confirmed' : 'Received' ?></span>
        <h1 class="section-title"><?= h($order->order_number) ?></h1>
    </div>

    <div class="eg-card p-4 p-md-5 mb-4" style="max-width: 40rem;">
        <?php if ($paid) : ?>
            <p role="status">Thank you. This order is confirmed. We will send a confirmation email shortly.</p>
        <?php else : ?>
            <p role="status">
                We have your order. Payment confirmation can take a moment to arrive;
                we will email you when it clears. Status:
                <?= h($labels[$order->status] ?? $order->status) ?>.
            </p>
        <?php endif; ?>

        <dl class="eg-kv-list">
            <div class="eg-kv-row">
                <dt>Amount</dt>
                <dd><?= $this->Money->aud((int)$order->grand_total_cents) ?></dd>
            </div>
            <?php if ($order->promised_delivery_date) : ?>
                <div class="eg-kv-row">
                    <dt>Promised delivery</dt>
                    <dd><?= h($order->promised_delivery_date->format('d M Y')) ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if ($shipping) : ?>
            <h2 class="checkout-section-title">Delivery address</h2>
            <p class="mb-0">
                <?= h($shipping->recipient_name) ?><br>
                <?= h($shipping->line1) ?>
                <?= $shipping->line2 ? '<br>' . h($shipping->line2) : '' ?><br>
                <?= h($shipping->suburb) ?> <?= h($shipping->state) ?> <?= h($shipping->postcode) ?>
            </p>
        <?php endif; ?>
    </div>

    <a class="btn btn-eg-ghost" href="<?= $this->Url->build('/account/orders/' . (int)$order->id) ?>">
        View in your account
    </a>
</div>
