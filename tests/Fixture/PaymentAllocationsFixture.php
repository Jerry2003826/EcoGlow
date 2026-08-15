<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Payment allocations table truncated per test.
 */
class PaymentAllocationsFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'payment_allocations';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
