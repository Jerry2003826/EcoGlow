<?php
/**
 * Confirm sign-out. GET /logout must not destroy the session.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Log out');
?>
<div class="container">
    <div class="auth-wrap">
        <div class="eg-card auth-card reveal">
            <h1 class="h3 mb-1 text-center">Log out</h1>
            <p class="text-muted text-center small mb-4">Sign out of your Eco Glow account?</p>
            <?= $this->Form->create(null) ?>
            <div class="d-grid">
                <?= $this->Form->button(__('Log out'), ['class' => 'btn btn-eg-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
            <p class="text-center small mb-0 mt-3">
                <?= $this->Html->link(__('Cancel'), '/', ['class' => 'auth-link']) ?>
            </p>
        </div>
    </div>
</div>
