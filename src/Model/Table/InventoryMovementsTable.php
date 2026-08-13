<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * InventoryMovements Model
 */
class InventoryMovementsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('inventory_movements');
        $this->setPrimaryKey('id');
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->belongsTo('InventoryLocations', ['foreignKey' => 'inventory_location_id']);
    }
}
