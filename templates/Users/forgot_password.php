<?php
$this->assign('title', 'Forgot password');
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
            <h2>Forgot password</h2>
            <?= $this->Flash->render() ?>
            <?= $this->Form->create() ?>
            <fieldset>
                <?= $this->Form->control('email', [
                    'label' => 'Email',
                    'required' => true,
                    'type' => 'email',
                    'class' => 'form-control',
                ]) ?>
            </fieldset>
            <?= $this->Form->button('Send reset link', ['class' => 'btn']) ?>
            <p class="forgot-link">
                <?= $this->Html->link('Back to login', ['action' => 'login']) ?>
            </p>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
