<?php

use Cake\Core\Configure;

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
?>

<link rel="stylesheet" href = "/css/login.css" />

<div class="login-page">
    <div class="login">
        <div class="login-content">
            <h1>Log In</h1>

            <div class ="login-image">
                <div class="image-placeholder">
                    Image
                </div>
            </div>
        </div>

         <div class="login-form">
            <?= $this->Flash->render() ?>
            <?= $this->Form->create() ?>
            <fieldset>
                <?= $this->Form->control('username', ['required' => true]) ?>
                <?= $this->Form->control('user_password', ['label' => 'Password', 'type' => 'password', 'required' => true]) ?>
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
            <?= $this->Form->end()?>
        </div>
        
    </div>

</div>

<script>
    // Callbacks for Turnstile. Login button is disabled until Turnstile passes.
    var turnstileMessageBlock = document.querySelector('#turnstile-message');
    var actionButton = document.querySelector('button.btn');

    function turnstileOnSuccess(token) {
        turnstileMessageBlock.style.display = 'none';
        actionButton.removeAttribute('disabled');
    }

    function turnstileOnError(errorCode) {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge error. Please refresh the webpage and try again.";
        actionButton.setAttribute('disabled');
    }

    function turnstileOnExpired() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge token expired. Please validate again.";
        actionButton.setAttribute('disabled');
    }

    function turnstileOnTimeout() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge timed out. Please validate again.";
        actionButton.setAttribute('disabled');
    }
</script>