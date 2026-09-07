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
                <th>PaymentId</th>
                <th>OrderId</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= (int)$payment->PaymentId ?></td>
                    <td>
                        <?= $this->Html->link(
                            (string)$payment->OrderId,
                            ['controller' => 'Orders', 'action' => 'view', $payment->OrderId]
                        ) ?>
                    </td>
                    <td>$<?= number_format((float)$payment->Amount, 2) ?></td>
                    <td><?= h($payment->PaymentMethod) ?></td>
                    <td><?= h((string)$payment->PaymentDate) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
