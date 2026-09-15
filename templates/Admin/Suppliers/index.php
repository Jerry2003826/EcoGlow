<?php
// templates/Admin/Suppliers/index.php

$this->assign('title', 'Suppliers');
?>

<h1 class="page-title">Suppliers</h1>
<p class="page-lead">
    <?= $this->Html->link('Add supplier', ['action' => 'add'], ['class' => 'admin-green-button']) ?>
</p>

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
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?= h($supplier->SupplierName) ?></td>
                    <td>
                        <?php if (!empty($supplier->Email)): ?>
                            <?= $this->Html->link(
                                (string)$supplier->Email,
                                'mailto:' . $supplier->Email
                            ) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($supplier->Phone)): ?>
                            <?= $this->Html->link(
                                (string)$supplier->Phone,
                                'tel:' . preg_replace('/\s+/', '', (string)$supplier->Phone)
                            ) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($supplier->Address) ?></td>
                    <td><?= count($supplier->products ?? []) ?></td>
                    <td>
                        <?= $this->Html->link(
                            'Edit',
                            ['action' => 'edit', $supplier->SupplierId]
                        ) ?>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $supplier->SupplierId],
                            ['confirm' => 'Delete this supplier?']
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card">
    <h2>Contact supplier</h2>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'contact'],
        'class' => 'admin-form',
    ]) ?>

    <?= $this->Form->control('supplier_id', [
        'label' => 'Supplier',
        'options' => $supplierOptions ?? [],
        'required' => true,
    ]) ?>

    <?= $this->Form->control('subject', [
        'label' => 'Subject',
        'required' => true,
    ]) ?>

    <?= $this->Form->control('message', [
        'label' => 'Message',
        'type' => 'textarea',
        'rows' => 8,
        'required' => true,
    ]) ?>

    <?= $this->Form->button('Send Email') ?>
    <?= $this->Form->end() ?>
</div>
