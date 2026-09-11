<?php

use Cake\Core\Configure;

$this->assign('title', 'Login');

$this->Html->css('login', ['block' => true, 'timestamp' => true]);
$this->Html->script('turnstile-form', ['block' => true, 'timestamp' => true]);
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
<div class="login-page">
    <div class="login-background">
        <?= $this->Html->image(
        'landing/login_picture.jpg',
        [
            'class' => 'login-background-image',
            'alt' => 'Login background image',
        ]
    ) ?>
    </div>
    <div class="login-form">
         <div class="login-card">
             <h2>Log in</h2>
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
            <blockquote id="turnstile-message" role="status" aria-live="polite" hidden></blockquote>
            </fieldset>

            <?= $this->Form->button('Login', ['class' => 'btn', 'disabled' => true]); ?>
            <p class="forgot-link">
                <a href="/forgot-password">Forgot password?</a>
            </p>
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
