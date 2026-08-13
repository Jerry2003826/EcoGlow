<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ReorderRules Model
 */
class ReorderRulesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('reorder_rules');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->belongsTo('InventoryLocations', ['foreignKey' => 'inventory_location_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
