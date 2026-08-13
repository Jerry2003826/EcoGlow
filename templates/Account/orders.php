<?php
/**
 * Customer order history.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var iterable<\App\Model\Entity\SalesOrder> $orders
 */

use App\Model\Entity\SalesOrder;

$this->assign('title', 'Your Orders');
$this->Html->css('account', ['block' => true]);
$labels = SalesOrder::statusLabels();
?>
<div class="container py-5 account-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= $this->Url->build('/account') ?>">Account</a></li>
            <li aria-current="page">Orders</li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Your account</span>
        <h1 class="section-title">Orders</h1>
    </div>

    <?= $this->element('account/nav', ['current' => 'orders']) ?>

    <?php if (count($orders) === 0) : ?>
        <p class="text-muted">You have not placed an order yet.</p>
    <?php else : ?>
        <ul class="eg-cart-list">
            <?php foreach ($orders as $order) : ?>
                <li class="eg-card p-4 mb-3">
                    <p class="mb-1">
                        <a href="<?= $this->Url->build('/account/orders/' . (int)$order->id) ?>">
                            <?= h($order->order_number) ?>
                        </a>
                    </p>
                    <p class="account-order-status mb-1">
                        <?= h($labels[$order->status] ?? $order->status) ?>
                        <?php if ($order->promised_delivery_date) : ?>
                            · Promised <?= h($order->promised_delivery_date->format('d M Y')) ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-0"><?= $this->Money->aud($order->grand_total_cents) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
