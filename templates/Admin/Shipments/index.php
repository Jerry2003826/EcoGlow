<?php
// templates/Admin/Shipments/index.php

$this->assign('title', 'Shipments');
?>

<h1 class="page-title">Shipments</h1>

<div class="panel-card">
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Delivery Number</th>
                <th>Buyer type</th>
                <th>Buyer</th>
                <th>Address</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $shipment): ?>
                <?php $order = $shipment->order; ?>
                <tr
                    class="admin-clickable-row"
                    data-href="/admin/shipments/edit/<?= (int)$shipment->DeliveryId ?>"
                >
                    <td><?= (int)$shipment->DeliveryId ?></td>
                    <td><?= !empty($order?->CustomerId) ? 'Customer' : 'Guest' ?></td>
                    <td>
                        <?= h(
                            !empty($order?->CustomerId)
                                ? ($order->customer->CustomerName ?? '')
                                : ($order->guest->GuestName ?? '')
                        ) ?>
                    </td>
                    <td><?= h($shipment->DeliveryAddress) ?></td>
                    <td><?= h($shipment->DeliveryStatus) ?></td>
                    <td><?= h((string)$shipment->DeliveryDate) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
