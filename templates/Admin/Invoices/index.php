<?php
/**
 * Invoice list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\Invoice> $invoices
 * @var string $status
 * @var string $q
 * @var \Cake\I18n\Date $today
 */

use App\Model\Entity\Invoice;

$this->assign('title', 'Invoices');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Invoices']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Finance</span>
    <h1>Invoices</h1>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <?php
        $statusUrl = function (?string $value) use ($q): array {
            return ['action' => 'index', '?' => array_filter([
                'status' => $value,
                'q' => $q !== '' ? $q : null,
            ])];
        };
        ?>
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">All</a>
        <?php foreach (Invoice::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="admin-filter-row">
        <div class="admin-field admin-field-search">
            <label for="q">Search</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= h($q) ?>" placeholder="Invoice or customer">
        </div>
        <?php if ($status !== '') : ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-eg-ghost">Apply filters</button>
    </div>
</form>

<?php if (count($invoices) === 0) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No invoices match those filters',
            'body' => 'Issue an invoice from an order detail page. Line items are copied from the order snapshot, so later price edits cannot change them.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="table table-eg table-hover align-middle" aria-label="Invoices">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('invoice_number', 'Invoice') ?></th>
                        <th>Customer</th>
                        <th><?= $this->Paginator->sort('grand_total_cents', 'Total') ?></th>
                        <th><?= $this->Paginator->sort('amount_paid_cents', 'Paid') ?></th>
                        <th>Balance</th>
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th><?= $this->Paginator->sort('issue_date', 'Issued') ?></th>
                        <th><?= $this->Paginator->sort('due_date', 'Due') ?></th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice) : ?>
                        <?php $overdue = $invoice->isOverdue($today); ?>
                        <tr class="<?= $overdue ? 'is-overdue' : '' ?>">
                            <td class="cell-id"><?= h($invoice->invoice_number) ?></td>
                            <td><?= h($invoice->customer->label ?? 'Walk-in') ?></td>
                            <td><?= $this->Money->aud((int)$invoice->grand_total_cents) ?></td>
                            <td><?= $this->Money->aud((int)$invoice->amount_paid_cents) ?></td>
                            <td><?= $this->Money->aud((int)$invoice->balance_due_cents) ?></td>
                            <td>
                                <?= $this->element('admin/status_pill', [
                                    'status' => $overdue ? Invoice::STATUS_OVERDUE : $invoice->status,
                                    'label' => $overdue
                                        ? Invoice::statusLabels()[Invoice::STATUS_OVERDUE]
                                        : (Invoice::statusLabels()[$invoice->status] ?? $invoice->status),
                                    'toneOverride' => Invoice::statusTone($overdue ? Invoice::STATUS_OVERDUE : $invoice->status),
                                ]) ?>
                            </td>
                            <td class="text-nowrap"><?= $invoice->issue_date ? h($invoice->issue_date->format('d M Y')) : '—' ?></td>
                            <td class="text-nowrap"><?= $invoice->due_date ? h($invoice->due_date->format('d M Y')) : '—' ?></td>
                            <td class="text-end">
                                <?= $this->Html->link('View', ['action' => 'view', $invoice->id], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= $this->element('admin/pagination', [
        'label' => 'Invoices pagination',
        'counter' => 'Page {{page}} of {{pages}}, showing {{current}} of {{count}} invoices',
    ]) ?>
<?php endif; ?>
