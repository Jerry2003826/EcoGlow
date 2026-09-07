<?php
// templates/Products/index.php

$this->assign('title', 'Products');

$categoryOptions = ['' => 'All Categories'];

foreach ($categories as $categoryItem) {
    $categoryOptions[$categoryItem->CategoryId] = $categoryItem->CategoryName;
}
?>

<div class="store-toolbar">
    <?= $this->Html->link('View Cart', '/cart') ?>
</div>

<div class="store-search">
    <?= $this->Form->create(null, ['type' => 'get']) ?>
    <?= $this->Form->control('q', ['value' => $q ?? '']) ?>
    <?= $this->Form->control('category', [
        'options' => $categoryOptions,
        'value' => $category ?? '',
    ]) ?>
    <?= $this->Form->button('Search') ?>
    <?= $this->Form->end() ?>
</div>

<p>Continue shopping and add items to your cart. View Cart anytime.</p>

<div class="product-grid">
    <?php foreach ($products as $product): ?>
        <?php
        $image = $product->Image ?: (
            (int)$product->CategoryId === 1
                ? 'landing/pendants.jpg'
                : 'landing/wall-light.jpg'
        );
        ?>
        <div class="product-card">
            <?= $this->Html->image($image) ?>

            <h3><?= h($product->ProductName) ?></h3>

            <p><?= h($product->Description) ?></p>

            <div class="price-tag">
                $<?= number_format((float)$product->Price, 2) ?>
            </div>

            <?= $this->Form->create() ?>
            <?= $this->Form->hidden('product_id', ['value' => $product->ProductId]) ?>
            <?= $this->Form->button('Add to Cart') ?>
            <?= $this->Form->end() ?>
        </div>
    <?php endforeach; ?>
</div>
