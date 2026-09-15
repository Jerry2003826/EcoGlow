<?php
// templates/Admin/Suppliers/add.php

$this->assign('title', 'Add Supplier');
?>

<h1 class="page-title">Add Supplier</h1>

<div class="panel-card">
    <?= $this->Form->create($supplier, ['class' => 'admin-form']) ?>
    <?= $this->Form->control('SupplierName', [
        'label' => 'Supplier name',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Email', [
        'label' => 'Email',
        'type' => 'email',
    ]) ?>
    <?= $this->Form->control('Phone', [
        'label' => 'Phone',
    ]) ?>
    <?= $this->Form->control('Address', [
        'label' => 'Address',
    ]) ?>
    <div class="form-actions">
        <?= $this->Form->button('Save', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to Suppliers', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
