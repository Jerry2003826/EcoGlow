<?php
/**
 * Admin login view.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Admin Login');
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Eco Glow Admin</h1>
                <p class="text-muted text-center small mb-4">Sign in to manage contact messages</p>
                <?= $this->Form->create(null) ?>
                <div class="mb-3">
                    <?= $this->Form->control('email', [
                        'label' => 'Email',
                        'class' => 'form-control',
                        'required' => true,
                        'autofocus' => true,
                    ]) ?>
                </div>
                <div class="mb-3">
                    <?= $this->Form->control('password', [
                        'label' => 'Password',
                        'class' => 'form-control',
                        'required' => true,
                    ]) ?>
                </div>
                <div class="d-grid">
                    <?= $this->Form->button(__('Sign in'), ['class' => 'btn btn-primary']) ?>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
