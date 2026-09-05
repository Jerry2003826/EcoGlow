<?php

$this->assign('title', 'Edit Customer');

echo $this->Form->create($customer);
?>

<h1 class="page-title">Edit Customer</h1>

<div class="panel-card">
    <?= $this->Form->control('first_name') ?>
    <?= $this->Form->control('last_name') ?>
    <?= $this->Form->control('email') ?>
    <?= $this->Form->control('phone') ?>
    <?= $this->Form->control('address_line_1') ?>
    <?= $this->Form->control('address_line_2') ?>
    <?= $this->Form->control('suburb') ?>
    <?= $this->Form->control('state') ?>
    <?= $this->Form->control('postcode') ?>
    <?= $this->Form->control('country') ?>

    <?php if ($canViewAge): ?>
        <?= $this->Form->control('age') ?>
    <?php endif; ?>

    <?= $this->Form->button('Save Customer') ?>
</div>

<?= $this->Form->end() ?>
