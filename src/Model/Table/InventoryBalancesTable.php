<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * InventoryBalances Model
 *
 * quantity_available is a generated column (on-hand minus reserved). This
 * table is never UPDATEd from application code — stock changes go through
 * sp_apply_inventory_change_in_transaction.
 */
class InventoryBalancesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('inventory_balances');
        $this->setPrimaryKey(['product_variant_id', 'inventory_location_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->belongsTo('InventoryLocations', ['foreignKey' => 'inventory_location_id']);
    }
}
