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
 */

use App\Model\Entity\SalesOrder;

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
        <a class="eg-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl(null)) ?>">All</a>
        <?php foreach (SalesOrder::statusLabels() as $key => $label) : ?>
            <a class="eg-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= $this->Url->build($statusUrl($key)) ?>"><?= h($label) ?></a>
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
        <div class="table-responsive">
            <table class="table table-eg table-hover align-middle" aria-label="Sales orders">
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('order_number', 'Order') ?></th>
                        <th>Customer</th>
                        <th><?= $this->Paginator->sort('source_channel', 'Channel') ?></th>
                        <th><?= $this->Paginator->sort('grand_total_cents', 'Amount') ?></th>
                        <th><?= $this->Paginator->sort('status') ?></th>
                        <th><?= $this->Paginator->sort('placed_at', 'Placed') ?></th>
                        <th><?= $this->Paginator->sort('promised_delivery_date', 'Promised') ?></th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesOrders as $order) : ?>
                        <?php
                        $overdue = $order->isDeliveryOverdue($today);
                        $channels = SalesOrder::channelLabels();
                        ?>
                        <tr class="<?= $overdue ? 'is-overdue' : '' ?>">
                            <td class="cell-id"><?= h($order->order_number) ?></td>
                            <td><?= h($order->customer_label) ?></td>
                            <td><?= h($channels[$order->source_channel] ?? $order->source_channel) ?></td>
                            <td><?= $this->Money->aud((int)$order->grand_total_cents) ?></td>
                            <td><?= $this->element('admin/status_pill', ['status' => $order->status]) ?></td>
                            <td class="text-nowrap"><?= h(($order->placed_at ?? $order->created)?->format('d M Y')) ?></td>
                            <td class="text-nowrap">
                                <?= $order->promised_delivery_date ? h($order->promised_delivery_date->format('d M Y')) : '—' ?>
                                <?php if ($overdue) : ?>
                                    <span class="admin-overdue-flag">Overdue <?= (int)$order->overdueDays($today) ?> days</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
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
