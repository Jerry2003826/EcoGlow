<?php
$this->assign('title', 'Edit account');
$this->Html->css('storefront', ['block' => true, 'timestamp' => true]);
$identity = $this->request->getAttribute('identity');
$role = (string)($identity?->get('UserRole') ?? '');
$isAdmin = $role === 'admin';
$backUrl = $isAdmin ? '/users' : ($role === 'staff' ? '/admin' : '/account');
?>

<div class="account-edit-card">
    <h1>Edit account</h1>
    <p>Update your login details. Leave the password blank to keep your current password.</p>

    <?= $this->Form->create($user) ?>
    <?= $this->Form->control('Username', [
        'label' => 'Username',
        'required' => true,
        'autocomplete' => 'username',
    ]) ?>
    <?= $this->Form->control('Email', [
        'label' => 'Email',
        'type' => 'email',
        'required' => true,
        'autocomplete' => 'email',
    ]) ?>
    <?= $this->Form->control('Password', [
        'label' => 'New password (optional)',
        'type' => 'password',
        'value' => '',
        'required' => false,
        'minlength' => 8,
        'autocomplete' => 'new-password',
    ]) ?>

    <?php if ($isAdmin): ?>
        <?= $this->Form->control('UserRole', [
            'label' => 'Role',
            'options' => ['customer' => 'Customer', 'staff' => 'Staff', 'admin' => 'Admin'],
        ]) ?>
        <?= $this->Form->control('Status', [
            'label' => 'Status',
            'options' => ['active' => 'Active', 'disabled' => 'Disabled'],
        ]) ?>
    <?php endif; ?>

    <div class="account-edit-actions">
        <?= $this->Form->button('Save changes') ?>
        <?= $this->Html->link('Cancel', $backUrl) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
