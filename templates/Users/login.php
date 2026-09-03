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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow-sm">
                <div class="card-body">

                    <h1 class="h3 mb-4">
                        Employee Login
                    </h1>

                    <?= $this->Flash->render() ?>

                    <?= $this->Form->create(null, [
                        'url' => $loginUrl,
                    ]) ?>

                    <div class="mb-3">
                        <?= $this->Form->control('email', [
                            'label' => 'Email',
                            'required' => true,
                            'type' => 'email',
                            'class' => 'form-control',
                        ]) ?>
                    </div>

                    <div class="mb-3">
                        <?= $this->Form->control('password', [
                            'label' => 'Password',
                            'required' => true,
                            'type' => 'password',
                            'class' => 'form-control',
                        ]) ?>
                    </div>

                    <div class="mb-3">
                        <div
                            class="cf-turnstile"
                            data-size="flexible"
                            data-theme="light"
                            data-callback="turnstileOnSuccess"
                            data-error-callback="turnstileOnError"
                            data-expired-callback="turnstileOnExpired"
                            data-timeout-callback="turnstileOnTimeout"
                            data-sitekey="<?= h((string)Configure::read('Captcha.turnstile.siteKey')) ?>">
                        </div>
                        <blockquote id="turnstile-message" style="display:none"></blockquote>
                    </div>

                    <div>
                        <?= $this->Form->button('Sign In', [
                            'class' => 'btn btn-primary',
                        ]) ?>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>

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
