<?php
// templates/Admin/Customers/edit_guest.php

$this->assign('title', 'Edit Guest');
?>

<h1 class="page-title">Edit Guest</h1>

<div class="panel-card">
    <?= $this->Form->create($guest, ['class' => 'admin-form']) ?>
    <?= $this->Form->control('GuestName', [
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
        'required' => true,
        'inputmode' => 'numeric',
        'pattern' => '04[0-9]{8}',
        'maxlength' => 10,
        'title' => 'Please enter a 10-digit Australian mobile number starting with 04',
    ]) ?>
    <?= $this->Form->control('Address', [
        'label' => 'Address',
        'required' => true,
    ]) ?>
    <div class="form-actions">
        <?= $this->Form->button('Update', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to Customers', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
