<?php

$this->assign('title', 'Inventory');

$lowStockCount = count($items ?? []);
?>

<h1 class="page-title">Inventory</h1>

<?php if ($lowStockCount > 0): ?>
    <div class="panel-card">
        <strong>
            Low stock alert:
        </strong>
        <?= h((string)$lowStockCount) ?>
        item(s) have reached or fallen below their reorder threshold and may require replenishment.
    </div>
<?php endif; ?>

<?php if ($lowStockCount === 0): ?>
    <div class="panel-card">
        <div class="empty-state">
            No low stock items were found. Products that reach their reorder threshold will appear here.
        </div>
    </div>
<?php else: ?>
    <div class="panel-card">
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th>Location</th>
                    <th>Available</th>
                    <th>Reorder Point</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= h($item['product_name']) ?></td>
                        <td><?= h($item['variant_name']) ?></td>
                        <td><?= h($item['sku']) ?></td>
                        <td><?= h($item['location_name']) ?></td>
                        <td><?= h((string)$item['quantity_available']) ?></td>
                        <td><?= h((string)$item['reorder_point']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
