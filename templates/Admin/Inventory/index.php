<?php
/**
 * Inventory list with a single reasoned-adjustment panel.
 *
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $rows
 * @var iterable<\App\Model\Entity\InventoryLocation> $locations
 * @var int $locationId
 * @var bool $canAdjust
 * @var array<string, array{label: string, sign: int, type: string}> $reasons
 */

$skuCount = count($rows);
$reorderCount = 0;
$outCount = 0;
$onHandTotal = 0;
$reservedTotal = 0;
$locationName = '';
foreach ($locations as $location) {
    if ((int)$location->id === $locationId) {
        $locationName = (string)$location->name;
        break;
    }
}
foreach ($rows as $row) {
    $onHandTotal += (int)$row['quantity_on_hand'];
    $reservedTotal += (int)$row['quantity_reserved'];
    if ((int)$row['needs_reorder'] === 1) {
        $reorderCount++;
    }
    if ((int)$row['quantity_available'] <= 0) {
        $outCount++;
    }
}
$increaseReasons = array_filter($reasons, static fn(array $reason): bool => $reason['sign'] === 1);
$decreaseReasons = array_filter($reasons, static fn(array $reason): bool => $reason['sign'] === -1);

$this->assign('title', 'Inventory');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Inventory']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Catalogue<?= $locationName !== '' ? ' · ' . h($locationName) : '' ?></span>
    <h1>Inventory</h1>
</div>

<div class="admin-stat-grid" data-inv-stats>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $skuCount ?></span>
        <span class="eg-eyebrow">Active SKUs</span>
    </div>
    <button type="button" class="admin-stat-card" data-inv-filter="low" aria-pressed="false">
        <span class="admin-stat-value"><?= $reorderCount ?></span>
        <span class="eg-eyebrow">Needs reorder</span>
    </button>
    <button type="button" class="admin-stat-card" data-inv-filter="out" aria-pressed="false">
        <span class="admin-stat-value"><?= $outCount ?></span>
        <span class="eg-eyebrow">Out of stock</span>
    </button>
    <div class="admin-stat-card">
        <span class="admin-stat-value"><?= $onHandTotal ?></span>
        <span class="eg-eyebrow">On hand<?= $reservedTotal > 0 ? ' · ' . $reservedTotal . ' reserved' : '' ?></span>
    </div>
</div>

