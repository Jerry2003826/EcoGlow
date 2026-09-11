<?php
// templates/Account/orders.php
?>

<div class="checkout-card">
    <h1>My orders</h1>
    <p><?= $this->Html->link('Back to account', '/account') ?></p>

    <?php if ($orders->isEmpty()): ?>
        <p>You have not placed any orders yet.</p>
    <?php else: ?>
        <table class="receipt-table">
            <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            '#' . $order->OrderId,
                            '/account/orders/' . $order->OrderId
                        ) ?>
                    </td>
                    <td><?= h((string)$order->OrderDate) ?></td>
                    <td><?= h((string)$order->Status) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
