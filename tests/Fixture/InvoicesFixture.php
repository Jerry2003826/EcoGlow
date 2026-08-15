<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Invoices table truncated per test.
 */
class InvoicesFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'invoices';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
