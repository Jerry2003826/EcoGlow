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
?>
<div class="container py-5 account-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Account</li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Your account</span>
        <h1 class="section-title">Profile</h1>
    </div>

    <?= $this->element('account/nav', ['current' => 'index']) ?>

    <div class="eg-card p-4 p-md-5 reveal" style="max-width: 36rem;">
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
</div>
