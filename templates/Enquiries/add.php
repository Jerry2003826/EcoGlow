<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Enquiry $enquiry
 */
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
            </fieldset>
            <?= $this->Form->button(__('Submit') , ['class' => 'btn'])?>
            <?= $this->Form->end() ?>
        </div>

    </div>
</div>
