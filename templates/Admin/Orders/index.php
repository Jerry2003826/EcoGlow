<?php
/**
 * Admin order list.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\SalesOrder> $salesOrders
 * @var string $status
 * @var string $channel
 * @var string $q
 * @var string $from
 * @var string $to
 * @var \Cake\I18n\Date $today
 * @var array<string, int> $statusCounts
 * @var int $awaitingCount
 * @var int $overdueCount
 */

use App\Model\Entity\SalesOrder;

$statusCounts = $statusCounts ?? [];
$orderTotal = array_sum($statusCounts);
$onHoldCount = $statusCounts[SalesOrder::STATUS_ON_HOLD] ?? 0;

$this->assign('title', 'Orders');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Orders']],
]));
?>
<div class="admin-page-head d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
        <span class="eg-eyebrow">Sales</span>
        <h1>Orders</h1>
    </div>
    <?= $this->Html->link('Record order', ['action' => 'add'], ['class' => 'btn btn-eg-primary']) ?>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $orderTotal ?></span>
        <span class="eg-eyebrow">All orders</span>
    </div>
    <a class="admin-stat-card<?= $status === SalesOrder::STATUS_CONFIRMED ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => array_filter([
           'status' => SalesOrder::STATUS_CONFIRMED,
           'channel' => $channel !== '' ? $channel : null,
           'q' => $q !== '' ? $q : null,
           'from' => $from !== '' ? $from : null,
           'to' => $to !== '' ? $to : null,
       ])]) ?>">
        <span class="admin-stat-value"><?= (int)$awaitingCount ?></span>
        <span class="eg-eyebrow">Awaiting dispatch</span>
    </a>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= (int)$overdueCount ?></span>
        <span class="eg-eyebrow">Overdue</span>
    </div>
    <a class="admin-stat-card<?= $status === SalesOrder::STATUS_ON_HOLD ? ' is-active' : '' ?>"
       href="<?= $this->Url->build(['action' => 'index', '?' => array_filter([
           'status' => SalesOrder::STATUS_ON_HOLD,
           'channel' => $channel !== '' ? $channel : null,
           'q' => $q !== '' ? $q : null,
           'from' => $from !== '' ? $from : null,
           'to' => $to !== '' ? $to : null,
       ])]) ?>">
        <span class="admin-stat-value"><?= $onHoldCount ?></span>
        <span class="eg-eyebrow">On hold</span>
    </a>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="eg-chip-row" role="group" aria-label="Filter by status">
        <?php
        $statusUrl = function (?string $value) use ($channel, $q, $from, $to): array {
            $query = array_filter([
                'status' => $value,
                'channel' => $channel !== '' ? $channel : null,
                'q' => $q !== '' ? $q : null,
                'from' => $from !== '' ? $from : null,
                'to' => $to !== '' ? $to : null,
            ]);

            return ['action' => 'index', '?' => $query];
        };
        ?>
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">
            All <span class="admin-chip-count"><?= $orderTotal ?></span>
        </a>
        <?php foreach (SalesOrder::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>">
                <?= h($label) ?> <span class="admin-chip-count"><?= (int)($statusCounts[$key] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="admin-filter-row">
        <div class="admin-field">
            <label for="channel">Source channel</label>
            <select name="channel" id="channel" class="form-select">
                <option value="">All channels</option>
                <?php foreach (SalesOrder::channelLabels() as $key => $label) : ?>
                    <option value="<?= h($key) ?>"<?= $channel === $key ? ' selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label for="from">From date</label>
            <input type="date" class="form-control" id="from" name="from" value="<?= h($from) ?>" lang="en">
        </div>
        <div class="admin-field">
            <label for="to">To date</label>
            <input type="date" class="form-control" id="to" name="to" value="<?= h($to) ?>" lang="en">
        </div>
        <div class="admin-field admin-field-search">
            <label for="q">Search</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= h($q) ?>" placeholder="Order or customer" lang="en">
        </div>
        <?php if ($status !== '') : ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-eg-ghost">Apply filters</button>
    </div>
</form>

<?php if (count($salesOrders) === 0) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No orders match those filters',
            'body' => 'Recorded phone, email, SMS and walk-in sales will appear here. Try clearing the filters, or record a new order.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2>Sales orders</h2>
            <p class="admin-panel-caption">
                <?= (int)$salesOrders->totalCount() ?> matching
            </p>
        </div>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Sales orders">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('order_number', 'Order') ?></th>
                        <th><?= $this->Paginator->sort('source_channel', 'Channel') ?></th>
                        <th class="text-end"><?= $this->Paginator->sort('grand_total_cents', 'Amount') ?></th>
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th><?= $this->Paginator->sort('placed_at', 'Placed') ?></th>
                        <th><?= $this->Paginator->sort('promised_delivery_date', 'Promised') ?></th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesOrders as $order) : ?>
                        <?php
                        $overdue = $order->isDeliveryOverdue($today);
                        $channels = SalesOrder::channelLabels();
                        $channelLabel = $channels[$order->source_channel] ?? $order->source_channel;
                        ?>
                        <tr class="<?= $overdue ? 'is-overdue' : '' ?>">
                            <td class="admin-identity-cell" data-label="Order">
                                <?= $this->element('admin/identity', [
                                    'title' => (string)$order->customer_label,
                                    'code' => (string)$order->order_number,
                                    'meta' => null,
                                ]) ?>
                            </td>
                            <td data-label="Channel"><?= h($channelLabel) ?></td>
                            <td class="cell-qty" data-label="Amount"><?= $this->Money->aud((int)$order->grand_total_cents) ?></td>
                            <td data-label="Status"><?= $this->element('admin/status_pill', ['status' => $order->status]) ?></td>
                            <td class="text-nowrap" data-label="Placed"><?= h(($order->placed_at ?? $order->created)?->format('d M Y')) ?></td>
                            <td class="text-nowrap" data-label="Promised">
                                <?= $order->promised_delivery_date ? h($order->promised_delivery_date->format('d M Y')) : '—' ?>
                                <?php if ($overdue) : ?>
                                    <span class="admin-overdue-flag">Overdue <?= (int)$order->overdueDays($today) ?> days</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end admin-list-action" data-label="">
                                <?= $this->Html->link('View', ['action' => 'view', $order->id], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->element('admin/pagination', [
        'label' => 'Orders pagination',
        'counter' => 'Page {{page}} of {{pages}}, showing {{current}} of {{count}} orders',
    ]) ?>
<?php endif; ?>
