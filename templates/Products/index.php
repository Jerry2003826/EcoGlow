<?php
// templates/Products/index.php

$this->assign('title', 'Products');
$this->Html->css('products', ['block' => true, 'timestamp' => true]);

$categoryOptions = ['' => 'All Categories'];

foreach ($categories as $categoryItem) {
    $categoryOptions[$categoryItem->CategoryId] = $categoryItem->CategoryName;
}
?>

<div class="products-page">
    <section class="products-banner" aria-labelledby="products-banner-title">
        <div class="products-banner-content">
            <h1 id="products-banner-title">Our Products.</h1>
        </div>
    </section>

    <div class="products-heading">
        <div>
            <p class="products-eyebrow">The collection</p>
            <h2>Lighting with presence.</h2>
        </div>
        <?= $this->Html->link('View Cart', '/cart', ['class' => 'products-cart-link']) ?>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'products-filter-form']) ?>
        <div class="products-search">
            <?= $this->Form->control('q', [
                'label' => false,
                'aria-label' => 'Search products',
                'placeholder' => 'Search by name or description',
                'value' => $q ?? '',
                'templates' => ['inputContainer' => '<div class="products-search-input">{{content}}</div>'],
            ]) ?>
            <?= $this->Form->button('Search', ['class' => 'products-search-button']) ?>
        </div>

        <div class="products-layout">
            <aside class="products-sidebar" aria-label="Product filters">
                <div class="filter-heading">
                    <p class="products-eyebrow">Refine</p>
                    <h2>Shop your way.</h2>
                </div>

                <?= $this->Form->control('category', [
                    'label' => 'Category',
                    'options' => $categoryOptions,
                    'value' => $category ?? '',
                ]) ?>

                <fieldset class="price-filter">
                    <legend>Price</legend>
                    <?php foreach ([
                        '' => 'All prices',
                        'under-100' => 'Under $100',
                        '100-250' => '$100 - $250',
                        'over-250' => 'Over $250',
                    ] as $priceValue => $priceLabel): ?>
                        <label>
                            <input
                                type="radio"
                                name="price"
                                value="<?= h($priceValue) ?>"
                                <?= ($price ?? '') === $priceValue ? 'checked' : '' ?>
                            >
                            <span><?= h($priceLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <?= $this->Form->button('Apply filters', ['class' => 'products-filter-button']) ?>
            </aside>
        </div>
    <?= $this->Form->end() ?>

    <div class="products-results-heading">
        <p><?= count($products) ?> pieces to explore</p>
    </div>

    <div class="product-grid">
    <?php if (count($products) === 0): ?>
        <div class="products-empty-state">
            <h3>No matching products</h3>
            <p>Try another search term or clear the filters to browse the collection.</p>
            <?= $this->Html->link('Clear filters', '/products', ['class' => 'products-cart-link']) ?>
        </div>
    <?php endif; ?>
    <?php foreach ($products as $product): ?>
        <?php
        $stockQty = (int)($product->inventory?->Quantity ?? 0);
        ?>
        <article class="product-card">
            <div class="product-card-image">
                <?= $this->Html->image($product->Image, [
                    'alt' => (string)$product->ProductName,
                ]) ?>
            </div>
            <div class="product-card-content">
                <p class="product-category"><?= h($product->product_category->CategoryName ?? 'Lighting') ?></p>
                <h3><?= h($product->ProductName) ?></h3>
                <p class="product-description"><?= h($product->Description) ?></p>
                <div class="product-card-footer">
                    <div class="price-tag">$<?= number_format((float)$product->Price, 2) ?></div>
                    <span class="product-stock"><?= $stockQty > 0 ? 'In stock' : 'Out of stock' ?></span>
                </div>
                <?php if ($stockQty > 0): ?>
                    <?= $this->Form->create(null, ['class' => 'product-action']) ?>
                    <?= $this->Form->hidden('product_id', ['value' => $product->ProductId]) ?>
                    <?= $this->Form->button('Add to Cart', ['class' => 'product-action-button']) ?>
                    <?= $this->Form->end() ?>
                <?php else: ?>
                    <div class="out-of-stock">Currently unavailable</div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</div>
