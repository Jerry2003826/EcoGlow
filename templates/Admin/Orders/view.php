<?php
// templates/Admin/Orders/view.php
?>
<h1 class="page-title"><?= h($order->order_number) ?></h1>

<div class="panel-card">
    <p><?= h($order->customer?->first_name) ?> <?= h($order->customer?->last_name) ?></p>
    <p><?= h($order->customer?->email) ?></p>
    <p><?= h($order->customer?->phone) ?></p>
    <p><?= h($order->customer?->address_line_1) ?></p>
    <p><?= h($order->customer?->suburb) ?></p>
    <p><?= h($order->customer?->state) ?></p>
    <p><?= h($order->customer?->postcode) ?></p>
</div>

<?php if ($canManageOrders): ?>
<div class="panel-card">
    <?= $this->Form->create() ?>
    <?= $this->Form->hidden('form_name', ['value' => 'status']) ?>
    <?= $this->Form->control('status', ['value' => $order->status]) ?>
    <?= $this->Form->button('Update Status') ?>
    <?= $this->Form->end() ?>
</div>

<div class="panel-card">
    <?= $this->Form->create() ?>
    <?= $this->Form->hidden('form_name', ['value' => 'delivery']) ?>
    <?= $this->Form->control('promised_delivery_on', ['value' => $order->promised_delivery_on]) ?>
    <?= $this->Form->control('delivery_notes', ['value' => $order->delivery_notes]) ?>
    <?= $this->Form->button('Update Delivery') ?>
    <?= $this->Form->end() ?>
</div>
<?php endif; ?>
