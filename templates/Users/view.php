<?php
$this->assign('title', 'User account');
$this->Html->css('storefront', ['block' => true, 'timestamp' => true]);
?>

<div class="account-edit-card">
    <h1>User account</h1>
    <dl class="user-details">
        <div><dt>ID</dt><dd><?= (int)$user->UserId ?></dd></div>
        <div><dt>Username</dt><dd><?= h($user->Username) ?></dd></div>
        <div><dt>Email</dt><dd><?= h($user->Email) ?></dd></div>
        <div><dt>Role</dt><dd><?= h(ucfirst((string)$user->UserRole)) ?></dd></div>
        <div><dt>Status</dt><dd><?= h(ucfirst((string)$user->Status)) ?></dd></div>
    </dl>
    <p>To deactivate this account, choose Edit account and change its Status to Disabled.</p>
    <div class="account-edit-actions">
        <?= $this->Html->link('Edit account', ['action' => 'edit', $user->UserId], ['class' => 'button']) ?>
        <?= $this->Html->link('Back to users', ['action' => 'index']) ?>
    </div>
</div>
