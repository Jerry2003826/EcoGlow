<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrderStatusHistoryFixture
 */
class OrderStatusHistoryFixture extends TestFixture
{
    /**
     * Physical table — inflection would look for order_status_histories.
     *
     * @var string
     */
    public string $table = 'order_status_history';
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
