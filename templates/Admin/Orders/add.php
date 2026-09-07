<?php
// templates/Admin/Orders/add.php
?>
<h1 class="page-title">Create Order</h1>

<div class="panel-card">
<?= $this->Form->create() ?>
<?= $this->Form->control('first_name') ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->control('phone') ?>
<?= $this->Form->control('address_line_1') ?>
<?= $this->Form->button('Save') ?>
<?= $this->Form->end() ?>
</div>
