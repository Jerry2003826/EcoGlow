<?php
// templates/Admin/Payments/index.php

$this->assign('title', 'Payments');
?>

<h1 class="page-title">Payments</h1>

<div class="panel-card">
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Buyer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <?php
                $order = $payment->order;
                $buyerName = '';
                if ($order) {
                    $buyerName = !empty($order->CustomerId)
                        ? (string)($order->customer->CustomerName ?? '')
                        : (string)($order->guest->GuestName ?? '');
                }
                ?>
                <tr>
                    <td>
                        <?= $buyerName?>
                    </td>
                    <td>$<?= number_format((float)$payment->Amount, 2) ?></td>
                    <td><?= h($payment->PaymentMethod) ?></td>
                    <td>
                        <?= $payment->PaymentDate
                            ? $payment->PaymentDate->format('d/m/Y H:i')
                            : ''
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