<div class="admin-filters">
    <div class="admin-filter-row">
        <form class="admin-field" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
            <label for="location">Location</label>
            <select name="location" id="location" class="form-select" onchange="this.form.submit()">
                <?php foreach ($locations as $location) : ?>
                    <option value="<?= (int)$location->id ?>"<?= (int)$location->id === $locationId ? ' selected' : '' ?>>
                        <?= h($location->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-eg-ghost">Filter</button></noscript>
        </form>
        <div class="admin-field admin-field-search">
            <label for="inventory-search">Search</label>
            <input type="search" id="inventory-search" class="form-control" data-inv-search
                   placeholder="SKU, product or variant" autocomplete="off">
        </div>
    </div>
    <div class="eg-chip-row" role="group" aria-label="Filter by stock status">
        <button type="button" class="eg-chip is-active" data-inv-filter="all" aria-pressed="true">All</button>
        <button type="button" class="eg-chip" data-inv-filter="low" aria-pressed="false">
            Needs reorder <span class="admin-chip-count"><?= $reorderCount ?></span>
        </button>
        <button type="button" class="eg-chip" data-inv-filter="out" aria-pressed="false">
            Out of stock <span class="admin-chip-count"><?= $outCount ?></span>
        </button>
        <button type="button" class="eg-chip" data-inv-filter="in" aria-pressed="false">In stock</button>
    </div>
</div>

<?php if ($rows === []) : ?>
    <div class="admin-panel">
        <?= $this->element('admin/empty', [
            'title' => 'No products at this location',
            'body' => 'Active variants and their on-hand, reserved and available quantities will list here once stock is received.',
        ]) ?>
    </div>
<?php else : ?>
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2>Stock balances</h2>
            <p class="admin-panel-caption" data-inv-caption data-location="<?= h($locationName !== '' ? $locationName : 'this location') ?>">
                <?= $skuCount ?> SKUs at <?= h($locationName !== '' ? $locationName : 'this location') ?>
            </p>
        </div>
        <div class="table-responsive admin-list-wrap">
            <table class="table table-eg admin-list-table align-middle" aria-label="Inventory balances" data-inv-table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">On hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Reorder at</th>
                        <th>Status</th>
                        <?php if ($canAdjust) : ?>
                            <th class="text-end"> </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $needs = (int)$row['needs_reorder'] === 1;
                        $available = (int)$row['quantity_available'];
                        $onHand = (int)$row['quantity_on_hand'];
                        $reserved = (int)$row['quantity_reserved'];
                        $reorderAt = (int)$row['reorder_point'];
                        $out = $available <= 0;
                        $stock = $out ? 'out' : ($needs ? 'low' : 'in');
                        $statusLabel = $out ? 'Out of stock' : ($needs ? 'Needs reorder' : 'In stock');
                        $statusTone = $out ? 'error' : ($needs ? 'warning' : 'success');
                        $variantLabel = trim((string)$row['variant_name']);
                        $productLabel = (string)$row['product_name'];
                        if ($variantLabel !== '') {
                            $productLabel .= ' · ' . $variantLabel;
                        }
                        $rowSearch = strtolower(
                            (string)$row['sku'] . ' ' . (string)$row['product_name'] . ' ' . $variantLabel . ' ' . $statusLabel,
                        );
                        $rowClass = $out ? 'is-out-stock' : ($needs ? 'is-low-stock' : '');
                        ?>
                        <tr class="<?= h($rowClass) ?>"
                            data-inv-row
                            data-stock="<?= h($stock) ?>"
                            data-low="<?= $needs ? '1' : '0' ?>"
                            data-search="<?= h($rowSearch) ?>">
                            <td class="admin-identity-cell" data-label="Product">
                                <?= $this->element('admin/identity', [
                                    'title' => (string)$row['product_name'],
                                    'code' => (string)$row['sku'],
                                    'meta' => $variantLabel !== '' ? $variantLabel : null,
                                ]) ?>
                            </td>
                            <td class="cell-qty" data-label="On hand"><?= $onHand ?></td>
                            <td class="cell-qty<?= $reserved > 0 ? ' is-reserved' : '' ?>" data-label="Reserved"><?= $reserved ?></td>
                            <td class="cell-qty" data-label="Available"><?= $available ?></td>
                            <td class="cell-qty cell-qty-muted" data-label="Reorder at"><?= $reorderAt > 0 ? $reorderAt : '—' ?></td>
                            <td data-label="Status">
                                <span class="admin-status admin-status-<?= h($statusTone) ?>"><?= h($statusLabel) ?></span>
                            </td>
                            <?php if ($canAdjust) : ?>
                                <td class="text-end admin-list-action" data-label="">
                                    <button type="button"
                                            class="btn btn-sm btn-eg-ghost"
                                            data-inv-adjust
                                            data-variant="<?= (int)$row['product_variant_id'] ?>"
                                            data-label="<?= h($productLabel) ?>"
                                            data-counts="<?= h($onHand . ' on hand · ' . $reserved . ' reserved · ' . $available . ' available') ?>">
                                        Adjust
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="admin-empty mt-3" data-inv-empty hidden>No SKUs match that search or filter.</p>
    </div>

    <?php if ($canAdjust) : ?>
        <div class="admin-panel admin-inv-adjust" data-inv-adjust-panel>
            <div class="admin-panel-head">
                <h2>Adjust stock</h2>
            </div>
            <p class="admin-inv-preview" data-inv-preview hidden></p>
            <?= $this->Form->create(null, [
                'url' => ['action' => 'adjust'],
                'class' => 'admin-inv-form',
                'data-inv-form' => true,
            ]) ?>
            <input type="hidden" name="inventory_location_id" value="<?= (int)$locationId ?>">
            <div class="admin-field">
                <label for="product_variant_id">SKU</label>
                <select name="product_variant_id" id="product_variant_id" class="form-select" required>
                    <option value="">Choose a SKU</option>
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $optionLabel = (string)$row['sku'] . ' — ' . (string)$row['product_name'];
                        if (!empty($row['variant_name'])) {
                            $optionLabel .= ' (' . $row['variant_name'] . ')';
                        }
                        ?>
                        <option value="<?= (int)$row['product_variant_id'] ?>"><?= h($optionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field admin-inv-qty">
                <label for="quantity">Quantity</label>
                <input class="form-control" type="number" min="1" step="1" required
                       name="quantity" id="quantity" value="1">
            </div>
            <div class="admin-field">
                <label for="reason">Reason</label>
                <select class="form-select" name="reason" id="reason" required>
                    <option value="">Choose a reason</option>
                    <optgroup label="Increase stock">
                        <?php foreach ($increaseReasons as $key => $reason) : ?>
                            <option value="<?= h($key) ?>"><?= h($reason['label']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Decrease stock">
                        <?php foreach ($decreaseReasons as $key => $reason) : ?>
                            <option value="<?= h($key) ?>"><?= h($reason['label']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="admin-field">
                <label for="note">Note (optional)</label>
                <input class="form-control" type="text" name="note" id="note" placeholder="Supplier, count sheet, damage…">
            </div>
            <button type="submit" class="btn btn-eg-primary">Update stock</button>
            <?= $this->Form->end() ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
