<?php
/**
 * Customer 360 detail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var array<string, mixed> $summary
 * @var bool $canSeeContact
 * @var string $emailDisplay
 * @var string $phoneDisplay
 */

use App\Model\Entity\ContactMessage;
use App\Model\Entity\Invoice;
use Cake\I18n\DateTime;

$this->assign('title', $customer->label);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Customers', 'url' => ['action' => 'index']],
        ['label' => $customer->label],
    ],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Customer 360</span>
    <h1><?= h($customer->label) ?></h1>
</div>

<p class="admin-mask-note" role="note">
    Contact details are permission-protected.
    <?php if (!$canSeeContact) : ?>
        Email and phone are masked because your role does not include customers.view.
    <?php else : ?>
        You can see email and phone because you hold customers.view. Age is not collected on this screen.
    <?php endif; ?>
</p>

<div class="admin-detail">
    <div>
        <section class="admin-section" aria-labelledby="profile-heading">
            <h2 id="profile-heading">Profile</h2>
            <div class="admin-panel">
                <dl class="eg-kv-list">
                    <div class="eg-kv-row">
                        <dt>Name</dt>
                        <dd><?= h($customer->label) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Email</dt>
                        <dd class="admin-sensitive"><?= h($emailDisplay) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Phone</dt>
                        <dd class="admin-sensitive"><?= h($phoneDisplay) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Company</dt>
                        <dd><?= h($customer->company ?: '—') ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Type</dt>
                        <dd><?= h($customer->customer_type ?: 'individual') ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Status</dt>
                        <dd><?= h($customer->status) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Orders</dt>
                        <dd><?= (int)($summary['order_count'] ?? 0) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Lifetime spend</dt>
                        <dd><?= $this->Money->aud((int)($summary['lifetime_order_value_cents'] ?? 0)) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Last order</dt>
                        <dd>
                            <?= !empty($summary['last_order_at'])
                                ? h((new DateTime($summary['last_order_at']))->setTimezone('Australia/Melbourne')->format('d M Y, H:i'))
                                : '—' ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="orders-heading">
            <h2 id="orders-heading">Orders</h2>
            <div class="admin-panel">
                <?php if (empty($customer->sales_orders)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No orders yet',
                        'body' => 'Sales recorded for this customer will list here with their totals.',
                    ]) ?>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-eg align-middle" aria-label="Customer orders">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Placed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer->sales_orders as $order) : ?>
                                    <tr>
                                        <td class="cell-id">
                                            <?= $this->Html->link($order->order_number, ['controller' => 'Orders', 'action' => 'view', $order->id]) ?>
                                        </td>
                                        <td><?= $this->element('admin/status_pill', ['status' => $order->status]) ?></td>
                                        <td><?= $this->Money->aud((int)$order->grand_total_cents) ?></td>
                                        <td class="text-nowrap"><?= h(($order->placed_at ?? $order->created)?->setTimezone('Australia/Melbourne')->format('d M Y')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="invoices-heading">
            <h2 id="invoices-heading">Invoices</h2>
            <div class="admin-panel">
                <?php if (empty($customer->invoices)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No invoices yet',
                        'body' => 'Invoices issued from this customer’s orders will appear here.',
                    ]) ?>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-eg align-middle" aria-label="Customer invoices">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer->invoices as $invoice) : ?>
                                    <tr>
                                        <td class="cell-id">
                                            <?= $this->Html->link($invoice->invoice_number, ['controller' => 'Invoices', 'action' => 'view', $invoice->id]) ?>
                                        </td>
                                        <td><?= $this->element('admin/status_pill', [
                                            'status' => $invoice->status,
                                            'label' => Invoice::statusLabels()[$invoice->status] ?? $invoice->status,
                                            'toneOverride' => Invoice::statusTone($invoice->status),
                                        ]) ?></td>
                                        <td><?= $this->Money->aud((int)$invoice->grand_total_cents) ?></td>
                                        <td><?= $this->Money->aud((int)$invoice->balance_due_cents) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section" aria-labelledby="address-heading">
            <h2 id="address-heading">Addresses</h2>
            <div class="admin-panel">
                <?php if (empty($customer->addresses)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No saved addresses',
                        'body' => 'Shipping and billing addresses added for this customer will show here.',
                    ]) ?>
                <?php else : ?>
                    <?php foreach ($customer->addresses as $address) : ?>
                        <p class="mb-2">
                            <?= h($address->recipient_name) ?><br>
                            <?= h($address->line1) ?>
                            <?= $address->line2 ? '<br>' . h($address->line2) : '' ?><br>
                            <?= h($address->suburb) ?> <?= h($address->state) ?> <?= h($address->postcode) ?>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="messages-heading">
            <h2 id="messages-heading">Contact messages</h2>
            <div class="admin-panel">
                <?php if (empty($customer->contact_messages)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No linked enquiries',
                        'body' => 'Contact-form messages matched to this customer will list here.',
                    ]) ?>
                <?php else : ?>
                    <ul class="admin-need-list">
                        <?php foreach ($customer->contact_messages as $message) : ?>
                            <li>
                                <a href="<?= $this->Url->build(['controller' => 'ContactMessages', 'action' => 'view', $message->id]) ?>">
                                    <span><?= h($message->subject) ?></span>
                                    <span><?= h(ContactMessage::statusLabels()[$message->status] ?? $message->status) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-section" aria-labelledby="interactions-heading">
            <h2 id="interactions-heading">Interactions</h2>
            <div class="admin-panel">
                <?php if (empty($customer->customer_interactions)) : ?>
                    <?= $this->element('admin/empty', [
                        'title' => 'No logged interactions',
                        'body' => 'Calls, emails and notes logged against this customer will appear here.',
                    ]) ?>
                <?php else : ?>
                    <ol class="admin-timeline">
                        <?php foreach ($customer->customer_interactions as $interaction) : ?>
                            <li>
                                <strong><?= h($interaction->interaction_type) ?> · <?= h($interaction->channel) ?></strong>
                                <div class="small text-muted"><?= h($interaction->occurred_at?->format('d M Y, H:i')) ?></div>
                                <div><?= h($interaction->subject ?: $interaction->body) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
