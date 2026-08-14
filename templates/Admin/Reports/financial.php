<?php
/**
 * Financial report: profit, COGS and named customers.
 *
 * @var \App\View\AppView $this
 * @var string $preset
 * @var string $from
 * @var string $to
 * @var int $estimatedGrossProfit
 * @var int $cogsCents
 * @var array<int, array<string, mixed>> $transactions
 */

use Cake\I18n\DateTime;

$this->assign('title', 'Financial reports');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Reports', 'url' => ['action' => 'index']],
        ['label' => 'Financial'],
    ],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Finance</span>
    <h1>Financial reports</h1>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud((int)$estimatedGrossProfit) ?></span>
        <span class="eg-eyebrow">estimated gross profit</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud((int)$cogsCents) ?></span>
        <span class="eg-eyebrow">Estimated COGS</span>
    </div>
</div>

<p class="admin-note mb-4">
    Profit is labelled <strong>estimated gross profit</strong> because cost snapshots are estimates, not a full COGS ledger.
    <a href="<?= $this->Url->build(['action' => 'index', '?' => ['preset' => $preset, 'from' => $from, 'to' => $to]]) ?>">Back to operating reports</a>
</p>

<section class="admin-section">
    <div class="admin-panel">
        <div class="admin-panel-head"><h2>Transactions</h2></div>
        <?php if ($transactions === []) : ?>
            <?= $this->element('admin/empty', [
                'title' => 'No transactions in this range',
                'body' => 'Orders, payments and refunds dated in the selected Melbourne calendar days will list here.',
            ]) ?>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-eg align-middle" aria-label="Financial transactions">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
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
