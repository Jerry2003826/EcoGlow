<?php
/**
 * Admin contact messages list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ContactMessage> $contactMessages
 * @var int $unreadCount Set by AppController::beforeRender, shared with the nav badge.
 */
$this->assign('title', 'Contact Messages');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Messages']],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Inbox</span>
        <h1>Contact Messages</h1>
    </div>
    <?php if ($unreadCount > 0) : ?>
        <span class="pill pill-new"><?= $unreadCount ?> unread</span>
    <?php endif; ?>
</div>

<?php if (count($contactMessages) === 0) : ?>
    <div class="admin-panel text-center">
        <h2 class="h4">No messages yet.</h2>
        <p class="text-muted mb-0">New enquiries from the contact form will appear here.</p>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="table table-eg table-hover align-middle" aria-label="Contact messages">
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
