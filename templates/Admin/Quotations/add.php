<?php
// templates/Admin/Quotations/add.php

$this->assign('title', 'Generate Quotation');

$customerOptions = collection($customers)
    ->combine(
        'id',
        fn ($c) => trim(
            ($c->first_name ?? '') . ' ' . ($c->last_name ?? '')
        )
    )
    ->toArray();

$productVariantOptions = collection($productVariants)
    ->combine(
        'id',
        fn ($v) => $v->sku . ' - ' . $v->name
    )
    ->toArray();
?>

<h1 class="page-title">Generate Quotation</h1>

<div class="panel-card">
    <?= $this->Form->create($quotation) ?>

    <?= $this->Form->control('customer_id', [
        'options' => $customerOptions,
        'label' => 'Customer',
    ]) ?>

    <?= $this->Form->control('category', [
        'options' => [
            'product_sale' => 'Product Sale',
            'installation' => 'Installation',
            'repair' => 'Repair',
        ],
        'label' => 'Category',
    ]) ?>

    <?= $this->Form->control('status', [
        'options' => [
            'draft' => 'Draft',
            'issued' => 'Issued',
        ],
        'label' => 'Status',
    ]) ?>

    <?= $this->Form->control('valid_until', [
        'type' => 'date',
    ]) ?>

    <?= $this->Form->control('notes') ?>

    <h2>Items</h2>

    <?php for ($i = 0; $i < 3; $i++): ?>
        <?= $this->Form->control("items.$i.product_variant_id", [
            'label' => 'Product Variant',
            'options' => $productVariantOptions,
            'empty' => true,
        ]) ?>

        <?= $this->Form->control("items.$i.quantity", [
            'type' => 'number',
            'min' => 1,
            'value' => 1,
            'label' => 'Quantity',
        ]) ?>
    <?php endfor; ?>

    <?= $this->Form->button('Save Quotation') ?>

    <?= $this->Form->end() ?>
</div>
