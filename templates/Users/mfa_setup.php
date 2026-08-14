<?php
/**
 * Staff TOTP enrolment.
 *
 * @var \App\View\AppView $this
 * @var string $secret
 * @var string $otpauth
 * @var list<string> $recoveryCodes
 */
$this->assign('title', 'Set up two-factor authentication');
$recoveryCodes = $recoveryCodes ?? [];
?>
<div class="container py-5" style="max-width: 32rem;">
    <h1>Set up two-factor authentication</h1>
    <?php if ($recoveryCodes !== []) : ?>
        <p>Store these recovery codes in a safe place. Each code works once.</p>
        <ul class="font-monospace">
            <?php foreach ($recoveryCodes as $code) : ?>
                <li><?= h($code) ?></li>
            <?php endforeach; ?>
        </ul>
        <p><a class="btn btn-eg-primary" href="/admin">Continue to the staff area</a></p>
    <?php else : ?>
    <p>Add this account to your authenticator app, then enter a code to confirm.</p>
    <p class="mb-2"><strong>Secret</strong></p>
    <p class="font-monospace"><?= h($secret) ?></p>
    <p class="small text-muted text-break"><?= h($otpauth) ?></p>
    <?= $this->Form->create(null) ?>
    <?= $this->Form->control('code', [
        'label' => 'Authentication code',
        'class' => 'form-control',
        'autocomplete' => 'one-time-code',
        'inputmode' => 'numeric',
        'required' => true,
    ]) ?>
    <div class="d-grid mt-3">
        <?= $this->Form->button(__('Enable two-factor authentication'), ['class' => 'btn btn-eg-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
    <?php endif; ?>
</div>
