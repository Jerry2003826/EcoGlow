<?php
// templates/Admin/Orders/index.php

$this->assign('title', 'Orders');
?>

<h1 class="page-title">Orders</h1>

<div class="panel-card">
    <?= $this->Form->create(null, ['type' => 'get']) ?>

    <?= $this->Form->control('q', [
        'label' => 'Search',
        'value' => $q ?? '',
    ]) ?>

    <?= $this->Form->button('Search') ?>

    <?= $this->Form->end() ?>
</div>

<?php if (empty($orders) || count($orders) === 0): ?>
    <div class="empty-state">
        No orders found.
    </div>
<?php else: ?>
    <div class="panel-card">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>OrderId</th>
                <th>Guest Name</th>
                <th>Guest Email</th>
                <th>Status</th>
                <th>Order Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            (string)$order->OrderId,
                            ['action' => 'view', $order->OrderId]
                        ) ?>
                    </td>
                    <td><?= h($order->guest->GuestName ?? '') ?></td>
                    <td><?= h($order->guest->Email ?? '') ?></td>
                    <td><?= h($order->Status ?? '') ?></td>
                    <td><?= h((string)$order->OrderDate) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
