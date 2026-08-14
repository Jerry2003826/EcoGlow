<?php
/**
 * Invoice list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\Invoice> $invoices
 * @var string $status
 * @var string $q
 * @var \Cake\I18n\Date $today
 * @var array<string, int> $statusCounts
 * @var int $overdueCount
 */

use App\Model\Entity\Invoice;

$statusCounts = $statusCounts ?? [];
$invoiceTotal = array_sum($statusCounts);
$issuedCount = $statusCounts[Invoice::STATUS_ISSUED] ?? 0;
$paidCount = $statusCounts[Invoice::STATUS_PAID] ?? 0;

$this->assign('title', 'Invoices');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Invoices']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Finance</span>
    <h1>Invoices</h1>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $invoiceTotal ?></span>
        <span class="eg-eyebrow">All invoices</span>
    </div>
    <a class="admin-stat-card<?= $status === Invoice::STATUS_OVERDUE ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => array_filter([
           'status' => Invoice::STATUS_OVERDUE,
           'q' => $q !== '' ? $q : null,
       ])]) ?>">
        <span class="admin-stat-value"><?= (int)$overdueCount ?></span>
        <span class="eg-eyebrow">Overdue</span>
    </a>
    <a class="admin-stat-card<?= $status === Invoice::STATUS_ISSUED ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => array_filter([
           'status' => Invoice::STATUS_ISSUED,
           'q' => $q !== '' ? $q : null,
       ])]) ?>">
        <span class="admin-stat-value"><?= $issuedCount ?></span>
        <span class="eg-eyebrow">Issued</span>
    </a>
    <a class="admin-stat-card<?= $status === Invoice::STATUS_PAID ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => array_filter([
           'status' => Invoice::STATUS_PAID,
           'q' => $q !== '' ? $q : null,
       ])]) ?>">
        <span class="admin-stat-value"><?= $paidCount ?></span>
        <span class="eg-eyebrow">Paid</span>
    </a>
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
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">
            All <span class="admin-chip-count"><?= $invoiceTotal ?></span>
        </a>
        <?php foreach (Invoice::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>">
                <?= h($label) ?>
                <span class="admin-chip-count"><?= $key === Invoice::STATUS_OVERDUE ? (int)$overdueCount : (int)($statusCounts[$key] ?? 0) ?></span>
            </a>
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
        <div class="admin-panel-head">
            <h2>GST invoices</h2>
            <p class="admin-panel-caption"><?= (int)$invoices->totalCount() ?> matching</p>
        </div>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Invoices">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('invoice_number', 'Invoice') ?></th>
                        <th class="text-end"><?= $this->Paginator->sort('grand_total_cents', 'Total') ?></th>
                        <th class="text-end"><?= $this->Paginator->sort('amount_paid_cents', 'Paid') ?></th>
                        <th class="text-end">Balance</th>
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th><?= $this->Paginator->sort('due_date', 'Due') ?></th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice) : ?>
                        <?php $overdue = $invoice->isOverdue($today); ?>
                        <tr class="<?= $overdue ? 'is-overdue' : '' ?>">
                            <td class="admin-identity-cell" data-label="Invoice">
                                <?= $this->element('admin/identity', [
                                    'title' => (string)($invoice->customer->label ?? 'Walk-in'),
                                    'code' => (string)$invoice->invoice_number,
                                    'meta' => $invoice->issue_date ? $invoice->issue_date->format('d M Y') : null,
                                ]) ?>
                            </td>
                            <td class="cell-qty" data-label="Total"><?= $this->Money->aud((int)$invoice->grand_total_cents) ?></td>
                            <td class="cell-qty" data-label="Paid"><?= $this->Money->aud((int)$invoice->amount_paid_cents) ?></td>
                            <td class="cell-qty" data-label="Balance"><?= $this->Money->aud((int)$invoice->balance_due_cents) ?></td>
                            <td data-label="Status">
                                <?= $this->element('admin/status_pill', [
                                    'status' => $overdue ? Invoice::STATUS_OVERDUE : $invoice->status,
                                    'label' => $overdue
                                        ? Invoice::statusLabels()[Invoice::STATUS_OVERDUE]
                                        : (Invoice::statusLabels()[$invoice->status] ?? $invoice->status),
                                    'toneOverride' => Invoice::statusTone($overdue ? Invoice::STATUS_OVERDUE : $invoice->status),
                                ]) ?>
                            </td>
                            <td class="text-nowrap" data-label="Due"><?= $invoice->due_date ? h($invoice->due_date->format('d M Y')) : '—' ?></td>
                            <td class="text-end admin-list-action" data-label="">
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
