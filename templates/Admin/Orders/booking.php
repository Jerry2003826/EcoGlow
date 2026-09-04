<?php

$this->assign('title', (string)$booking->booking_number);
?>
<h1 class="page-title"><?= h($booking->booking_number) ?></h1>

<div class="panel-card">
    <dl>
        <dt>Booking #</dt>
        <dd><?= h($booking->booking_number) ?></dd>

        <dt>Type</dt>
        <dd><?= h($booking->service_type) ?></dd>

        <dt>Status</dt>
        <dd><?= h($booking->status) ?></dd>

        <dt>Scheduled</dt>
        <dd><?= h((string)$booking->scheduled_at) ?></dd>

        <dt>Customer / Guest</dt>
        <dd>
            <?= h(
                $booking->customer
                    ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                    : ($booking->guest_name ?: $booking->guest_email ?: $booking->guest_phone)
            ) ?>
        </dd>

        <dt>Notes</dt>
        <dd><?= h((string)$booking->notes) ?></dd>
    </dl>

    <?= $this->Html->link('Back to Orders', ['action' => 'index']) ?>
</div>
