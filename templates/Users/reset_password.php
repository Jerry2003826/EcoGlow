<?php
$this->assign('title', 'Reset password');
?>
<link rel="stylesheet" href="/css/login.css" />
<div class="login-page">
    <div class="login-background">
        <?= $this->Html->image('landing/login_picture.jpg', [
            'class' => 'login-background-image',
            'alt' => 'Login background image',
        ]) ?>
    </div>
    <div class="login-form">
        <div class="login-card">
            <h2>Reset password</h2>
            <?= $this->Flash->render() ?>
            <?= $this->Form->create() ?>
            <fieldset>
                <?= $this->Form->control('password', [
                    'label' => 'New password',
                    'required' => true,
                    'type' => 'password',
                    'class' => 'form-control',
                ]) ?>
                <?= $this->Form->control('password_confirm', [
                    'label' => 'Confirm password',
                    'required' => true,
                    'type' => 'password',
                    'class' => 'form-control',
                ]) ?>
            </fieldset>
            <?= $this->Form->button('Update password', ['class' => 'btn']) ?>
            <p class="forgot-link">
                <?= $this->Html->link('Back to login', ['action' => 'login']) ?>
            </p>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
