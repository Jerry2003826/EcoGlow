<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Enquiry $enquiry
 */
use Cake\Core\Configure;


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
<link rel="stylesheet" href = "/css/enquiryPage/add.css" />
<div class="add-contact-page">
    <div class="contact">

        <div class="contact-content">
            <h1>
                Contact Us
            </h1>
            <h4>
                Have a question or need assistance?
            </h4>
            <p>
                Send us an enquiry and our team will be happy to help.
            </p>
        </div>

        <div class="contact-form">
            <?= $this->Form->create($enquiry) ?>
            <fieldset>
                <?php
                    echo $this->Form->control('first_name');
                    echo $this->Form->control('last_name');
                    echo $this->Form->control('email');                    
                    echo $this->Form->control('contact_number');
                    echo $this->Form->control('enquiry_message');
                ?>
                <div class="cf-turnstile"
                    data-theme="light"
                    data-callback="turnstileOnSuccess"
                    data-error-callback="turnstileOnError"
                    data-expired-callback="turnstileOnExpired"
                    data-timeout-callback="turnstileOnTimeout"
                    data-sitekey="<?= Configure::read('Captcha.turnstile.siteKey') ?>"
                ></div>
        <blockquote id="turnstile-message" style="display:none"></blockquote>
            </fieldset>
            <?= $this->Form->button(__('Submit') , ['class' => 'btn', 'disabled' => true])?>
            <?= $this->Form->end() ?>
        </div>

    </div>
</div>

<script>
    // Callbacks for Turnstile. Login button is disabled until Turnstile passes.
    var turnstileMessageBlock = document.querySelector('#turnstile-message');
    var actionButton = document.querySelector('button.btn');

    function turnstileOnSuccess(token) {
        turnstileMessageBlock.style.display = 'none';
        actionButton.removeAttribute('disabled');
    }

    function turnstileOnError(errorCode) {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge error. Please refresh the webpage and try again.";
        actionButton.setAttribute('disabled');
    }

    function turnstileOnExpired() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge token expired. Please validate again.";
        actionButton.setAttribute('disabled');
    }

    function turnstileOnTimeout() {
        turnstileMessageBlock.style.display = 'block';
        turnstileMessageBlock.innerText = "Challenge timed out. Please validate again.";
        actionButton.setAttribute('disabled');
    }
</script>
