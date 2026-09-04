<?php

use App\Service\Money;

$this->assign('title', 'Orders');
?>
<h1 class="page-title">Orders</h1>

<div class="panel-card">
    <h2>Product Orders</h2>

    <?php if (empty($orders) || $orders->count() === 0): ?>
        <div class="empty-state">
            Product sales orders, paid orders, guest orders and customer orders will appear here.
        </div>
    <?php else: ?>
        <div class="table-responsive">
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
                        <td>
                            <?= h(
                                $order->customer
                                    ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                                    : ($order->guest_name ?: $order->guest_email ?: $order->guest_phone)
                            ) ?>
                        </td>
                        <td><?= h($order->status) ?></td>
                        <td><?= h($order->payment_status) ?></td>
                        <td><?= h(Money::formatAud((int)$order->grand_total_cents)) ?></td>
                        <td><?= h((string)$order->placed_at) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="paginator">
            <?= $this->Paginator->numbers() ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel-card">
    <h2>Service Bookings</h2>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            Installation bookings and repair bookings will appear here.
        </div>
    <?php else: ?>
        <div class="table-responsive">
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
                        <td>
                            <?= h(
                                $booking->customer
                                    ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                                    : ($booking->guest_name ?: $booking->guest_email ?: $booking->guest_phone)
                            ) ?>
                        </td>
                        <td><?= h($booking->status) ?></td>
                        <td><?= h((string)$booking->scheduled_at) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
