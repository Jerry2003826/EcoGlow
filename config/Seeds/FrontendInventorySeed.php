<?php
declare(strict_types=1);

use App\Service\Inventory\InventoryLedger;
use Cake\Database\Connection;
use Migrations\BaseSeed;

/**
 * Opening stock for the seeded storefront catalogue.
 *
 * Quantities go through sp_apply_inventory_change_in_transaction so
 * inventory_movements has a matching ledger row. inventory_balances is never
 * INSERTed here: quantity_available is a generated column, and the procedure
 * creates the balance row before applying the delta.
 *
 * Idempotency is ALIGN-to-target, not "add another receipt". When on-hand
 * already equals the demo target the procedure is skipped (it rejects a zero
 * delta). Re-running therefore cannot double stock. On-hand is never reduced
 * below quantity_reserved. Reorder rules and the balance reorder_point used by
 * v_low_stock_items are upserted every run.
 */
final class FrontendInventorySeed extends BaseSeed
{
    /**
     * Ashby and Rowan sit below their reorder points so the staff inventory
     * list and the dashboard "Low stock items" card have real rows.
     *
     * @var array<string, array{on_hand: int, reorder_point: int, reorder_quantity: int}>
     */
    private const TARGETS = [
        'LEGACY-MARLOW-FLOOR-LAMP' => ['on_hand' => 28, 'reorder_point' => 8, 'reorder_quantity' => 20],
        'LEGACY-HALDEN-PENDANT' => ['on_hand' => 22, 'reorder_point' => 6, 'reorder_quantity' => 16],
        'LEGACY-AURA-SMART-BULB-SET' => ['on_hand' => 40, 'reorder_point' => 10, 'reorder_quantity' => 24],
        'LEGACY-FERNWAY-SOLAR-PATH-LIGHT' => ['on_hand' => 18, 'reorder_point' => 6, 'reorder_quantity' => 12],
        'LEGACY-BRINDLE-WALL-SCONCE' => ['on_hand' => 16, 'reorder_point' => 5, 'reorder_quantity' => 12],
        'LEGACY-LINEN-DRUM-SHADE' => ['on_hand' => 32, 'reorder_point' => 8, 'reorder_quantity' => 20],
        'LEGACY-CORVA-CEILING-DISC' => ['on_hand' => 14, 'reorder_point' => 5, 'reorder_quantity' => 10],
        'LEGACY-ODETTE-ARC-LAMP' => ['on_hand' => 12, 'reorder_point' => 4, 'reorder_quantity' => 8],
        'LEGACY-NIMBUS-SMART-DOWNLIGHT' => ['on_hand' => 36, 'reorder_point' => 10, 'reorder_quantity' => 24],
        'LEGACY-KELSO-SOLAR-BOLLARD' => ['on_hand' => 15, 'reorder_point' => 5, 'reorder_quantity' => 10],
        'LEGACY-ASHBY-TWIN-SCONCE' => ['on_hand' => 4, 'reorder_point' => 10, 'reorder_quantity' => 16],
        'LEGACY-ROWAN-ROTARY-DIMMER' => ['on_hand' => 3, 'reorder_point' => 8, 'reorder_quantity' => 18],
    ];

    /**
     * SKUs intentionally below their reorder point.
     *
     * @var list<string>
     */
    private const LOW_STOCK_SKUS = [
        'LEGACY-ASHBY-TWIN-SCONCE',
        'LEGACY-ROWAN-ROTARY-DIMMER',
    ];

    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return [
            'FrontendCatalogSeed',
        ];
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $connection = $this->cakeConnection();
        $ledger = new InventoryLedger();
        $work = function () use ($connection, $ledger): array {
            return $this->seedBalances($connection, $ledger);
        };

        $summary = $connection->inTransaction()
            ? $work()
            : $connection->transactional($work);

