<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\EnquiriesTable;
use Cake\TestSuite\TestCase;

class EnquiriesTableTest extends TestCase
{

    protected $Enquiries;

    protected array $fixtures = [
        'app.Enquiries',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Enquiries') ? [] : ['className' => EnquiriesTable::class];
        $this->Enquiries = $this->getTableLocator()->get('Enquiries', $config);
    }

    protected function tearDown(): void
    {
        unset($this->Enquiries);

        parent::tearDown();
    }

    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
