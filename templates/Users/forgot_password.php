<?php
/**
 * Request a password reset link — night-glow brand theme.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Forgot Password');
?>
<div class="container">
    <div class="auth-wrap">
        <div class="glass-card auth-card reveal">
            <div class="auth-bulb" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18h6"/>
                    <path d="M10 21h4"/>
                    <path d="M12 3a6 6 0 0 0-3.5 10.9c.8.6 1.5 1.6 1.5 2.6V17h4v-.5c0-1 .7-2 1.5-2.6A6 6 0 0 0 12 3z"/>
                    <path d="M12 7v2"/>
                </svg>
            </div>
            <h1 class="h3 mb-1 text-center">Forgot your password?</h1>
            <p class="text-muted text-center small mb-4">
                Enter the email address on your account and we will send you a reset link.
            </p>
            <?= $this->Form->create(null) ?>
            <div class="mb-4">
                <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    <?= $this->Form->control('email', [
                        'type' => 'email',
                        'label' => ['text' => 'Email', 'class' => 'visually-hidden'],
                        'placeholder' => 'Email',
                        'class' => 'form-control',
                        'required' => true,
                        'autofocus' => true,
                    ]) ?>
                </div>
            </div>
            <div class="d-grid">
                <?= $this->Form->button(__('Send reset link'), ['class' => 'btn btn-glow btn-lg']) ?>
            </div>
            <?= $this->Form->end() ?>
            <p class="text-center small mt-4 mb-0">
                <?= $this->Html->link(__('Back to sign in'), ['action' => 'login'], ['class' => 'auth-link']) ?>
            </p>
        </div>
    </div>
</div>
