<?php
/**
 * Customer list from v_customer_360_summary.
 *
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $rows
 * @var string $q
 * @var bool $canSeeContact
 */

use App\Service\ContactMask;
use Cake\I18n\DateTime;

$customerCount = count($rows);
$withOrders = 0;
$openContacts = 0;
$lifetimeCents = 0;
foreach ($rows as $row) {
    if ((int)$row['order_count'] > 0) {
        $withOrders++;
    }
    $openContacts += (int)($row['open_contact_count'] ?? 0);
    $lifetimeCents += (int)$row['lifetime_order_value_cents'];
}

$this->assign('title', 'Customers');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Customers']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">CRM</span>
    <h1>Customers</h1>
</div>

<div class="admin-stat-grid">
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $customerCount ?></span>
        <span class="eg-eyebrow">Customers</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $withOrders ?></span>
        <span class="eg-eyebrow">With orders</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $openContacts ?></span>
        <span class="eg-eyebrow">Open enquiries</span>
    </div>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $this->Money->aud($lifetimeCents) ?></span>
        <span class="eg-eyebrow">Lifetime spend</span>
    </div>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="admin-filter-row">
        <div class="admin-field admin-field-search">
            <label for="q">Search</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= h($q) ?>" placeholder="Name, email or phone">
        </div>
        <button type="submit" class="btn btn-eg-ghost">Search</button>
    </div>
</form>

<?php if ($rows === []) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No customers match that search',
            'body' => 'Phone, email and walk-in buyers recorded with an order will appear here with their order count and spend.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2>Customer 360</h2>
            <p class="admin-panel-caption"><?= $customerCount ?> matching</p>
        </div>
        <?php if (!$canSeeContact) : ?>
            <p class="admin-mask-note" role="note">Contact details are permission-protected on this screen.</p>
        <?php endif; ?>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Customers">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Type / status</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Lifetime spend</th>
                        <th>Last order</th>
                        <th class="text-end"> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $name = trim($row['first_name'] . ' ' . $row['last_name']);
                        $email = $canSeeContact ? ($row['email'] ?: '—') : ContactMask::email($row['email'] ?? null);
                        $phone = $canSeeContact ? ($row['phone'] ?: '—') : ContactMask::phone($row['phone'] ?? null);
                        $meta = trim($email . ' · ' . $phone, ' ·');
                        $status = (string)($row['status'] ?: '');
                        ?>
                        <tr>
                            <td class="admin-identity-cell admin-sensitive" data-label="Customer">
                                <?= $this->element('admin/identity', [
                                    'title' => $name !== '' ? $name : 'Customer',
                                    'code' => null,
                                    'meta' => $meta,
                                ]) ?>
                            </td>
                            <td data-label="Status">
                                <?= $this->element('admin/status_pill', [
                                    'status' => $status !== '' ? $status : 'unknown',
                                    'label' => $status !== '' ? $status : '—',
                                    'toneOverride' => $status === 'active' ? 'success' : 'muted',
                                ]) ?>
                            </td>
                            <td class="cell-qty" data-label="Orders"><?= (int)$row['order_count'] ?></td>
                            <td class="cell-qty" data-label="Lifetime spend"><?= $this->Money->aud((int)$row['lifetime_order_value_cents']) ?></td>
                            <td class="text-nowrap" data-label="Last order">
                                <?= $row['last_order_at']
                                    ? h((new DateTime($row['last_order_at']))->setTimezone('Australia/Melbourne')->format('d M Y'))
                                    : '—' ?>
                            </td>
                            <td class="text-end admin-list-action" data-label="">
                                <?= $this->Html->link('View', ['action' => 'view', $row['customer_id']], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
