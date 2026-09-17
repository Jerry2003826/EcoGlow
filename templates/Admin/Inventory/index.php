<?php
// templates/Admin/Inventory/index.php

$this->assign('title', 'Inventory');
?>

<h1 class="page-title">Inventory</h1>
<p class="page-lead">
    <?= $this->Html->link('Add product', '/admin/products/add', ['class' => 'admin-green-button']) ?>
</p>

<div class="panel-card">
    <p>Products at or below the low-stock threshold are highlighted.</p>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>On hand</th>
                <th>Low stock at</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($products ?? []) as $product): ?>
                <?php
                    $quantity = (int)($product->inventory->Quantity ?? 0);
                    $threshold = (int)($product->inventory->LowStockThreshold ?? 0);
                    $outOfStock = $quantity === 0;
                    $low = $quantity <= $threshold;
                ?>
                <tr
                    class="admin-clickable-row"
                    data-href="/admin/products/edit/<?= (int)$product->ProductId ?>"
                >
                    <td><?= h($product->ProductName) ?></td>
                    <td><?= h($product->product_category->CategoryName ?? '') ?></td>
                    <td><?= h($product->supplier->SupplierName ?? '') ?></td>
                    <td>$<?= number_format((float)$product->Price, 2) ?></td>
                    <td><?= $quantity ?></td>
                    <td><?= $threshold ?></td>
                    <td>
                        <?php if ($outOfStock): ?>
                            <span class="status-out-of-stock">
                                Out of Stock
                            </span>
                        <?php elseif ($low): ?>
                            <span class="status-low">
                                Low stock
                            </span>
                        <?php else: ?>
                            <span class="status-ok">
                                In stock
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $this->Html->link(
                            'Stock',
                            ['action' => 'edit', $product->ProductId],
                            ['class' => 'admin-action-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            'Delete',
                            '/admin/products/delete/' . (int)$product->ProductId,
                            ['confirm' => 'Delete this product?', 'class' => 'admin-delete-link']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card">
    <h2>Product categories</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Category</th>
                <th>Products</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($categories ?? []) as $category): ?>
                <tr>
                    <td><?= h($category->CategoryName) ?></td>
                    <td><?= count($category->products ?? []) ?></td>
                    <td>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'deleteCategory', $category->CategoryId],
                            ['confirm' => 'Delete this category?', 'class' => 'admin-delete-link']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card">
    <h2>Add category</h2>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'addCategory'],
        'class' => 'admin-category-form',
    ]) ?>

    <?= $this->Form->control('CategoryName', [
        'label' => 'Category name',
        'required' => true,
    ]) ?>

    <?= $this->Form->button(
        'Save category',
        ['class' => 'admin-green-button']
    ) ?>

    <?= $this->Form->end() ?>
</div>