<?php
// templates/Admin/Orders/index.php

$this->assign('title', 'Orders');
?>

<h1 class="page-title">Orders and bookings</h1>

<div class="panel-card">
    <?= $this->Form->create(null, [
        'label' => false,
        'type' => 'get',
        'class' => 'admin-search-form',
    ]) ?>
    <?= $this->Form->control('q', [
        'label' => 'Search',
        'placeholder' => 'order number, name, email',
        'value' => $q ?? '',
    ]) ?>
    <?= $this->Form->control('type', [
        'label' => 'Filter',
        'value' => $type ?? '',
        'options' => [
            '' => 'All orders and bookings',
            'product' => 'Product orders',
            'installation' => 'Installation bookings',
            'repair' => 'Repair bookings',
        ],
    ]) ?>
    <?= $this->Form->button('Search', ['class' => 'admin-search-button']) ?>
    <?= $this->Form->end() ?>
</div>

<?php if ($type !== 'repair'): ?>
    <div class="panel-card">
        <h2>Product and installation orders</h2>
        <?php if (empty($orders) || count($orders) === 0): ?>
            <div class="empty-state">
                <p>No orders match your current search.</p>
                <?= $this->Html->link('Clear search and filters', '/admin/orders') ?>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Type</th>
                        <th>Buyer type</th>
                        <th>Buyer</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>OrderDate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $isInstall = !empty($order->shopping_cart)
                            && (int)$order->shopping_cart->InstallOptIn === 1;
                        ?>
                        <tr
                            class="admin-clickable-row"
                            data-href="/admin/orders/view/<?= (int)$order->OrderId ?>"
                        >
                            <td>
                                <?= $this->Html->link(
                                    (string)$order->OrderId,
                                    [
                                        'action' => 'view', 
                                        $order->OrderId,
                                    ],
                                    ['class' => 'order-link']
                                ) ?>
                            </td>
                            <td><?= $isInstall ? 'Product + installation' : 'Product' ?></td>
                            <td><?= $order->CustomerId ? 'Customer' : 'Guest' ?></td>
                            <td>
                                <?= h(
                                    $order->CustomerId
                                        ? ($order->customer->CustomerName ?? '')
                                        : ($order->guest->GuestName ?? '')
                                ) ?>
                            </td>
                            <td>
                                <?= h(
                                    $order->CustomerId
                                        ? ($order->customer->Email ?? '')
                                        : ($order->guest->Email ?? '')
                                ) ?>
                            </td>
                            <td><?= h($order->Status ?? '') ?></td>
                            <td><?= h((string)$order->OrderDate) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($type === '' || $type === 'repair'): ?>
    <div class="panel-card">
        <h2>Repair bookings</h2>
        <?php if (empty($repairs) || count($repairs) === 0): ?>
            <div class="empty-state">
                <p>No repair bookings match your current search.</p>
                <?= $this->Html->link('Clear search and filters', '/admin/orders') ?>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>ServiceRequestId</th>
                        <th>Type</th>
                        <th>Scheduled</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($repairs as $repair): ?>
                        <tr>
                            <td><?= (int)$repair->ServiceRequestId ?></td>
                            <td><?= h($repair->RequestType) ?></td>
                            <td><?= h((string)$repair->RequestedDate) ?></td>
                            <td><?= h($repair->Description) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel-card">
        <h2>Add repair booking</h2>
        <?= $this->Form->create(null, [
            'url' => ['action' => 'addRepair'],
        ]) ?>
        <?= $this->Form->control('RequestedDate', [
            'label' => 'Booking time',
            'type' => 'datetime-local',
        ]) ?>
        <?= $this->Form->control('Description', [
            'label' => 'Customer and fault details',
            'type' => 'textarea',
            'required' => true,
        ]) ?>
        <?= $this->Form->button('Save repair booking') ?>
        <?= $this->Form->end() ?>
    </div>
<?php endif; ?>
