<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * One captured side-effect per provider payment.
 */
class PaymentEffectsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('payment_effects');
        $this->setPrimaryKey('id');
    }
}
