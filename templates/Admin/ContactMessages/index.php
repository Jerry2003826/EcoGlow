<?php
/**
 * Admin contact messages list view.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ContactMessage> $contactMessages
 * @var int $unreadCount
 */
$this->assign('title', 'Contact Messages');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Contact Messages</h1>
    <?php if ($unreadCount > 0) : ?>
        <span class="badge text-bg-warning fs-6"><?= $unreadCount ?> unread</span>
    <?php endif; ?>
</div>

<?php if (count($contactMessages) === 0) : ?>
    <div class="alert alert-info">No messages yet. New enquiries from the contact form will appear here.</div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('created', 'Received') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('email') ?></th>
                    <th><?= $this->Paginator->sort('subject') ?></th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contactMessages as $message) : ?>
                    <tr class="<?= $message->is_read ? '' : 'table-warning' ?>">
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
                        <td>
                            <?php if ($message->is_read) : ?>
                                <span class="badge text-bg-secondary">Read</span>
                            <?php else : ?>
                                <span class="badge text-bg-warning">New</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?= $this->Html->link('View', ['action' => 'view', $message->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?= $this->Form->postLink(
                                'Delete',
                                ['action' => 'delete', $message->id],
                                [
                                    'confirm' => __('Are you sure you want to delete this message?'),
                                    'class' => 'btn btn-sm btn-outline-danger',
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Messages pagination">
        <ul class="pagination justify-content-center">
            <?= $this->Paginator->first('« First', ['class' => 'page-link']) ?>
            <?= $this->Paginator->prev('‹ Prev', ['class' => 'page-link']) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next ›', ['class' => 'page-link']) ?>
            <?= $this->Paginator->last('Last »', ['class' => 'page-link']) ?>
        </ul>
    </nav>
    <p class="text-center text-muted small"><?= $this->Paginator->counter('Page {{page}} of {{pages}}, showing {{current}} of {{count}} messages') ?></p>
<?php endif; ?>
