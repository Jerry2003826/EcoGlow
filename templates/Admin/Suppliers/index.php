<?php
// templates/Admin/Suppliers/index.php

$this->assign('title', 'Suppliers');
?>

<h1 class="page-title">Suppliers</h1>

<div class="panel-card">
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Supplier</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Products</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?= h($supplier->SupplierName) ?></td>
                    <td><?= h($supplier->Email) ?></td>
                    <td><?= h($supplier->Phone) ?></td>
                    <td><?= h($supplier->Address) ?></td>
                    <td><?= count($supplier->products ?? []) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
