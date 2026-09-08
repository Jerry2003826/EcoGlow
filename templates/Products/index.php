<?php
// templates/Products/index.php

$this->assign('title', 'Products');

$categoryOptions = ['' => 'All Categories'];

foreach ($categories as $categoryItem) {
    $categoryOptions[$categoryItem->CategoryId] = $categoryItem->CategoryName;
}
?>

<div class="store-toolbar">
    <?= $this->Html->link('View Cart', '/cart', ['class' => 'button']) ?>
</div>

<div class="store-search">
    <?= $this->Form->create(null, ['type' => 'get']) ?>
    <?= $this->Form->control('q', [
        'label' => 'Search',
        'value' => $q ?? '',
    ]) ?>
    <?= $this->Form->control('category', [
        'label' => 'Category',
        'options' => $categoryOptions,
        'value' => $category ?? '',
    ]) ?>
    <?= $this->Form->button('Search') ?>
    <?= $this->Form->end() ?>
</div>

<div class="product-grid">
    <?php foreach ($products as $product): ?>
        <?php
        $image = ((int)$product->CategoryId === 1)
            ? 'landing/pendants.jpg'
            : 'landing/wall-light.jpg';

        $stockQty = (int)($product->inventory?->Quantity ?? 0);
        ?>
        <div class="product-card">
            <?= $this->Html->image($image) ?>

            <h3><?= h($product->ProductName) ?></h3>

            <p><?= h($product->Description) ?></p>

            <div class="price-tag">
                $<?= number_format((float)$product->Price, 2) ?>
            </div>

            <p>Available: <?= $stockQty ?></p>

            <?php if ($stockQty > 0): ?>
                <?= $this->Form->create() ?>
                <?= $this->Form->hidden('product_id', [
                    'value' => $product->ProductId,
                ]) ?>
                <?= $this->Form->button('Add to Cart') ?>
                <?= $this->Form->end() ?>
            <?php else: ?>
                <div class="out-of-stock">
                    Out of stock
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
