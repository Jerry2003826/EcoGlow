<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InventoryLocationsFixture
 */
class InventoryLocationsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'code' => 'MEL-WH',
                'name' => 'Melbourne warehouse',
                'address' => ['state' => 'VIC'],
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
