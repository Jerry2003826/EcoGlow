<?php

use App\Service\Money;

$this->assign('title', (string)$order->order_number);
?>
<h1 class="page-title"><?= h($order->order_number) ?></h1>

<div class="panel-card">
    <dl>
        <dt>Status</dt>
        <dd><?= h($order->status) ?></dd>

        <dt>Payment</dt>
        <dd><?= h($order->payment_status) ?></dd>

        <dt>Total</dt>
        <dd><?= h(Money::formatAud((int)$order->grand_total_cents)) ?></dd>

        <dt>Placed</dt>
        <dd><?= h((string)$order->placed_at) ?></dd>

        <dt>Promised Delivery</dt>
        <dd><?= h((string)$order->promised_delivery_on) ?></dd>

        <dt>Delivery Notes</dt>
        <dd><?= h((string)$order->delivery_notes) ?></dd>

        <dt>Customer</dt>
        <dd>
            <?= h(
                $order->customer
                    ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                    : ($order->guest_name ?: $order->guest_email ?: $order->guest_phone)
            ) ?>
        </dd>
    </dl>
</div>
