<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Credit note items table truncated per test.
 */
class CreditNoteItemsFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'credit_note_items';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
