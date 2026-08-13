<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Invoices Model
 *
 * @method \App\Model\Entity\Invoice newEmptyEntity()
 * @method \App\Model\Entity\Invoice get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Invoice saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class InvoicesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invoices');
        $this->setDisplayField('invoice_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->hasMany('InvoiceItems', [
            'foreignKey' => 'invoice_id',
            'sort' => ['InvoiceItems.line_number' => 'ASC'],
        ]);
        $this->hasMany('InvoiceStatusHistory', [
            'foreignKey' => 'invoice_id',
            'sort' => ['InvoiceStatusHistory.id' => 'ASC'],
        ]);
        $this->mapJsonColumns([
            'business_snapshot',
            'customer_snapshot',
            'billing_address_snapshot',
            'metadata',
        ]);
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findDetail(SelectQuery $query): SelectQuery
    {
        return $query->contain([
            'Customers',
            'SalesOrders',
            'InvoiceItems',
            'InvoiceStatusHistory' => ['Users'],
        ]);
    }
}
