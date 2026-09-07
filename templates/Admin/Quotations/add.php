<?php
// templates/Admin/Quotations/add.php

$this->assign('title', 'New quotation');
?>

<h1 class="page-title">New quotation</h1>

<div class="panel-card">
    <?= $this->Form->create() ?>
    <?= $this->Form->control('CustomerName', ['label' => 'Customer name', 'required' => true]) ?>
    <?= $this->Form->control('Email', ['label' => 'Email', 'type' => 'email', 'required' => true]) ?>
    <?= $this->Form->control('Phone', ['label' => 'Phone']) ?>
    <?= $this->Form->control('Address', ['label' => 'Address']) ?>
    <?= $this->Form->control('TotalAmount', [
        'label' => 'Total AUD (GST inclusive)',
        'type' => 'number',
        'step' => '0.01',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Notes', [
        'label' => 'Line items and notes',
        'type' => 'textarea',
    ]) ?>
    <?= $this->Form->button('Save and email quotation') ?>
    <?= $this->Form->end() ?>
    <p><?= $this->Html->link('Back', '/admin/quotations', ['class' => 'admin-back-link']) ?></p>
</div>
