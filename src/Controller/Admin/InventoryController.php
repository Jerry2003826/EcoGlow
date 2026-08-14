<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Inventory\InventoryLedger;
use Cake\Http\Response;
use Cake\Log\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Inventory list and reasoned stock adjustments.
 */
class InventoryController extends AdminController
{
    /**
     * Adjustment reasons. Quantity on the form is always a positive integer;
     * the reason supplies the sign and the movement_type written to the ledger.
     *
     * @var array<string, array{label: string, sign: int, type: string}>
     */
    public const REASONS = [
        'receipt' => [
            'label' => 'Goods received',
            'sign' => 1,
            'type' => 'receipt',
        ],
        'count_gain' => [
            'label' => 'Stocktake gain',
            'sign' => 1,
            'type' => 'count_adjustment',
        ],
        'count_loss' => [
            'label' => 'Stocktake loss',
            'sign' => -1,
            'type' => 'count_adjustment',
        ],
        'damage' => [
            'label' => 'Damage / breakage',
            'sign' => -1,
            'type' => 'damage',
        ],
        'shrinkage' => [
            'label' => 'Shrinkage / loss',
            'sign' => -1,
            'type' => 'shrinkage',
        ],
        'adjustment_in' => [
            'label' => 'Manual increase',
            'sign' => 1,
            'type' => 'adjustment',
        ],
        'adjustment_out' => [
            'label' => 'Manual decrease',
            'sign' => -1,
            'type' => 'adjustment',
        ],
    ];

    /**
     * @var \App\Service\Inventory\InventoryLedger
     */
    private InventoryLedger $ledger;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->ledger = new InventoryLedger();
    }

    /**
     * Stock table, optionally filtered by location.
     *
     * @return void
     */
    public function index(): void
    {
        $this->ledger->ensureDefaultLocation();
        $locationId = (int)$this->request->getQuery('location', 0);
        $locations = $this->fetchTable('InventoryLocations')->find()
            ->where(['is_active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();
        if ($locationId < 1 && $locations->count() > 0) {
            $locationId = (int)$locations->first()->id;
        }

        $connection = $this->fetchTable('ProductVariants')->getConnection();
        $sql = 'SELECT pv.id AS product_variant_id,
                       pv.sku,
                       p.name AS product_name,
                       pv.name AS variant_name,
                       il.id AS inventory_location_id,
                       il.name AS location_name,
                       COALESCE(ib.quantity_on_hand, 0) AS quantity_on_hand,
                       COALESCE(ib.quantity_reserved, 0) AS quantity_reserved,
                       COALESCE(ib.quantity_available, 0) AS quantity_available,
                       COALESCE(rr.reorder_point, ib.reorder_point, 0) AS reorder_point,
                       CASE
                         WHEN COALESCE(ib.quantity_available, 0) <= COALESCE(rr.reorder_point, ib.reorder_point, 0)
                              AND COALESCE(rr.reorder_point, ib.reorder_point, 0) > 0
                         THEN 1 ELSE 0
                       END AS needs_reorder
                  FROM product_variants pv
                  INNER JOIN products p ON p.id = pv.product_id
                  INNER JOIN inventory_locations il ON il.id = :location_id
                  LEFT JOIN inventory_balances ib
                    ON ib.product_variant_id = pv.id
                   AND ib.inventory_location_id = il.id
                  LEFT JOIN reorder_rules rr
                    ON rr.product_variant_id = pv.id
                   AND rr.inventory_location_id = il.id
                   AND rr.enabled = 1
                 WHERE pv.is_active = 1
                 ORDER BY needs_reorder DESC, p.name ASC, pv.sku ASC';
        $rows = $connection->execute($sql, ['location_id' => $locationId])->fetchAll('assoc');

        $canAdjust = $this->permissions->has($this->actorId(), 'inventory.adjust');
        $reasons = self::REASONS;

        $this->set(compact('rows', 'locations', 'locationId', 'canAdjust', 'reasons'));
    }

    /**
     * Apply a reasoned on-hand adjustment through the stored procedure.
     *
     * @return \Cake\Http\Response|null
     */
    public function adjust(): ?Response
    {
        $this->request->allowMethod(['post']);
        $variantId = (int)$this->request->getData('product_variant_id');
        $locationId = (int)$this->request->getData('inventory_location_id');
        $quantity = (int)$this->request->getData('quantity');
        $reasonKey = (string)$this->request->getData('reason');
        $note = trim((string)$this->request->getData('note'));

        if ($variantId < 1 || $locationId < 1) {
            $this->Flash->error(__('Choose a product and a location.'));

            return $this->redirect(['action' => 'index', '?' => ['location' => $locationId]]);
        }
        if ($quantity < 1) {
            $this->Flash->error(__('Enter a quantity of at least 1.'));

            return $this->redirect(['action' => 'index', '?' => ['location' => $locationId]]);
        }
        if (!isset(self::REASONS[$reasonKey])) {
            $this->Flash->error(__('Choose a reason for this stock change.'));

            return $this->redirect(['action' => 'index', '?' => ['location' => $locationId]]);
        }

        $reason = self::REASONS[$reasonKey];
        $delta = $quantity * $reason['sign'];
        $label = $reason['label'];
        if ($note !== '') {
            $label .= ': ' . $note;
        }

        try {
            $this->ledger->transactional(function () use ($variantId, $locationId, $reason, $delta, $label) {
                $this->ledger->applyInTransaction(
                    $variantId,
                    $locationId,
                    $reason['type'],
                    $delta,
                    0,
                    'manual_adjustment',
                    null,
                    $label,
                    $this->actorId(),
                );
            });
            $this->Flash->success(__('Stock updated ({0}).', $reason['label']));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        } catch (Throwable $exception) {
            $reference = strtoupper(bin2hex(random_bytes(4)));
            Log::error('Inventory adjust failed [' . $reference . ']: ' . $exception->getMessage());
            $this->Flash->error(__('The stock change could not be saved. Reference {0}.', $reference));
        }

        return $this->redirect(['action' => 'index', '?' => ['location' => $locationId]]);
    }
}
