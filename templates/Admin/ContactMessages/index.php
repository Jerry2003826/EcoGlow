<?php
/**
 * Admin contact messages list view — warm-earth storefront theme.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ContactMessage> $contactMessages
 * @var int $unreadCount Set by AppController::beforeRender, shared with the nav badge.
 */
$this->assign('title', 'Contact Messages');
?>
<div class="container py-5">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Messages</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 reveal">
        <div>
            <span class="eg-eyebrow">Inbox</span>
            <h1 class="section-title h2 mb-0">Contact Messages</h1>
        </div>
        <?php if ($unreadCount > 0) : ?>
            <span class="pill pill-new"><?= $unreadCount ?> unread</span>
        <?php endif; ?>
    </div>

    <?php if (count($contactMessages) === 0) : ?>
        <div class="eg-card p-5 text-center reveal">
            <div class="auth-mark mx-auto" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
            </div>
            <h2 class="h4">No messages yet &mdash; the floor is yours.</h2>
            <p class="text-muted mb-0">New enquiries from the contact form will appear here.</p>
        </div>
    <?php else : ?>
        <div class="admin-panel reveal">
            <div class="table-responsive">
                <table class="table table-eg table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('created', 'Received') ?></th>
                            <th><?= $this->Paginator->sort('name') ?></th>
                            <th><?= $this->Paginator->sort('email') ?></th>
                            <th><?= $this->Paginator->sort('subject') ?></th>
                            <th>Message</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contactMessages as $message) : ?>
                            <tr class="<?= $message->is_read ? '' : 'row-unread' ?>">
                                <td class="text-nowrap"><?= h($message->created->format('d M Y, H:i')) ?></td>
                                <td><?= h($message->name) ?></td>
                                <td><a href="mailto:<?= h($message->email) ?>"><?= h($message->email) ?></a></td>
                                <td>
                                    <?php if (!$message->is_read) : ?>
                                        <strong><?= h($message->subject) ?></strong>
                                    <?php else : ?>
                                        <?= h($message->subject) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-message"><?= h($this->Text->truncate($message->message, 60)) ?></td>
                                <td>
                                    <?php if ($message->is_read) : ?>
                                        <span class="pill pill-read">Read</span>
                                    <?php else : ?>
                                        <span class="pill pill-new">New</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <?= $this->Html->link('View', ['action' => 'view', $message->id], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                                    <?= $this->Form->postLink(
                                        'Delete',
                                        ['action' => 'delete', $message->id],
                                        [
                                            'confirm' => __('Are you sure you want to delete this message?'),
                                            'class' => 'btn btn-eg-danger',
                                        ],
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <nav aria-label="Messages pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?= $this->Paginator->first('« First', ['class' => 'page-link']) ?>
                <?= $this->Paginator->prev('‹ Prev', ['class' => 'page-link']) ?>
                <?= $this->Paginator->numbers() ?>
                <?= $this->Paginator->next('Next ›', ['class' => 'page-link']) ?>
                <?= $this->Paginator->last('Last »', ['class' => 'page-link']) ?>
            </ul>
        </nav>
        <p class="text-center small text-muted"><?= $this->Paginator->counter('Page {{page}} of {{pages}}, showing {{current}} of {{count}} messages') ?></p>
    <?php endif; ?>
</div>
