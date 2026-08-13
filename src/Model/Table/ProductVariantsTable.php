<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ProductVariants Model
 */
class ProductVariantsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('product_variants');
        $this->setDisplayField('sku');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Products', ['foreignKey' => 'product_id']);
        $this->hasMany('InventoryBalances', ['foreignKey' => 'product_variant_id']);
        $this->mapJsonColumns(['attributes', 'dimensions_mm', 'metadata']);
    }
}
