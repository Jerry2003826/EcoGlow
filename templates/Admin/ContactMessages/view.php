<?php
/**
 * Admin contact message detail view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 */
$this->assign('title', 'Message: ' . $contactMessage->subject);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Message Details</h1>
    <div class="d-flex gap-2">
        <?= $this->Html->link('Back to Messages', ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?= $this->Form->postLink(
            'Delete',
            ['action' => 'delete', $contactMessage->id],
            [
                'confirm' => __('Are you sure you want to delete this message?'),
                'class' => 'btn btn-outline-danger',
            ]
        ) ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h2 class="h4"><?= h($contactMessage->subject) ?></h2>
        <dl class="row mt-4">
            <dt class="col-sm-3">From</dt>
            <dd class="col-sm-9"><?= h($contactMessage->name) ?></dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><a href="mailto:<?= h($contactMessage->email) ?>"><?= h($contactMessage->email) ?></a></dd>

            <dt class="col-sm-3">Phone</dt>
            <dd class="col-sm-9"><?= $contactMessage->phone ? h($contactMessage->phone) : '<span class="text-muted">Not provided</span>' ?></dd>

            <dt class="col-sm-3">Received</dt>
            <dd class="col-sm-9"><?= h($contactMessage->created->format('d M Y, H:i')) ?></dd>

            <dt class="col-sm-3">Message</dt>
            <dd class="col-sm-9">
                <div class="border rounded p-3 bg-light"><?= nl2br(h($contactMessage->message)) ?></div>
            </dd>
        </dl>
        <a class="btn btn-warning" href="mailto:<?= h($contactMessage->email) ?>?subject=<?= rawurlencode('Re: ' . $contactMessage->subject) ?>">
            Reply via Email
        </a>
    </div>
</div>
