<?php

$this->assign('title', (string)($message->reference_number ?: 'Message'));
?>

<h1 class="page-title"><?= h((string)($message->reference_number ?: 'Message')) ?></h1>

<div class="panel-card">
    <p><strong>Reference:</strong> <?= h((string)($message->reference_number ?: $message->id)) ?></p>
    <p><strong>Name:</strong> <?= h($message->name) ?></p>
    <p><strong>Email:</strong> <?= h($message->email) ?></p>
    <p><strong>Phone:</strong> <?= h((string)$message->phone) ?></p>
    <p><strong>Subject:</strong> <?= h($message->subject) ?></p>
    <p><strong>Created:</strong> <?= h((string)$message->created) ?></p>
    <p><strong>Message:</strong></p>
    <p><?= nl2br(h((string)$message->message)) ?></p>

    <?= $this->Html->link(
        'Back to Messages',
        ['action' => 'index']
    ) ?>
</div>
