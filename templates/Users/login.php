<?php

use Cake\Core\Configure;

$this->assign('title', 'Login');

$this->Html->script('https://challenges.cloudflare.com/turnstile/v0/api.js', [
    'block' => true,
    'async' => true,
    'defer' => true,
]);
$this->Html->meta([
    'block' => true,
    'link' => 'https://challenges.cloudflare.com',
    'rel' => 'preconnect',
]);

$loginUrl = '/login';
$redirect = $this->request->getQuery('redirect');
if (is_string($redirect) && $redirect !== '') {
    $loginUrl .= '?redirect=' . rawurlencode($redirect);
}
?>
<link rel="stylesheet" href = "/css/login.css" />
<div class="login-page">
    <div class="login">
        <div class="login-content">
            <h1>Log In</h1>

            <div class ="login-image">
                <?= $this->Html->image(
                    'landing/pendants.jpg',
                    [
                        'class' => 'login-photo',
                        'alt' => 'Pendant lights in a modern interior',
                    ]
                ) ?>
            </div>
        </div>

         <div class="login-form">
            <?= $this->Flash->render() ?>
            <?= $this->Form->create(null, [
                'url' => $loginUrl,
            ]) ?>
            <fieldset>
                <?= $this->Form->control('email', [
                    'label' => 'Email',
                    'required' => true,
                    'type' => 'email',
                    'class' => 'form-control',
                ]) ?>
                <?= $this->Form->control('password', [
                    'label' => 'Password',
                    'required' => true,
                    'type' => 'password',
                    'class' => 'form-control',
                ]) ?>
                <div class="cf-turnstile"
                    data-size="flexible"
                    data-theme="light"
                    data-callback="turnstileOnSuccess"
                    data-error-callback="turnstileOnError"
                    data-expired-callback="turnstileOnExpired"
                    data-timeout-callback="turnstileOnTimeout"
                    data-sitekey="<?= Configure::read('Captcha.turnstile.siteKey') ?>"
            ></div>
            <blockquote id="turnstile-message" style="display:none"></blockquote>
            </fieldset>

            <?= $this->Form->button('Login', ['class' => 'btn', 'disabled' => true]); ?>
            <div class="signup-link">
                <span>Don't have an account?</span>

                <?= $this->Html->link(
                    'Sign Up',
                    ['action' => 'add'],
                    ['class' => 'signup-button']
                ) ?>
            </div>
            <?= $this->Form->end()?>
        </div>
        
    </div>

</div>

<script>
    var turnstileMessageBlock = document.querySelector('#turnstile-message');
    var actionButton = document.querySelector('button.btn');

    function turnstileOnSuccess(token) {
        turnstileMessageBlock.style.display = 'none';
        actionButton.removeAttribute('disabled');
    }

    function turnstileOnError(errorCode) {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge error. Please refresh the webpage and try again.";
    }

    function turnstileOnExpired() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge token expired. Please validate again.";
    }

    function turnstileOnTimeout() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge timed out. Please validate again.";
    }
</script>
