<?php

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __('Sign Up') ?></legend>
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
