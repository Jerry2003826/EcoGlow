<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Invoice items table truncated per test.
 */
class InvoiceItemsFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'invoice_items';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
