<?php
/**
 * Staff dashboard.
 *
 * @var \App\View\AppView $this
 * @var int $ordersToday
 * @var int $awaitingDispatch
 * @var int $lowStock
 * @var int $unreadMessages
 * @var iterable<\App\Model\Entity\SalesOrder> $newOrders
 * @var iterable<\App\Model\Entity\ContactMessage> $unreadInbox
 * @var array<int, array<string, mixed>> $lowStockItems
 * @var array<int, array<string, mixed>> $recentTransactions
 */

use App\Model\Entity\SalesOrder;
use Cake\I18n\DateTime;

$this->assign('title', 'Dashboard');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Dashboard']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Today</span>
    <h1>Dashboard</h1>
</div>

<div class="admin-stat-grid">
    <a class="admin-stat-card" href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'index']) ?>">
        <span class="admin-stat-value"><?= (int)$ordersToday ?></span>
        <span class="eg-eyebrow">Orders today</span>
    </a>
    <a class="admin-stat-card" href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'index', '?' => ['status' => SalesOrder::STATUS_CONFIRMED]]) ?>">
        <span class="admin-stat-value"><?= (int)$awaitingDispatch ?></span>
        <span class="eg-eyebrow">Awaiting dispatch</span>
    </a>
    <a class="admin-stat-card" href="<?= $this->Url->build(['controller' => 'Inventory', 'action' => 'index']) ?>">
        <span class="admin-stat-value"><?= (int)$lowStock ?></span>
        <span class="eg-eyebrow">Low stock items</span>
    </a>
    <a class="admin-stat-card" href="<?= $this->Url->build(['controller' => 'ContactMessages', 'action' => 'index']) ?>">
        <span class="admin-stat-value"><?= (int)$unreadMessages ?></span>
        <span class="eg-eyebrow">Unread messages</span>
    </a>
</div>

<div class="admin-split">
    <section class="admin-section" aria-labelledby="need-heading">
        <h2 id="need-heading">Needs attention</h2>
        <div class="admin-panel">
            <h3 class="admin-panel-title">New orders</h3>
            <?php if (count($newOrders) === 0) : ?>
                <p class="admin-empty">None waiting.</p>
            <?php else : ?>
                <ul class="admin-need-list">
                    <?php foreach ($newOrders as $order) : ?>
                        <li>
                            <a href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'view', $order->id]) ?>">
                                <span><?= h($order->order_number) ?> · <?= h($order->customer_label) ?></span>
                                <span><?= $this->Money->aud((int)$order->grand_total_cents) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3 class="admin-panel-title mt-4">Unread messages</h3>
            <?php if (count($unreadInbox) === 0) : ?>
                <p class="admin-empty">None waiting.</p>
            <?php else : ?>
                <ul class="admin-need-list">
                    <?php foreach ($unreadInbox as $message) : ?>
                        <li>
                            <a href="<?= $this->Url->build(['controller' => 'ContactMessages', 'action' => 'view', $message->id]) ?>">
                                <span><?= h($message->subject) ?></span>
                                <span><?= h($message->name) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3 class="admin-panel-title mt-4">Below reorder point</h3>
            <?php if ($lowStockItems === []) : ?>
                <p class="admin-empty">None waiting.</p>
            <?php else : ?>
                <ul class="admin-need-list">
                    <?php foreach ($lowStockItems as $item) : ?>
                        <li>
                            <a href="<?= $this->Url->build(['controller' => 'Inventory', 'action' => 'index']) ?>">
                                <span><?= h($item['sku']) ?> · <?= h($item['product_name']) ?></span>
                                <span><?= (int)$item['quantity_available'] ?> available</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-section" aria-labelledby="recent-heading">
        <h2 id="recent-heading">Recent transactions</h2>
        <div class="admin-panel">
            <?php if ($recentTransactions === []) : ?>
                <p class="admin-empty">None yet.</p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-eg align-middle" aria-label="Recent transactions">
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
                            <?php foreach ($recentTransactions as $row) : ?>
                                <tr>
                                    <td>
                                        <?php if ($row['transaction_type'] === 'order') : ?>
                                            <a href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'view', $row['transaction_id']]) ?>">
                                                <?= h($row['reference_number']) ?>
                                            </a>
                                        <?php else : ?>
                                            <?= h($row['reference_number'] ?: '—') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($row['customer_name'] ?: 'Walk-in') ?></td>
                                    <td><?= $this->Money->aud((int)$row['amount_cents']) ?></td>
                                    <td><?= $this->element('admin/status_pill', ['status' => (string)$row['status']]) ?></td>
                                    <td class="text-nowrap">
                                        <?= h($row['occurred_at'] ? (new DateTime($row['occurred_at']))->format('d M Y, H:i') : '—') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
