<?php

$this->assign('title', 'Suppliers');
?>

<h1 class="page-title">Suppliers</h1>

<?php if (count($suppliers) === 0): ?>
    <div class="panel-card">
        <div class="empty-state">
            No suppliers are available.
        </div>
    </div>
<?php else: ?>
    <div class="panel-card">
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Products</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td>
                            <?= $this->Html->link(
                                (string)$supplier->name,
                                ['action' => 'view', $supplier->id]
                            ) ?>
                        </td>
                        <td><?= h((string)$supplier->email) ?></td>
                        <td><?= h((string)$supplier->phone) ?></td>
                        <td>
                            <?php
                            $names = [];

                            foreach ($supplier->products as $product) {
                                $names[] = $product->name ?? ('#' . $product->id);
                            }

                            echo h(implode(', ', $names));
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
