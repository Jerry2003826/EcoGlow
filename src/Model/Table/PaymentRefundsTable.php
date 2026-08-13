<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * PaymentRefunds Model
 *
 * @method \App\Model\Entity\PaymentRefund newEmptyEntity()
 * @method \App\Model\Entity\PaymentRefund saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class PaymentRefundsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payment_refunds');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Payments', ['foreignKey' => 'payment_id']);
        $this->mapJsonColumns(['provider_metadata']);
    }
}
