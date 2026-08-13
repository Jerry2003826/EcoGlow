<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * StockReservations Model
 *
 * @method \App\Model\Entity\StockReservation newEmptyEntity()
 * @method \App\Model\Entity\StockReservation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class StockReservationsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stock_reservations');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->belongsTo('SalesOrderItems', ['foreignKey' => 'sales_order_item_id']);
        $this->belongsTo('ProductVariants', ['foreignKey' => 'product_variant_id']);
        $this->belongsTo('InventoryLocations', ['foreignKey' => 'inventory_location_id']);
    }
}
