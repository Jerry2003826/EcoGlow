<?php
// templates/Account/orders.php
$this->assign('title', 'My orders');
?>

<div class="account-page">
    <header class="account-heading">
        <?= $this->Html->link('Back to account', '/account', ['class' => 'account-back-link']) ?>
        <h1>My orders</h1>
        <p>Open an order to view its items, payment and delivery details.</p>
    </header>

    <section class="account-card">
    <?php if ($orders->isEmpty()): ?>
        <div class="account-empty-state">
            <h2>Your next light is waiting</h2>
            <p>You have not placed any orders yet.</p>
            <?= $this->Html->link('Explore lighting', '/products', ['class' => 'account-button']) ?>
        </div>
    <?php else: ?>
        <div class="account-table-wrap" tabindex="0" role="region" aria-label="Your orders, scroll horizontally if needed">
        <table class="account-table">
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
                    <td><span class="account-status"><?= h(ucfirst((string)$order->Status)) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    </section>
</div>
