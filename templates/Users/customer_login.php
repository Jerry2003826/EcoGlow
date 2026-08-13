<?php
/**
 * Customer login — same visual as staff login, different destination.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Sign In');
$this->Html->css('account', ['block' => true]);
?>
<div class="container">
    <div class="auth-wrap">
        <div class="eg-card auth-card reveal">
            <div class="auth-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h1 class="h3 mb-1 text-center">Welcome back</h1>
            <p class="text-muted text-center small mb-4">Sign in to your Eco Glow account</p>
            <?= $this->Form->create(null) ?>
            <div class="mb-3">
                <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    <?= $this->Form->control('email', [
                        'label' => ['text' => 'Email', 'class' => 'visually-hidden'],
                        'placeholder' => 'Email',
                        'class' => 'form-control',
                        'required' => true,
                        'autofocus' => true,
                    ]) ?>
                </div>
            </div>
            <div class="mb-4">
                <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <?= $this->Form->control('password', [
                        'label' => ['text' => 'Password', 'class' => 'visually-hidden'],
                        'placeholder' => 'Password',
                        'class' => 'form-control',
                        'required' => true,
                    ]) ?>
                </div>
            </div>
            <div class="d-grid">
                <?= $this->Form->button(__('Sign in'), ['class' => 'btn btn-eg-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
            <p class="text-center small mb-0 mt-2">
                <?= $this->Html->link(__('Forgot password?'), '/forgot-password', ['class' => 'auth-link']) ?>
            </p>
            <p class="eg-note mb-0 mt-3">
                New here?
                <a href="<?= $this->Url->build('/register') ?>">Create an account</a>.
            </p>
        </div>
    </div>
</div>
