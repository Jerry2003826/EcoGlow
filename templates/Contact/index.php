<?php
/**
 * Public contact form view — warm-earth storefront theme.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 * @var string $recaptchaSitekey
 * @var bool $recaptchaEnabled
 */
$this->assign('title', 'Contact Us');
?>
<div class="container py-5">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Contact</li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5 align-items-stretch">
        <div class="col-lg-5 reveal">
            <div class="contact-aside">
                <div>
                    <span class="eg-eyebrow">Say hello</span>
                    <h1 class="section-title h2 mb-3">Contact Eco Glow Lighting</h1>
                    <p class="text-muted mb-0">
                        Questions about a product, an installation, or a repair? Send us a message and
                        we will get back to you as soon as possible.
                    </p>
                </div>

                <div class="d-flex flex-column mt-2">
                    <div class="kv">
                        <span class="kv-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg>
                        </span>
                        <span>
                            <span class="kv-label d-block">Phone</span>
                            <span class="kv-value">(03) 9000 0000</span>
                        </span>
                    </div>
                    <div class="kv">
                        <span class="kv-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        </span>
                        <span>
                            <span class="kv-label d-block">Email</span>
                            <span class="kv-value">hello@ecoglow.example</span>
                        </span>
                    </div>
                    <div class="kv">
                        <span class="kv-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        </span>
                        <span>
                            <span class="kv-label d-block">Hours</span>
                            <span class="kv-value">Mon &ndash; Sat, 8am &ndash; 6pm</span>
                        </span>
                    </div>
                </div>

                <p class="text-muted mb-0 mt-auto small">
                    Every message lands directly with Jordan &mdash; no call centres, no bots.
                </p>
            </div>
        </div>

        <div class="col-lg-7 reveal" data-reveal-step="1">
            <div class="eg-card h-100 p-4 p-lg-5">
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
                        <div class="mb-4">
                            <span class="recaptcha-frame">
                                <span class="g-recaptcha d-block" data-sitekey="<?= h($recaptchaSitekey) ?>" data-theme="light"></span>
                            </span>
                        </div>
                        <?= $this->Html->script('https://www.google.com/recaptcha/api.js', ['async' => true, 'defer' => true, 'block' => 'scriptBottom']) ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="d-grid gap-2 d-sm-flex">
                    <?= $this->Form->button(__('Send message'), ['class' => 'btn btn-eg-primary flex-sm-fill']) ?>
                    <button type="reset" class="btn btn-eg-ghost"><?= __('Clear') ?></button>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
