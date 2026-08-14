<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Financial/security alerts for Stripe binding failures.
 */
class PaymentReconciliationAlertsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('payment_reconciliation_alerts');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('event_id')
            ->maxLength('event_id', 64)
            ->requirePresence('event_id', 'create')
            ->notEmptyString('event_id');
        $validator
            ->scalar('reason')
            ->maxLength('reason', 64)
            ->requirePresence('reason', 'create')
            ->notEmptyString('reason');

        return $validator;
    }
}
