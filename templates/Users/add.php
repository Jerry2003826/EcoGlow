<?php
// templates/Users/add.php

$this->assign('title', 'Create Account');
$this->Html->css('storefront', ['block' => true, 'timestamp' => true]);

$errors = $errors ?? [];
?>

<div class="checkout-card signup-card">
    <h2>Create Account</h2>

    <?php if (!empty($errors['CustomerName'])): ?>
        <p class="error">Please enter your name.</p>
    <?php endif; ?>

    <?php if (!empty($errors['Email'])): ?>
        <p class="error">Please enter your email address.</p>
    <?php endif; ?>

    <?= $this->Form->create($user) ?>

    <?= $this->Form->control('Username') ?>
    <?= $this->Form->control('Password', ['type' => 'password', 'required' => true]) ?>
    <?= $this->Form->control('Email', ['type' => 'email']) ?>
    <?= $this->Form->control('customer.CustomerName', ['label' => 'Full name']) ?>
    <?= $this->Form->control('customer.Phone', [
        'label' => 'Phone',
        'inputmode' => 'numeric',
        'pattern' => '[0-9]+',
    ]) ?>
    <?= $this->Form->control('customer.Address', ['label' => 'Address']) ?>

    <?= $this->Form->button('Create Account') ?>

    <?= $this->Form->end() ?>
</div>
