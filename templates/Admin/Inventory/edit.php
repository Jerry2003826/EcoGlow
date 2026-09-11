<?php

$this->assign('title', 'Edit Inventory');
?>

<h1 class="page-title">Edit Inventory</h1>

<div class="panel-card">
    <p><strong>Product:</strong> <?= h($inventory->product->ProductName ?? '') ?></p>

    <?= $this->Form->create($inventory)?>
    <?= $this->Form->control('Quantity', [
        'label' => 'On hand',
        'type' => 'number',
        'min' => 0,
        'step' => 1,
    ]) ?>

    <?= $this->Form->control('LowStockThreshold', [
        'label' => 'Low stock at',
        'type' => 'number',
        'min' => 1,
        'step' => 1,
    ]) ?>
    <?= $this->Form->button('Save Inventory') ?>
    <?= $this->Form->end() ?>

    <?= $this->Html->link('Back to Inventory', ['action' => 'index']) ?>
</div>