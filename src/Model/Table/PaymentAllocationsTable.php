<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Unique payment-to-invoice effects.
 */
class PaymentAllocationsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('payment_allocations');
        $this->setPrimaryKey('id');
    }
}
