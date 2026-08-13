<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * OrderStatusHistory Model
 *
 * @method \App\Model\Entity\OrderStatusHistory newEmptyEntity()
 * @method \App\Model\Entity\OrderStatusHistory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class OrderStatusHistoryTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('order_status_history');
        $this->setPrimaryKey('id');
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->belongsTo('Users', [
            'foreignKey' => 'changed_by_user_id',
        ]);
    }
}
