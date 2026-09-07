<?php
// templates/Checkout/index.php
?>

<h1>Checkout</h1>

<?= $this->Form->create() ?>

<?= $this->Form->control('GuestName') ?>

<?= $this->Form->control('Email') ?>

<?= $this->Form->control('Address') ?>

<?= $this->Form->control('Phone') ?>

<?= $this->Form->control('card_number', [
    'label' => 'Test Card Number',
    'value' => '4242424242424242',
]) ?>

<?= $this->Form->button('Complete Order') ?>

<?= $this->Form->end() ?>

<p>
    Test card:
</p>
