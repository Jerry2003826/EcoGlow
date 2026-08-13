<?php
/**
 * Customer registration — warm-earth storefront theme.
 *
 * Age is not collected. See docs/database/customer-contact-and-age.md.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', 'Create an Account');
$this->Html->css('account', ['block' => true]);
?>
<div class="container">
    <nav aria-label="Breadcrumb" class="pt-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Create account</li>
        </ol>
    </nav>

    <div class="auth-wrap">
        <div class="eg-card auth-card reveal">
            <div class="auth-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h1 class="h3 mb-1 text-center">Create your account</h1>
            <p class="text-muted text-center small mb-4">
                Track orders, save finishes and book installation in one place.
            </p>

            <?= $this->Form->create($user ?? null, ['url' => '/register']) ?>
            <div class="mb-3">
                <?= $this->Form->control('name', [
                    'type' => 'text',
                    'label' => 'Full name',
                    'class' => 'form-control',
                    'autocomplete' => 'name',
                    'required' => true,
                    'value' => $this->getRequest()->getData('name'),
                ]) ?>
            </div>
            <div class="mb-3">
                <?= $this->Form->control('email', [
                    'type' => 'email',
                    'label' => 'Email',
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'required' => true,
                ]) ?>
            </div>
            <div class="mb-3">
                <?= $this->Form->control('phone', [
                    'type' => 'tel',
                    'label' => 'Phone',
                    'class' => 'form-control',
                    'autocomplete' => 'tel',
                    'required' => true,
                    'value' => $this->getRequest()->getData('phone'),
                ]) ?>
            </div>
            <div class="mb-3">
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'label' => 'Password',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'minlength' => 8,
                    'aria-describedby' => 'password-help',
                    'required' => true,
                    'value' => '',
                ]) ?>
                <p class="form-text mb-0" id="password-help">At least 8 characters.</p>
            </div>
            <div class="mb-4">
                <?= $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'label' => 'Confirm password',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'minlength' => 8,
                    'required' => true,
                    'value' => '',
                ]) ?>
                <?php if (!empty($user) && $user->getError('confirm_password')) : ?>
                    <div class="error-message"><?= h(implode(' ', $user->getError('confirm_password'))) ?></div>
                <?php endif; ?>
            </div>
            <div class="d-grid">
                <?= $this->Form->button(__('Create account'), [
                    'class' => 'btn btn-eg-primary',
                ]) ?>
            </div>
            <?= $this->Form->end() ?>

            <p class="eg-note mb-0">
                Already have an account?
                <a href="<?= $this->Url->build('/account/login') ?>">Sign in</a>.
            </p>
        </div>
    </div>
</div>
