<?php

$this->assign('title', 'Generate Quotation');
?>

<h1 class="page-title">Generate Quotation</h1>

<div class="panel-card">
    <?= $this->Form->create($quotation) ?>

    <?= $this->Form->control('customer_id', [
        'options' => collection($customers)->combine(
            'id',
            fn ($customer) => trim(
                ($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')
            )
        )->toArray(),
        'label' => 'Customer',
    ]) ?>

    <?= $this->Form->control('status', [
        'options' => [
            'draft' => 'Draft',
            'issued' => 'Issued',
        ],
    ]) ?>

    <?= $this->Form->control('valid_until', [
        'type' => 'date',
    ]) ?>

    <?= $this->Form->control('notes') ?>

    <h2>Items</h2>

    <?php for ($i = 0; $i < 3; $i++): ?>
        <?= $this->Form->control("items.$i.product_variant_id", [
            'label' => 'Product Variant',
            'options' => collection($productVariants)->combine(
                'id',
                fn ($variant) => $variant->sku . ' - ' . $variant->name
            )->toArray(),
            'empty' => true,
        ]) ?>

        <?= $this->Form->control("items.$i.quantity", [
            'type' => 'number',
            'min' => 1,
            'value' => 1,
        ]) ?>
    <?php endfor; ?>

    <?= $this->Form->button('Save Quotation') ?>

    <?= $this->Form->end() ?>
</div>
