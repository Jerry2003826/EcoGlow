<?php
/**
 * Inventory list with reasoned adjustments.
 *
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $rows
 * @var iterable<\App\Model\Entity\InventoryLocation> $locations
 * @var int $locationId
 * @var bool $canAdjust
 * @var array<string, array{label: string, sign: int, type: string}> $reasons
 */

$this->assign('title', 'Inventory');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Inventory']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Catalogue</span>
    <h1>Inventory</h1>
</div>

<form class="admin-filters" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
    <div class="admin-field">
        <label for="location">Location</label>
        <select name="location" id="location" class="form-select" onchange="this.form.submit()">
            <?php foreach ($locations as $location) : ?>
                <option value="<?= (int)$location->id ?>"<?= (int)$location->id === $locationId ? ' selected' : '' ?>>
                    <?= h($location->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <noscript><button type="submit" class="btn btn-eg-ghost">Filter</button></noscript>
</form>

<?php if ($rows === []) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No products at this location',
            'body' => 'Active variants and their on-hand, reserved and available quantities will list here once stock is received.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="table-responsive">
            <table class="table table-eg align-middle" aria-label="Inventory balances">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>On hand</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Reorder point</th>
                        <th>Status</th>
                        <?php if ($canAdjust) : ?>
                            <th>Adjust</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <?php $needs = (int)$row['needs_reorder'] === 1; ?>
                        <tr class="<?= $needs ? 'is-low-stock' : '' ?>">
                            <td class="cell-sku"><?= h($row['sku']) ?></td>
                            <td>
                                <?= h($row['product_name']) ?>
                                <?php if ($row['variant_name']) : ?>
                                    <div class="small text-muted"><?= h($row['variant_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$row['quantity_on_hand'] ?></td>
                            <td><?= (int)$row['quantity_reserved'] ?></td>
                            <td><?= (int)$row['quantity_available'] ?></td>
                            <td><?= (int)$row['reorder_point'] ?></td>
                            <td>
                                <?php if ($needs) : ?>
                                    <span class="admin-status admin-status-warning">Needs reorder</span>
                                <?php else : ?>
                                    <span class="admin-status admin-status-success">In stock</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canAdjust) : ?>
                                <td>
                                    <?= $this->Form->create(null, [
                                        'url' => ['action' => 'adjust'],
                                        'class' => 'admin-adjust-form',
                                    ]) ?>
                                    <input type="hidden" name="product_variant_id" value="<?= (int)$row['product_variant_id'] ?>">
                                    <input type="hidden" name="inventory_location_id" value="<?= (int)$row['inventory_location_id'] ?>">
                                    <label class="visually-hidden" for="qty-<?= (int)$row['product_variant_id'] ?>">Quantity</label>
                                    <input class="form-control" type="number" min="1" step="1" required
                                           name="quantity" id="qty-<?= (int)$row['product_variant_id'] ?>" value="1">
                                    <label class="visually-hidden" for="reason-<?= (int)$row['product_variant_id'] ?>">Reason</label>
                                    <select class="form-select" name="reason" id="reason-<?= (int)$row['product_variant_id'] ?>" required>
                                        <option value="">Reason</option>
                                        <?php foreach ($reasons as $key => $reason) : ?>
                                            <option value="<?= h($key) ?>"><?= h($reason['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="visually-hidden" for="note-<?= (int)$row['product_variant_id'] ?>">Note</label>
                                    <input class="form-control" type="text" name="note"
                                           id="note-<?= (int)$row['product_variant_id'] ?>" placeholder="Note (optional)">
                                    <button type="submit" class="btn btn-eg-ghost">Update</button>
                                    <?= $this->Form->end() ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
