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
                <th>DeliveryId</th>
                <th>OrderId</th>
                <th>Guest</th>
                <th>Address</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $shipment): ?>
                <tr>
                    <td><?= (int)$shipment->DeliveryId ?></td>
                    <td>
                        <?= $this->Html->link(
                            (string)$shipment->OrderId,
                            ['controller' => 'Orders', 'action' => 'view', $shipment->OrderId]
                        ) ?>
                    </td>
                    <td><?= h($shipment->order->guest->GuestName ?? '') ?></td>
                    <td><?= h($shipment->DeliveryAddress) ?></td>
                    <td><?= h($shipment->DeliveryStatus) ?></td>
                    <td><?= h((string)$shipment->DeliveryDate) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
