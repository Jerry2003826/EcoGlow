<?php

$this->assign('title', 'Issue Invoice');

echo $this->Form->create($invoice);
?>

<h1 class="page-title">Issue Invoice</h1>

<div class="panel-card">
    <select name="sales_order_id">
        <?php foreach ($orders as $order): ?>
            <option value="<?= (int)$order->id ?>">
                <?= h($order->order_number) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?= $this->Form->button('Issue Invoice') ?>
</div>

<?= $this->Form->end() ?>
