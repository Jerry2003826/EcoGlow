<?php
// templates/Admin/Products/index.php

$this->assign('title', 'Products');
?>

<h1 class="page-title">Products</h1>
<p class="page-lead">
    <?= $this->Html->link('Add product', ['action' => 'add'], ['class' => 'admin-green-button']) ?>
    <?= $this->Html->link('Manage categories', '/admin/inventory', ['class' => 'admin-back-link']) ?>
</p>


<div class="panel-card">
    <p>
        <?= $this->Html->link(
            'Add Product',
            ['action' => 'add'],
            ['class' => 'admin-green-button']
        ) ?>
    </p>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Stock</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr class="admin-table-row-link" data-row-link="<?= h($this->Url->build(['action' => 'edit', $product->ProductId])) ?>" tabindex="0">
                    <td><a href="<?= h($this->Url->build(['action' => 'edit', $product->ProductId])) ?>"><?= h($product->ProductName) ?></a></td>
                    <td><?= h($product->product_category->CategoryName ?? '') ?></td>
                    <td><?= h($product->supplier->SupplierName ?? '') ?></td>
                    <td>$<?= number_format((float)$product->Price, 2) ?></td>
                    <td><?= (int)($product->inventory->Quantity ?? 0) ?></td>
                    <td>
                        <?= $this->Html->link(
                            'Edit',
                            ['action' => 'edit', $product->ProductId]
                        ) ?>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $product->ProductId],
                            ['confirm' => 'Delete this product?']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
