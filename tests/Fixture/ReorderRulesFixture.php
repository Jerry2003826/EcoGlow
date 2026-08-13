<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ReorderRulesFixture
 */
class ReorderRulesFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'product_variant_id' => 1,
                'inventory_location_id' => 1,
                'calculation_method' => 'min_max',
                'reorder_point' => 2,
                'reorder_quantity' => 4,
                'enabled' => 1,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'product_variant_id' => 2,
                'inventory_location_id' => 1,
                'calculation_method' => 'min_max',
                'reorder_point' => 1,
                'reorder_quantity' => 2,
                'enabled' => 1,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
