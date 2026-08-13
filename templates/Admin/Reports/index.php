<?php
/**
 * Operating reports.
 *
 * @var \App\View\AppView $this
 * @var string $preset
 * @var string $from
 * @var string $to
 * @var string $sort
 * @var string $direction
 * @var int $ordersTotal
 * @var int $grossSales
 * @var int $taxCents
 * @var int $average
 * @var int $estimatedGrossProfit
 * @var int $cogsCents
 * @var array<int, array<string, mixed>> $channels
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $transactions
 */

use App\Model\Entity\SalesOrder;
use Cake\I18n\DateTime;

$this->assign('title', 'Reports');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Reports']],
]));

$sortUrl = function (string $column) use ($preset, $from, $to, $sort, $direction): array {
    $next = $sort === $column && $direction === 'ASC' ? 'desc' : 'asc';

    return ['action' => 'index', '?' => [
        'preset' => $preset,
        'from' => $from,
        'to' => $to,
        'sort' => $column,
        'direction' => $next,
    ]];
};
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Finance</span>
    <h1>Reports</h1>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Date range">
        <?php foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Custom'] as $key => $label) : ?>
            <a class="eg-chip<?= $preset === $key ? ' is-active' : '' ?>"
               href="<?= $this->Url->build(['action' => 'index', '?' => ['preset' => $key]]) ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="admin-filter-row">
        <div class="admin-field">
            <label for="from">From date</label>
            <input type="date" class="form-control" id="from" name="from" value="<?= h($from) ?>" lang="en">
        </div>
        <div class="admin-field">
            <label for="to">To date</label>
            <input type="date" class="form-control" id="to" name="to" value="<?= h($to) ?>" lang="en">
        </div>
        <input type="hidden" name="preset" value="custom">
        <button type="submit" class="btn btn-eg-ghost">Apply dates</button>
    </div>
</form>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= (int)$ordersTotal ?></span>
        <span class="eg-eyebrow">Orders</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud((int)$grossSales) ?></span>
        <span class="eg-eyebrow">Sales (GST inclusive)</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud((int)$average) ?></span>
        <span class="eg-eyebrow">Average order (GST inclusive)</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud((int)$estimatedGrossProfit) ?></span>
        <span class="eg-eyebrow">estimated gross profit</span>
    </div>
</div>

<p class="admin-note mb-4">
    Sales figures are GST inclusive. GST included in the range: <?= $this->Money->aud((int)$taxCents) ?>.
    Profit is labelled <strong>estimated gross profit</strong> because cost snapshots are estimates, not a full COGS ledger.
</p>

<div class="admin-split">
    <section class="admin-section">
        <div class="admin-panel">
            <div class="admin-panel-head"><h2>By channel</h2></div>
            <?php if ($channels === []) : ?>
                <?= $this->element('admin/empty', [
                    'title' => 'No channel split',
                    'body' => 'Orders in this date range will break down by phone, email, SMS, in store and web.',
                ]) ?>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-eg align-middle" aria-label="Sales by channel">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Orders</th>
                                <th>Sales (GST inclusive)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($channels as $row) : ?>
                                <tr>
                                    <td><?= h(SalesOrder::channelLabels()[$row['source_channel']] ?? $row['source_channel']) ?></td>
                                    <td><?= (int)$row['order_count'] ?></td>
                                    <td><?= $this->Money->aud((int)$row['sales_cents']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="admin-section">
        <div class="admin-panel">
            <div class="admin-panel-head"><h2>By category</h2></div>
            <?php if ($categories === []) : ?>
                <?= $this->element('admin/empty', [
                    'title' => 'No category split',
                    'body' => 'Line items in this date range will group by catalogue category.',
                ]) ?>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-eg align-middle" aria-label="Sales by category">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Lines</th>
                                <th>Sales (GST inclusive)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $row) : ?>
                                <tr>
                                    <td><?= h($row['category_name']) ?></td>
                                    <td><?= (int)$row['line_count'] ?></td>
                                    <td><?= $this->Money->aud((int)$row['sales_cents']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="admin-section">
    <div class="admin-panel">
        <div class="admin-panel-head"><h2>Recent transactions</h2></div>
        <?php if ($transactions === []) : ?>
            <?= $this->element('admin/empty', [
                'title' => 'No transactions in this range',
                'body' => 'Orders, payments and refunds dated in the selected Melbourne calendar days will list here. Empty ranges show as 0, not an error.',
            ]) ?>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-eg align-middle" aria-label="Transactions">
                    <thead>
                        <tr>
                            <th><?= $this->Html->link('Reference', $sortUrl('reference')) ?></th>
                            <th><?= $this->Html->link('Customer', $sortUrl('customer')) ?></th>
                            <th><?= $this->Html->link('Amount', $sortUrl('amount')) ?></th>
                            <th><?= $this->Html->link('Status', $sortUrl('status')) ?></th>
                            <th><?= $this->Html->link('Date', $sortUrl('occurred_at')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $row) : ?>
                            <tr>
                                <td class="cell-ref"><?= h($row['reference_number'] ?: '—') ?></td>
                                <td><?= h($row['customer_name'] ?: 'Walk-in') ?></td>
                                <td><?= $this->Money->aud((int)$row['amount_cents']) ?></td>
                                <td><?= $this->element('admin/status_pill', ['status' => (string)$row['status']]) ?></td>
                                <td class="text-nowrap">
                                    <?= $row['occurred_at']
                                        ? h((new DateTime($row['occurred_at']))->setTimezone('Australia/Melbourne')->format('d M Y, H:i'))
                                        : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
