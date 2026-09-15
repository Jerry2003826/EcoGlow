<?php
// templates/Admin/Customers/edit.php

$this->assign('title', 'Edit Customer');
?>

<h1 class="page-title">Edit Customer</h1>

<div class="panel-card">
    <?= $this->Form->create($customer, ['class' => 'admin-form']) ?>
    <?= $this->Form->control('CustomerName', [
        'label' => 'Name',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Email', [
        'label' => 'Email',
        'type' => 'email',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Phone', [
        'label' => 'Phone',
    ]) ?>
    <?= $this->Form->control('Address', [
        'label' => 'Address',
    ]) ?>
    <div class="form-actions">
        <?= $this->Form->button('Update', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to Customers', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
