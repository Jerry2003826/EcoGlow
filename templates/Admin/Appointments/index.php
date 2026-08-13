<?php
/**
 * Staff list of installation and repair requests.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\ServiceRequest> $requests
 * @var string $status
 */

use App\Model\Entity\ServiceRequest;

$labels = ServiceRequest::statusLabels();
$this->assign('title', 'Appointments');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Appointments']],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Services</span>
        <h1>Appointments</h1>
    </div>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build(['action' => 'index']) ?>">All</a>
        <?php foreach ($labels as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>"
               href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => $key]]) ?>">
                <?= h($label) ?>
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
        <div class="table-responsive">
            <table class="table table-eg table-hover align-middle" aria-label="Service requests">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Preferred date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request) : ?>
                        <tr>
                            <td class="cell-id"><?= h($request->request_number) ?></td>
                            <td><?= h($request->customer->label ?? $request->contact_name) ?></td>
                            <td><?= h($request->service_type->name ?? '') ?></td>
                            <td><?= $this->element('admin/status_pill', ['status' => $request->status]) ?></td>
                            <td class="text-nowrap">
                                <?= $request->preferred_date ? h($request->preferred_date->format('d M Y')) : '—' ?>
                            </td>
                            <td class="text-end">
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
