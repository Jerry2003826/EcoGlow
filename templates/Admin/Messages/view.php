<?php
// templates/Admin/Messages/view.php

$this->assign('title', 'Enquiry #' . $message->EnquiryId);
?>

<div class="panel-card">
    <h1>Enquiry #<?= (int)$message->EnquiryId ?></h1>
    <?= $this->Form->create(null, [
        'url' => ['action' => 'updateStatus', $message->EnquiryId],
        'class' => 'order-status-form',
    ]) ?>
    <?= $this->Form->control('Status', [
        'label' => 'Status',
        'value' => (string)($message->Status ?? 'New'),
        'options' => [
            'New' => 'New',
            'Pending' => 'Pending',
            'Resolved' => 'Resolved',
        ],
    ]) ?>
    <?= $this->Form->button('Update status') ?>
    <?= $this->Form->end() ?>
    <p><strong>Name:</strong> <?= h($message->Name) ?></p>
    <p><strong>Email:</strong>
        <?php if (!empty($message->Email)): ?>
            <?= $this->Html->link(
                (string)$message->Email,
                'mailto:' . $message->Email
            ) ?>
        <?php endif; ?>
    </p>
    <p><strong>Subject:</strong> <?= h($message->Subject) ?></p>
    <p><strong>Created:</strong> <?= h((string)$message->CreatedDate) ?></p>
    <p><?= nl2br(h((string)$message->Message)) ?></p>
    <?= $this->Html->link('Back to Messages', '/admin/messages', ['class' => 'admin-back-link']) ?>
</div>
