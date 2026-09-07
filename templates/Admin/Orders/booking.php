<?php
// templates/Admin/Orders/booking.php
?>
<h1 class="page-title"><?= h($booking->booking_number) ?></h1>

<div class="panel-card">
    <?= $this->Form->create() ?>
    <?= $this->Form->control('status', ['value' => $booking->status]) ?>
    <?= $this->Form->control('scheduled_at', ['value' => $booking->scheduled_at]) ?>
    <?= $this->Form->control('notes', ['value' => $booking->notes]) ?>
    <?= $this->Form->button('Update Booking') ?>
    <?= $this->Form->end() ?>
</div>
