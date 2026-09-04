<?php

use App\Service\Money;

$this->assign('title', (string)$order->order_number);

$customerName = '';

if ($order->customer) {
    $customerName = trim(
        (string)($order->customer->first_name ?? '') . ' ' .
        (string)($order->customer->last_name ?? '')
    );
}
?>

<h1 class="page-title"><?= h((string)$order->order_number) ?></h1>

<section class="panel-card">
    <dl class="attention-list">
        <dt>Status</dt>
        <dd><?= h((string)$order->status) ?></dd>

        <dt>Payment</dt>
        <dd><?= h((string)$order->payment_status) ?></dd>

        <dt>Total</dt>
        <dd><?= h(Money::formatAud((int)$order->grand_total_cents)) ?></dd>

        <dt>Placed</dt>
        <dd><?= h((string)$order->placed_at) ?></dd>

        <dt>Customer</dt>
        <dd><?= h($customerName) ?></dd>

        <dt>Email</dt>
        <dd><?= h((string)($order->customer?->email ?? $order->guest_email)) ?></dd>

        <dt>Phone</dt>
        <dd><?= h((string)($order->customer?->phone ?? $order->guest_phone)) ?></dd>

        <dt>Guest Name</dt>
        <dd><?= h((string)$order->guest_name) ?></dd>
    </dl>

    <p>
        <?= $this->Html->link('Back to Orders', ['prefix' => 'Admin', 'controller' => 'Orders', 'action' => 'index']) ?>
    </p>
</section>
