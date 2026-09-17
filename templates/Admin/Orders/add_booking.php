<?php
// templates/Admin/Orders/add_booking.php
?>
<h1 class="page-title">Create Booking</h1>

<div class="panel-card">
<?= $this->Form->create() ?>
<?= $this->Form->control('first_name') ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->control('phone') ?>
<?= $this->Form->control('address_line_1') ?>
<?= $this->Form->control('service_type') ?>
<?= $this->Form->control('scheduled_at') ?>
<?= $this->Form->control('notes') ?>
<?= $this->Form->button('Save') ?>
<?= $this->Form->end() ?>
</div>
