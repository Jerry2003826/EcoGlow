<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * SalesOrderItems Model
 *
 * @method \App\Model\Entity\SalesOrderItem newEmptyEntity()
 * @method \App\Model\Entity\SalesOrderItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SalesOrderItemsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sales_order_items');
        $this->setDisplayField('item_name_snapshot');
        $this->setPrimaryKey('id');
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->belongsTo('Products', ['foreignKey' => 'product_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->hasMany('StockReservations', ['foreignKey' => 'sales_order_item_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
