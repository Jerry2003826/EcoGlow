<?php
// templates/Admin/Suppliers/view.php

$this->assign('title', $supplier->name);
?>

<h1 class="page-title"><?= h($supplier->name) ?></h1>

<div class="panel-card">
    <p><strong>Code:</strong> <?= h($supplier->code) ?></p>
    <p><strong>Email:</strong> <?= h((string)$supplier->email) ?></p>
    <p><strong>Phone:</strong> <?= h((string)$supplier->phone) ?></p>

    <p>
        <strong>Address:</strong>
        <?= h(trim(implode(', ', array_filter([
            $supplier->address_line_1,
            $supplier->suburb,
            $supplier->state,
            $supplier->postcode,
        ])))) ?>
    </p>

    <p><strong>Notes:</strong> <?= h((string)$supplier->notes) ?></p>
</div>

<div class="panel-card">
    <h2>Products</h2>

    <?php if (empty($supplier->products)): ?>
        <div class="empty-state">No products linked.</div>
    <?php else: ?>
        <ul>
            <?php foreach ($supplier->products as $product): ?>
                <li><?= h($product->name ?? ('#' . $product->id)) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="panel-card">
    <?= $this->Html->link('Edit Supplier', ['action' => 'edit', $supplier->id]) ?>
    <?= $this->Html->link('Back to Suppliers', ['action' => 'index']) ?>
</div>
