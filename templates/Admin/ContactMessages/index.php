<?php
/**
 * Admin contact messages list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ContactMessage> $contactMessages
 * @var string $status
 * @var int $unreadCount
 * @var array<string, int> $statusCounts
 */

use App\Model\Entity\ContactMessage;

$statusCounts = $statusCounts ?? [];
$messageTotal = array_sum($statusCounts);
$statClass = static function (string $current, string $value): string {
    $class = 'admin-stat-card';
    if ($current === $value) {
        $class .= ' is-active';
    }

    return $class;
};

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

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= (int)$unreadCount ?></span>
        <span class="eg-eyebrow">Unread</span>
    </div>
    <a class="<?= h($statClass($status, ContactMessage::STATUS_NEW)) ?>"
       href="<?= $this->Url->build($statusUrl(ContactMessage::STATUS_NEW)) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ContactMessage::STATUS_NEW] ?? 0) ?></span>
        <span class="eg-eyebrow">New</span>
    </a>
    <a class="<?= h($statClass($status, ContactMessage::STATUS_IN_PROGRESS)) ?>"
       href="<?= $this->Url->build($statusUrl(ContactMessage::STATUS_IN_PROGRESS)) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ContactMessage::STATUS_IN_PROGRESS] ?? 0) ?></span>
        <span class="eg-eyebrow">In progress</span>
    </a>
    <a class="<?= h($statClass($status, ContactMessage::STATUS_RESOLVED)) ?>"
       href="<?= $this->Url->build($statusUrl(ContactMessage::STATUS_RESOLVED)) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ContactMessage::STATUS_RESOLVED] ?? 0) ?></span>
        <span class="eg-eyebrow">Resolved</span>
    </a>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">
            All <span class="admin-chip-count"><?= $messageTotal ?></span>
        </a>
        <?php foreach (ContactMessage::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>">
                <?= h($label) ?> <span class="admin-chip-count"><?= (int)($statusCounts[$key] ?? 0) ?></span>
            </a>
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
        <div class="admin-panel-head">
            <h2>Inbox</h2>
            <p class="admin-panel-caption"><?= (int)$contactMessages->totalCount() ?> matching</p>
        </div>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Contact messages">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('subject') ?></th>
                        <th><?= $this->Paginator->sort('created', 'Received') ?></th>
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th>Assigned to</th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactMessages as $message) : ?>
                        <?php $messageStatus = (string)($message->status ?: ContactMessage::STATUS_NEW); ?>
                        <tr class="<?= $message->is_read ? '' : 'row-unread' ?>">
                            <td class="admin-identity-cell" data-label="Subject">
                                <?= $this->element('admin/identity', [
                                    'title' => (string)$message->subject,
                                    'code' => null,
                                    'meta' => trim((string)$message->name . ' · ' . (string)$message->email),
                                ]) ?>
                            </td>
                            <td class="text-nowrap" data-label="Received"><?= h($message->created->format('d M Y, H:i')) ?></td>
                            <td data-label="Status">
                                <?= $this->element('admin/status_pill', [
                                    'status' => $messageStatus,
                                    'label' => ContactMessage::statusLabels()[$messageStatus] ?? $messageStatus,
                                    'toneOverride' => ContactMessage::statusTone($messageStatus),
                                ]) ?>
                            </td>
                            <td data-label="Assigned to"><?= h($message->assigned_user->email ?? 'Unassigned') ?></td>
                            <td class="text-end admin-list-action" data-label="">
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
