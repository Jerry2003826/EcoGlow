<?php
/**
 * Staff list of installation and repair requests.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ServiceRequest> $requests
 * @var string $status
 * @var array<string, int> $statusCounts
 */

use App\Model\Entity\ServiceRequest;

$labels = ServiceRequest::statusLabels();
$statusCounts = $statusCounts ?? [];
$requestTotal = array_sum($statusCounts);

$this->assign('title', 'Appointments');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Appointments']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Services</span>
    <h1>Appointments</h1>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $requestTotal ?></span>
        <span class="eg-eyebrow">All requests</span>
    </div>
    <a class="admin-stat-card<?= $status === ServiceRequest::STATUS_NEW ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => ServiceRequest::STATUS_NEW]]) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ServiceRequest::STATUS_NEW] ?? 0) ?></span>
        <span class="eg-eyebrow">Awaiting confirmation</span>
    </a>
    <a class="admin-stat-card<?= $status === ServiceRequest::STATUS_SCHEDULED ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => ServiceRequest::STATUS_SCHEDULED]]) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ServiceRequest::STATUS_SCHEDULED] ?? 0) ?></span>
        <span class="eg-eyebrow">Scheduled</span>
    </a>
    <a class="admin-stat-card<?= $status === ServiceRequest::STATUS_IN_PROGRESS ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => ServiceRequest::STATUS_IN_PROGRESS]]) ?>">
        <span class="admin-stat-value"><?= (int)($statusCounts[ServiceRequest::STATUS_IN_PROGRESS] ?? 0) ?></span>
        <span class="eg-eyebrow">In progress</span>
    </a>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'index']) ?>">
            All <span class="admin-chip-count"><?= $requestTotal ?></span>
        </a>
        <?php foreach ($labels as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>"
               href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => $key]]) ?>">
                <?= h($label) ?> <span class="admin-chip-count"><?= (int)($statusCounts[$key] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</form>

<?php if (count($requests) === 0) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No service requests yet',
            'body' => 'Customer installation and repair bookings appear here for confirmation and scheduling.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2>Service requests</h2>
            <p class="admin-panel-caption"><?= (int)$requests->totalCount() ?> matching</p>
        </div>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Service requests">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Preferred date</th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request) : ?>
                        <tr>
                            <td class="admin-identity-cell" data-label="Request">
                                <?= $this->element('admin/identity', [
                                    'title' => (string)($request->customer->label ?? $request->contact_name),
                                    'code' => (string)$request->request_number,
                                    'meta' => null,
                                ]) ?>
                            </td>
                            <td data-label="Type"><?= h($request->service_type->name ?? '') ?></td>
                            <td data-label="Status"><?= $this->element('admin/status_pill', ['status' => $request->status]) ?></td>
                            <td class="text-nowrap" data-label="Preferred date">
                                <?= $request->preferred_date ? h($request->preferred_date->format('d M Y')) : '—' ?>
                            </td>
                            <td class="text-end admin-list-action" data-label="">
                                <?= $this->Html->link(
                                    'View',
                                    ['action' => 'view', $request->id],
                                    ['class' => 'btn btn-sm btn-eg-ghost'],
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= $this->element('admin/pagination', [
        'label' => 'Appointments pagination',
        'counter' => 'Page {{page}} of {{pages}}, showing {{current}} of {{count}} requests',
    ]) ?>
<?php endif; ?>
