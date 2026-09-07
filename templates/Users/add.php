<?php

?>
<div class="row" style="display: flex; justify-content: center; align-items: center; padding: 20px;">
    <div class="column column-50">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __('Customer Sign Up') ?></legend>
                <?php
                    echo $this->Form->control('Username');
                    echo $this->Form->control('Password', ['type' => 'password', 'required' => true]);
                    echo $this->Form->control('Email', ['type' => 'email']);
                    echo $this->Form->control('customer.CustomerName', ['label' => 'Full name']);
                    echo $this->Form->control('customer.Phone');
                    echo $this->Form->control('customer.Address');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
