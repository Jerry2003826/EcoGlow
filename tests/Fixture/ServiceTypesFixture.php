<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Service types for booking tests.
 */
class ServiceTypesFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Installation',
                'description' => 'Licensed installation',
                'base_price_cents' => 15000,
                'default_duration_minutes' => 120,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Repair',
                'description' => 'On-site repair',
                'base_price_cents' => 9000,
                'default_duration_minutes' => 90,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
