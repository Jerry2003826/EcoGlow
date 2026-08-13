<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * OrderAddresses Model
 *
 * @method \App\Model\Entity\OrderAddress newEmptyEntity()
 * @method \App\Model\Entity\OrderAddress saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class OrderAddressesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('order_addresses');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);
        $this->belongsTo('SalesOrders', ['foreignKey' => 'sales_order_id']);
    }
}
