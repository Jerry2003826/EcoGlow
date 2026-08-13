<?php
/**
 * Request a password reset link — warm-earth storefront theme.
 *
 * @var \App\View\AppView $this
 * @var string $loginPath Sign-in path matching the area that started this reset.
 */
$loginPath = $loginPath ?? '/login';
$this->assign('title', 'Forgot Password');
?>
<div class="container">
    <div class="auth-wrap">
        <div class="eg-card auth-card reveal">
            <div class="auth-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-10 6L2 7"/>
                </svg>
            </div>
            <h1 class="h3 mb-1 text-center">Forgot your password?</h1>
            <p class="text-muted text-center small mb-4">
                Enter the email address on your account and we will send you a reset link.
            </p>
            <?= $this->Form->create(null) ?>
            <?php if ($loginPath === '/account/login') : ?>
                <?= $this->Form->hidden('from', ['value' => 'customer']) ?>
            <?php endif; ?>
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
                <?= $this->Form->button(__('Send reset link'), ['class' => 'btn btn-eg-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
            <p class="text-center small mt-4 mb-0">
                <?= $this->Html->link(__('Back to sign in'), $loginPath, ['class' => 'auth-link']) ?>
            </p>
        </div>
    </div>
</div>
