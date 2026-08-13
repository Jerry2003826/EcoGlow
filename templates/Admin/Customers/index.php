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

$this->assign('title', 'Customers');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Customers']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">CRM</span>
    <h1>Customers</h1>
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
        <?php if (!$canSeeContact) : ?>
            <p class="admin-mask-note" role="note">Contact details are permission-protected on this screen.</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-eg table-hover align-middle" aria-label="Customers">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type / status</th>
                        <th>Orders</th>
                        <th>Lifetime spend</th>
                        <th>Last order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?= h(trim($row['first_name'] . ' ' . $row['last_name'])) ?></td>
                            <td class="admin-sensitive">
                                <?= h($canSeeContact ? ($row['email'] ?: '—') : ContactMask::email($row['email'] ?? null)) ?>
                            </td>
                            <td class="cell-id admin-sensitive">
                                <?= h($canSeeContact ? ($row['phone'] ?: '—') : ContactMask::phone($row['phone'] ?? null)) ?>
                            </td>
                            <td><?= h($row['status'] ?: '—') ?></td>
                            <td><?= (int)$row['order_count'] ?></td>
                            <td><?= $this->Money->aud((int)$row['lifetime_order_value_cents']) ?></td>
                            <td class="text-nowrap">
                                <?= $row['last_order_at']
                                    ? h((new DateTime($row['last_order_at']))->setTimezone('Australia/Melbourne')->format('d M Y'))
                                    : '—' ?>
                            </td>
                            <td class="text-end">
                                <?= $this->Html->link('View', ['action' => 'view', $row['customer_id']], ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
