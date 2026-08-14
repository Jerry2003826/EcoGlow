<?php
/**
 * Staff TOTP challenge.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Two-factor authentication');
?>
<div class="container py-5" style="max-width: 28rem;">
    <h1>Two-factor authentication</h1>
    <p>Enter the 6-digit code from your authenticator app, or a one-time recovery code.</p>
    <?= $this->Form->create(null) ?>
    <?= $this->Form->control('code', [
        'label' => 'Authentication code',
        'class' => 'form-control',
        'autocomplete' => 'one-time-code',
        'inputmode' => 'numeric',
        'required' => true,
    ]) ?>
    <div class="d-grid mt-3">
        <?= $this->Form->button(__('Continue'), ['class' => 'btn btn-eg-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
