<?php
// templates/Admin/Inventory/index.php

$this->assign('title', 'Inventory');
?>

<h1 class="page-title">Inventory</h1>

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
