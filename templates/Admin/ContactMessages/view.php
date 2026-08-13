<?php
/**
 * Admin contact message detail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContactMessage $contactMessage
 */
$this->assign('title', 'Message: ' . $contactMessage->subject);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Messages', 'url' => ['action' => 'index']],
        ['label' => $contactMessage->subject],
    ],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Message</span>
        <h1>Message Details</h1>
    </div>
    <div class="d-flex gap-2">
        <?= $this->Html->link('Back to Messages', ['action' => 'index'], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
        <?= $this->Form->postLink(
            'Delete',
            ['action' => 'delete', $contactMessage->id],
            [
                'confirm' => __('Are you sure you want to delete this message?'),
                'class' => 'btn btn-eg-danger',
            ],
        ) ?>
    </div>
</div>

<div class="admin-detail">
    <div class="admin-panel">
        <span class="eg-eyebrow">Subject</span>
        <h2><?= h($contactMessage->subject) ?></h2>
        <div class="message-bubble mt-3">
            <?= nl2br(h($contactMessage->message)) ?>
        </div>
        <div class="mt-4">
            <a class="btn btn-eg-primary" href="mailto:<?= h($contactMessage->email) ?>?subject=<?= rawurlencode('Re: ' . $contactMessage->subject) ?>">
                Reply via email
            </a>
        </div>
    </div>
    <aside class="admin-panel">
        <h2>Sender</h2>
        <dl class="eg-kv-list">
            <div class="eg-kv-row">
                <dt>From</dt>
                <dd><?= h($contactMessage->name) ?></dd>
            </div>
            <div class="eg-kv-row">
                <dt>Email</dt>
                <dd><a href="mailto:<?= h($contactMessage->email) ?>"><?= h($contactMessage->email) ?></a></dd>
            </div>
            <div class="eg-kv-row">
                <dt>Phone</dt>
                <dd><?= $contactMessage->phone ? h($contactMessage->phone) : 'Not provided' ?></dd>
            </div>
            <div class="eg-kv-row">
                <dt>Received</dt>
                <dd><?= h($contactMessage->created->format('d M Y, H:i')) ?></dd>
            </div>
        </dl>
    </aside>
</div>
