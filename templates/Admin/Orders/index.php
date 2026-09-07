<?php
// templates/Admin/Orders/index.php

use App\Service\Money;
?>

<h1 class="page-title">Orders</h1>

<div class="panel-card">
    <?= $this->Form->create(null, ['type' => 'get']) ?>
    <?= $this->Form->control('q', ['value' => $q]) ?>
    <?= $this->Form->button('Search') ?>
    <?= $this->Form->end() ?>

    <?php if ($canCreateOrder): ?>
        <?= $this->Html->link('Create Order', ['action' => 'add']) ?>
    <?php endif; ?>

    <?php if ($canManageServices): ?>
        <?= $this->Html->link('Create Booking', ['action' => 'addBooking']) ?>
    <?php endif; ?>
</div>

<div class="panel-card">
    <h2>Product Orders</h2>

    <table class="table admin-table">
        <thead>
        <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Total</th>
            <th>Placed</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $this->Html->link($order->order_number, ['action' => 'view', $order->id]) ?></td>
                <td><?= h(trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))) ?></td>
                <td><?= h($order->status) ?></td>
                <td><?= h($order->payment_status) ?></td>
                <td><?= h(Money::formatAud((int)$order->grand_total_cents)) ?></td>
                <td><?= h((string)$order->placed_at) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="panel-card">
    <h2>Service Bookings</h2>

    <table class="table admin-table">
        <thead>
        <tr>
            <th>Booking #</th>
            <th>Type</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Scheduled</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= $this->Html->link($booking->booking_number, ['action' => 'booking', $booking->id]) ?></td>
                <td><?= h($booking->service_type) ?></td>
                <td><?= h(trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))) ?></td>
                <td><?= h($booking->status) ?></td>
                <td><?= h((string)$booking->scheduled_at) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
