<?php
// templates/Admin/Suppliers/edit.php

$this->assign('title', 'Edit Supplier');
?>

<h1 class="page-title">Edit Supplier</h1>

<div class="panel-card">
    <?= $this->Form->create($supplier) ?>

    <?= $this->Form->control('name') ?>
    <?= $this->Form->control('email') ?>
    <?= $this->Form->control('phone') ?>
    <?= $this->Form->control('address_line_1') ?>
    <?= $this->Form->control('suburb') ?>
    <?= $this->Form->control('state') ?>
    <?= $this->Form->control('postcode') ?>
    <?= $this->Form->control('notes') ?>

    <?= $this->Form->button('Save Supplier') ?>

    <?= $this->Form->end() ?>

    <?= $this->Html->link(
        'Back to Supplier',
        ['action' => 'view', $supplier->id]
    ) ?>
</div>
