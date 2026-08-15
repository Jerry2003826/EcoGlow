<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Invoice status history table truncated per test.
 */
class InvoiceStatusHistoryFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'invoice_status_history';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
