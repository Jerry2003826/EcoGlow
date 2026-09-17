<?php
// templates/Admin/Quotations/add.php

$this->assign('title', 'New quotation');

$customerOptions = [];

foreach ($customers as $customer) {
    $customerOptions[(int)$customer->CustomerId] = trim(
        (string)$customer->CustomerName . ' — ' . (string)$customer->Email
    );
}
?>

<h1 class="page-title">New quotation</h1>
<p class="page-lead">
    Choose an existing customer from the database, then enter the quote total and notes.
</p>

<?php if ($customerOptions === []): ?>
    <div class="panel-card">
        <p>No customers are available yet. A customer record is created when they register or check out.</p>
        <p><?= $this->Html->link('Go to Customers', '/admin/customers', ['class' => 'admin-back-link']) ?></p>
    </div>
<?php else: ?>
    <div class="panel-card">
        <?= $this->Form->create(null, ['class' => 'admin-form']) ?>
        <?= $this->Form->control('CustomerId', [
            'label' => 'Customer',
            'options' => $customerOptions,
            'empty' => 'Select a customer',
            'required' => true,
        ]) ?>
        <?= $this->Form->control('TotalAmount', [
            'label' => 'Total AUD (GST inclusive)',
            'type' => 'number',
            'step' => '0.01',
            'min' => '0',
            'required' => true,
        ]) ?>
        <?= $this->Form->control('Notes', [
            'label' => 'Line items and notes',
            'type' => 'textarea',
            'rows' => 8,
        ]) ?>
        <div class="form-actions">
            <?= $this->Form->button('Save and email quotation', ['class' => 'admin-green-button']) ?>
            <?= $this->Html->link('Back to Quotations', '/admin/quotations', ['class' => 'admin-back-link']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
<?php endif; ?>
