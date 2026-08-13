<?php
/**
 * Choose a new password from a reset link — night-glow brand theme.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string|null $token The plain-text reset token, posted back with the form.
 * @var int $minPasswordLength
 */
$this->assign('title', 'Reset Password');
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
            <h1 class="h3 mb-1 text-center">Choose a new password</h1>
            <p class="text-muted text-center small mb-4">
                Pick something at least <?= h((string)$minPasswordLength) ?> characters long.
            </p>
            <?= $this->Form->create($user, ['url' => ['action' => 'resetPassword', $token]]) ?>
            <div class="mb-3">
                <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <?= $this->Form->control('password', [
                        'type' => 'password',
                        'label' => ['text' => 'New password', 'class' => 'visually-hidden'],
                        'placeholder' => 'New password',
                        'class' => 'form-control',
                        'required' => true,
                        'autocomplete' => 'new-password',
                        'autofocus' => true,
                        'value' => '',
                    ]) ?>
                </div>
            </div>
            <div class="mb-4">
                <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><path d="m9 16 2 2 4-4"/></svg>
                    <?= $this->Form->control('confirm_password', [
                        'type' => 'password',
                        'label' => ['text' => 'Confirm new password', 'class' => 'visually-hidden'],
                        'placeholder' => 'Confirm new password',
                        'class' => 'form-control',
                        'required' => true,
                        'autocomplete' => 'new-password',
                        'value' => '',
                    ]) ?>
                </div>
            </div>
            <div class="d-grid">
                <?= $this->Form->button(__('Update password'), ['class' => 'btn btn-glow btn-lg']) ?>
            </div>
            <?= $this->Form->end() ?>
            <p class="text-center small mt-4 mb-0">
                <?= $this->Html->link(__('Back to sign in'), ['action' => 'login'], ['class' => 'auth-link']) ?>
            </p>
        </div>
    </div>
</div>
