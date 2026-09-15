<?php
// templates/Admin/Shipments/edit.php

$this->assign('title', 'Edit Shipment');
?>

<h1 class="page-title">Edit Shipment</h1>

<div class="panel-card">
    <?= $this->Form->create($shipment, ['class' => 'admin-form']) ?>
    <?= $this->Form->control('DeliveryAddress', [
        'label' => 'Delivery address',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('DeliveryStatus', [
        'label' => 'Status',
        'options' => [
            'scheduled' => 'scheduled',
            'in_transit' => 'in_transit',
            'delivered' => 'delivered',
        ],
        'required' => true,
    ]) ?>
    <?= $this->Form->control('DeliveryDate', [
        'label' => 'Delivery / booking date',
        'type' => 'datetime-local',
        'value' => $shipment->DeliveryDate
            ? date('Y-m-d\TH:i', strtotime((string)$shipment->DeliveryDate))
            : '',
    ]) ?>
    <div class="form-actions">
        <?= $this->Form->button('Update', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to Shipments', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
