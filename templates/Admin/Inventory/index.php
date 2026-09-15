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
                <th>Supplier</th>
                <th>On hand</th>
                <th>Low stock at</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                    $outOfStock = (int)$item->Quantity === 0;
                    $low = (int)$item->Quantity <= (int)$item->LowStockThreshold; ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $item->product->ProductName ?? '',
                            ['action' => 'edit', $item->ProductId],
                            ['class' => 'inventory-product-link']
                        ) ?>
                    </td>
                    <td><?= h($item->product->supplier->SupplierName ?? '') ?></td>
                    <td><?= (int)$item->Quantity ?></td>
                    <td><?= (int)$item->LowStockThreshold ?></td>
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
                            ['confirm' => 'Delete this category?']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>Add category</h2>
    <?= $this->Form->create(null, [
        'url' => ['action' => 'addCategory'],
        'class' => 'admin-form',
    ]) ?>
    <?= $this->Form->control('CategoryName', [
        'label' => 'Category name',
        'required' => true,
    ]) ?>
    <?= $this->Form->button('Save category', ['class' => 'admin-green-button']) ?>
    <?= $this->Form->end() ?>
</div>
