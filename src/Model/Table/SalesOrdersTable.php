<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * SalesOrders Model
 *
 * @method \App\Model\Entity\SalesOrder newEmptyEntity()
 * @method \App\Model\Entity\SalesOrder get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SalesOrder saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SalesOrdersTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sales_orders');
        $this->setDisplayField('order_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->hasMany('SalesOrderItems', [
            'foreignKey' => 'sales_order_id',
            'sort' => ['SalesOrderItems.id' => 'ASC'],
        ]);
        $this->hasMany('OrderStatusHistory', [
            'foreignKey' => 'sales_order_id',
            'sort' => ['OrderStatusHistory.created' => 'ASC', 'OrderStatusHistory.id' => 'ASC'],
        ]);
        $this->hasMany('OrderNotes', [
            'foreignKey' => 'sales_order_id',
            'sort' => ['OrderNotes.created' => 'ASC', 'OrderNotes.id' => 'ASC'],
        ]);
        $this->hasMany('StockReservations', ['foreignKey' => 'sales_order_id']);
        $this->hasMany('OrderAddresses', ['foreignKey' => 'sales_order_id']);
        $this->hasMany('Payments', ['foreignKey' => 'sales_order_id']);
        $this->mapJsonColumns(['metadata']);
    }

    /**
     * Default contain graph for the admin detail page.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findDetail(SelectQuery $query): SelectQuery
    {
        return $query->contain([
            'Customers',
            'SalesOrderItems',
            'OrderStatusHistory' => ['Users'],
            'OrderNotes',
            'OrderAddresses',
            'Payments',
        ]);
    }
}