        fwrite(STDOUT, sprintf(
            "Opening stocktake: %d variants aligned, %d ledger movements written, %d already at target. Low stock: %s.\n",
            $summary['variants'],
            $summary['moved'],
            $summary['skipped'],
            implode(', ', self::LOW_STOCK_SKUS)
        ));
    }

    /**
     * @param \Cake\Database\Connection $connection Seed connection (already in a transaction).
     * @param \App\Service\Inventory\InventoryLedger $ledger Procedure wrapper.
     * @return array{variants: int, moved: int, skipped: int}
     */
    private function seedBalances(Connection $connection, InventoryLedger $ledger): array
    {
        $location = $ledger->ensureDefaultLocation();
        $locationId = (int)$location->id;
        $variants = $connection->execute(
            'SELECT id, sku FROM product_variants WHERE is_active = 1 ORDER BY id ASC',
        )->fetchAll('assoc');

        $moved = 0;
        $skipped = 0;
        foreach ($variants as $variant) {
            $variantId = (int)$variant['id'];
            $sku = (string)$variant['sku'];
            $plan = $this->planFor($sku);
            $current = $connection->execute(
                'SELECT quantity_on_hand, quantity_reserved
                   FROM inventory_balances
                  WHERE product_variant_id = ? AND inventory_location_id = ?',
                [$variantId, $locationId],
            )->fetch('assoc');
            $onHand = is_array($current) ? (int)$current['quantity_on_hand'] : 0;
            $reserved = is_array($current) ? (int)$current['quantity_reserved'] : 0;
            $target = max($plan['on_hand'], $reserved);
            $delta = $target - $onHand;
            if ($delta !== 0) {
                $ledger->applyInTransaction(
                    $variantId,
                    $locationId,
                    'count_adjustment',
                    $delta,
                    0,
                    'opening_stocktake',
                    null,
                    'Opening stocktake for storefront demo',
                    null,
                );
                $moved++;
            } else {
                $skipped++;
            }

            $this->upsertReorderRule($connection, $variantId, $locationId, $plan, $target);
            $this->setBalanceThresholds($connection, $variantId, $locationId, $plan);
        }

        return [
            'variants' => count($variants),
            'moved' => $moved,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param string $sku Variant SKU.
     * @return array{on_hand: int, reorder_point: int, reorder_quantity: int}
     */
    private function planFor(string $sku): array
    {
        if (isset(self::TARGETS[$sku])) {
            return self::TARGETS[$sku];
        }

        $spread = abs(crc32($sku)) % 29;

        return [
            'on_hand' => 12 + $spread,
            'reorder_point' => 5,
            'reorder_quantity' => 10,
        ];
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $variantId Variant.
     * @param int $locationId Location.
     * @param array{on_hand: int, reorder_point: int, reorder_quantity: int} $plan Demo plan.
     * @param int $target On-hand target actually applied.
     * @return void
     */
    private function upsertReorderRule(
        Connection $connection,
        int $variantId,
        int $locationId,
        array $plan,
        int $target,
    ): void {
        $sql = <<<'SQL'
INSERT INTO reorder_rules (
    product_variant_id, inventory_location_id, preferred_supplier_id,
    calculation_method, reorder_point, reorder_quantity, minimum_stock,
    maximum_stock, safety_stock, enabled, metadata, created, modified
) VALUES (?, ?, NULL, 'min_max', ?, ?, ?, ?, 0, 1, JSON_OBJECT('source', 'frontend-inventory-seed'), UTC_TIMESTAMP(6), UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE
    reorder_point = VALUES(reorder_point),
    reorder_quantity = VALUES(reorder_quantity),
    minimum_stock = VALUES(minimum_stock),
    maximum_stock = VALUES(maximum_stock),
    enabled = 1,
    modified = UTC_TIMESTAMP(6)
SQL;
        $connection->execute($sql, [
            $variantId,
            $locationId,
            $plan['reorder_point'],
            $plan['reorder_quantity'],
            $plan['reorder_point'],
            $target,
        ]);
    }

    /**
     * v_low_stock_items reads inventory_balances.reorder_point, not reorder_rules.
     * Thresholds are not stock quantities, so this UPDATE does not go through the
     * ledger procedure.
     *
     * @param \Cake\Database\Connection $connection Connection.
     * @param int $variantId Variant.
     * @param int $locationId Location.
     * @param array{on_hand: int, reorder_point: int, reorder_quantity: int} $plan Demo plan.
     * @return void
     */
    private function setBalanceThresholds(
        Connection $connection,
        int $variantId,
        int $locationId,
        array $plan,
    ): void {
        $connection->execute(
            'UPDATE inventory_balances
                SET reorder_point = ?, reorder_quantity = ?, modified = UTC_TIMESTAMP(6)
              WHERE product_variant_id = ? AND inventory_location_id = ?',
            [$plan['reorder_point'], $plan['reorder_quantity'], $variantId, $locationId],
        );
    }

    /**
     * CakePHP 5 migrations expose Cake\Database\Connection, not PDO.
     *
     * @return \Cake\Database\Connection
     */
    private function cakeConnection(): Connection
    {
        $connection = $this->getAdapter()->getConnection();
        if (!$connection instanceof Connection) {
            throw new RuntimeException('FrontendInventorySeed requires a CakePHP MySQL connection.');
        }

        return $connection;
    }
}
