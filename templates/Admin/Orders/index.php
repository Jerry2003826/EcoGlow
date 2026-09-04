<?php

use App\Service\Money;

$this->assign('title', 'Orders');
?>

<h1 class="page-title">Orders</h1>

<?php if ($orders->count() === 0): ?>
    <div class="panel-card">
        <div class="empty-state">
            Product sales orders, paid orders, guest walk-in orders and customer orders will appear here.
        </div>
    </div>
<?php else: ?>
    <section class="panel-card">
        <div class="table-responsive">
            <table class="table admin-table align-middle">
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
                    <?php
                    $customerName = '';

                    if ($order->customer) {
                        $customerName = trim(
                            (string)($order->customer->first_name ?? '') . ' ' .
                            (string)($order->customer->last_name ?? '')
                        );

                        if ($customerName === '') {
                            $customerName = (string)$order->customer->email;
                        }
                    } else {
                        $customerName = (string)(
                            $order->guest_name
                            ?: $order->guest_email
                            ?: $order->guest_phone
                        );
                    }
                    ?>
                    <tr>
                        <td>
                            <?= $this->Html->link(
                                (string)$order->order_number,
                                ['prefix' => 'Admin', 'controller' => 'Orders', 'action' => 'view', $order->id]
                            ) ?>
                        </td>
                        <td><?= h($customerName) ?></td>
                        <td><?= h((string)$order->status) ?></td>
                        <td><?= h((string)$order->payment_status) ?></td>
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
    </section>
<?php endif; ?>
