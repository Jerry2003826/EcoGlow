<?php
/**
 * Public contact form view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 * @var string $recaptchaSitekey
 * @var bool $recaptchaEnabled
 */
$this->assign('title', 'Contact Us');
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="mb-2">Contact Eco Glow Lighting</h1>
        <p class="text-muted mb-4">
            Questions about a product, an installation, or a repair? Send us a message and
            we will get back to you as soon as possible.
        </p>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?= $this->Form->create($contactMessage) ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <?= $this->Form->control('name', [
                            'label' => 'Your Name',
                            'class' => 'form-control',
                            'required' => true,
                        ]) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <?= $this->Form->control('email', [
                            'label' => 'Email',
                            'class' => 'form-control',
                            'required' => true,
                        ]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <?= $this->Form->control('phone', [
                            'label' => 'Phone (optional)',
                            'class' => 'form-control',
                            'required' => false,
                        ]) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <?= $this->Form->control('subject', [
                            'label' => 'Subject',
                            'class' => 'form-control',
                            'required' => true,
                        ]) ?>
                    </div>
                </div>
                <div class="mb-3">
                    <?= $this->Form->control('message', [
                        'type' => 'textarea',
                        'label' => 'Message',
                        'class' => 'form-control',
                        'rows' => 5,
                        'required' => true,
                    ]) ?>
                </div>

                <?php if ($recaptchaEnabled) : ?>
                    <?php if ($recaptchaSitekey === '') : ?>
                        <div class="alert alert-warning">
                            reCAPTCHA is enabled but no site key is configured.
                            Please set <code>Recaptcha.sitekey</code> in <code>config/app_local.php</code>.
                        </div>
                    <?php else : ?>
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= h($recaptchaSitekey) ?>"></div>
                        </div>
                        <?= $this->Html->script('https://www.google.com/recaptcha/api.js', ['async' => true, 'defer' => true, 'block' => 'scriptBottom']) ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="d-grid">
                    <?= $this->Form->button(__('Send Message'), ['class' => 'btn btn-warning btn-lg']) ?>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
