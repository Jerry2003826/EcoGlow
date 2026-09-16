<?php
// templates/Admin/Products/add.php

$this->assign('title', 'Add Product');
?>

<h1 class="page-title">Add Product</h1>

<div class="panel-card">
    <?= $this->Form->create($product, ['class' => 'admin-form', 'type' => 'file']) ?>
    <?= $this->Form->control('ProductName', [
        'label' => 'Product name',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('CategoryId', [
        'label' => 'Category',
        'options' => $categories ?? [],
        'empty' => 'Select a category',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('SupplierId', [
        'label' => 'Supplier',
        'options' => $suppliers ?? [],
        'empty' => 'Select a supplier',
    ]) ?>
    <?= $this->Form->control('Description', [
        'label' => 'Description',
        'type' => 'textarea',
        'rows' => 4,
    ]) ?>
    <?= $this->Form->control('Price', [
        'label' => 'Price',
        'type' => 'number',
        'step' => '0.01',
        'min' => '0',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Image', [
        'label' => 'Product image',
        'type' => 'file',
        'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
    ]) ?>
    <div class="form-actions">
        <?= $this->Form->button('Save', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to Inventory', '/admin/inventory', ['class' => 'admin-back-link']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
