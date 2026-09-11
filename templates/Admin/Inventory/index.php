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
                <?php $low = (int)$item->Quantity <= (int)$item->LowStockThreshold; ?>
                <tr>
                    <td><?= h($item->product->ProductName ?? '') ?></td>
                    <td><?= h($item->product->supplier->SupplierName ?? '') ?></td>
                    <td><?= (int)$item->Quantity ?></td>
                    <td><?= (int)$item->LowStockThreshold ?></td>
                    <td>
                        <span class="<?= $low ? 'status-low' : 'status-ok' ?>">
                            <?= $low ? 'Low stock' : 'OK' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
