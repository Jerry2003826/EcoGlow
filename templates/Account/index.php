<?php
/**
 * Customer profile.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', 'Your Account');
$this->Html->css('account', ['block' => true]);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Account']],
]));
?>
<div class="eg-page-head eg-page-head-start">
    <span class="eg-eyebrow">Your account</span>
    <h1 class="section-title">Profile</h1>
</div>

<div class="eg-card p-4 p-md-5" style="max-width: 36rem;">
        <?= $this->Form->create($customer) ?>
        <div class="mb-3">
            <?= $this->Form->control('first_name', [
                'label' => 'First name',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'given-name',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('last_name', [
                'label' => 'Last name',
                'class' => 'form-control',
                'autocomplete' => 'family-name',
            ]) ?>
        </div>
        <div class="mb-3">
            <?= $this->Form->control('email', [
                'type' => 'email',
                'label' => 'Email',
                'class' => 'form-control',
                'required' => true,
                'autocomplete' => 'email',
            ]) ?>
        </div>
        <?php if ($user->get('pending_email')) : ?>
            <p class="text-muted">Waiting to confirm <?= h((string)$user->get('pending_email')) ?>.</p>
        <?php endif; ?>
        <?php if ($user->get('email_verified_at') === null) : ?>
            <p class="text-muted">Please confirm your email before completing checkout.</p>
            <?= $this->Form->postLink(
                __('Resend confirmation email'),
                '/account/resend-verification',
                ['class' => 'btn btn-eg-ghost mb-3'],
            ) ?>
        <?php endif; ?>
        <div class="mb-3">
            <?= $this->Form->control('current_password', [
                'type' => 'password',
                'label' => 'Current password (required to change email)',
                'class' => 'form-control',
                'required' => false,
                'autocomplete' => 'current-password',
                'value' => '',
            ]) ?>
        </div>
        <div class="mb-4">
            <?= $this->Form->control('phone', [
                'type' => 'tel',
                'label' => 'Phone',
                'class' => 'form-control',
                'autocomplete' => 'tel',
            ]) ?>
        </div>
        <div class="d-grid">
            <?= $this->Form->button(__('Save details'), ['class' => 'btn btn-eg-primary']) ?>
        </div>
        <?= $this->Form->end() ?>
</div>
