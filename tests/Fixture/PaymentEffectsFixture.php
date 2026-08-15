<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Payment effects table truncated per test.
 */
class PaymentEffectsFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'payment_effects';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
