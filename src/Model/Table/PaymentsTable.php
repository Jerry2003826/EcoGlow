<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Payments Model
 *
 * @method \App\Model\Entity\Payment newEmptyEntity()
 * @method \App\Model\Entity\Payment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class PaymentsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payments');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
        $this->mapJsonColumns(['provider_metadata']);
    }
}
