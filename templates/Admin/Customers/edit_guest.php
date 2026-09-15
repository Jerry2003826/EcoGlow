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
