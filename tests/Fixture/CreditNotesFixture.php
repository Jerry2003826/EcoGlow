<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Credit notes table truncated per test.
 */
class CreditNotesFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'credit_notes';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}
