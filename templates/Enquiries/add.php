<?php
// templates/Enquiries/add.php

use Cake\Core\Configure;

$this->assign('title', 'Contact Us');
$this->Html->css('enquiryPage/add', ['block' => true, 'timestamp' => true]);
$this->Html->script('turnstile-form', ['block' => true, 'timestamp' => true]);
$this->Html->script('https://challenges.cloudflare.com/turnstile/v0/api.js', [
    'block' => true,
    'async' => true,
    'defer' => true,
]);

$this->Html->meta([
    'block' => true,
    'link' => 'https://challenges.cloudflare.com',
    'rel' => 'preconnect',
]);
?>

<div class="add-contact-page">
    <div class="add-contact-background">
        <?= $this->Html->image(
        'landing/login_picture.jpg',
        [
            'class' => 'add-contact-background-image',
            'alt' => 'Add contact background image',
        ]
    ) ?>
    </div>
    <div class="contact">
        <div class="contact-content">
            <h1>Contact Us</h1>
            <h4>Have a question or need assistance?</h4>
            <p>Send us an enquiry and our team will be happy to help.</p>
        </div>

        <div class="contact-form">
            <?= $this->Form->create($enquiry) ?>

            <fieldset>
                <?php
                    echo $this->Form->control('Name');
                    echo $this->Form->control('Email');
                    echo $this->Form->control('Subject');
                    echo $this->Form->control('Message', [
                        'type' => 'textarea',
                        'rows' => 5,
                    ]);
                ?>

                <div
                    class="cf-turnstile"
                    data-size="flexible"
                    data-theme="light"
                    data-callback="turnstileOnSuccess"
                    data-error-callback="turnstileOnError"
                    data-expired-callback="turnstileOnExpired"
                    data-timeout-callback="turnstileOnTimeout"
                    data-sitekey="<?= Configure::read('Captcha.turnstile.siteKey') ?>"
                ></div>

                <blockquote id="turnstile-message" role="status" aria-live="polite" hidden></blockquote>
            </fieldset>

            <?= $this->Form->button(
                __('Submit'),
                [
                    'class' => 'btn',
                    'disabled' => true,
                ]
            ) ?>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
