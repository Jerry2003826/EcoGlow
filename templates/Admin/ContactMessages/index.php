<?php
/**
 * Admin contact messages list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ContactMessage> $contactMessages
 * @var string $status
 * @var int $unreadCount
 */

use App\Model\Entity\ContactMessage;

$this->assign('title', 'Contact Messages');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Messages']],
]));

$statusUrl = function (?string $value): array {
    $query = array_filter(['status' => $value]);

    return ['action' => 'index', '?' => $query];
};
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

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">All</a>
        <?php foreach (ContactMessage::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </div>
</form>

<?php if (count($contactMessages) === 0) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No messages in this view',
            'body' => 'Enquiries from the public contact form will appear here so staff can assign, reply and close them.',
        ]) ?>
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
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th>Assigned to</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactMessages as $message) : ?>
                        <?php $messageStatus = (string)($message->status ?: ContactMessage::STATUS_NEW); ?>
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
                            <td>
                                <?= $this->element('admin/status_pill', [
                                    'status' => $messageStatus,
                                    'label' => ContactMessage::statusLabels()[$messageStatus] ?? $messageStatus,
                                    'toneOverride' => ContactMessage::statusTone($messageStatus),
                                ]) ?>
                            </td>
                            <td><?= h($message->assigned_user->email ?? 'Unassigned') ?></td>
                            <td class="text-end text-nowrap">
                                <?= $this->Html->link('View', ['action' => 'view', $message->id], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->element('admin/pagination', [
        'label' => 'Messages pagination',
        'counter' => 'Page {{page}} of {{pages}}, showing {{current}} of {{count}} messages',
    ]) ?>
<?php endif; ?>
