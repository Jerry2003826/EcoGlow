<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InventoryBalancesFixture
 */
class InventoryBalancesFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'product_variant_id' => 1,
                'inventory_location_id' => 1,
                'quantity_on_hand' => 5,
                'quantity_reserved' => 0,
                'reorder_point' => 2,
                'reorder_quantity' => 4,
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'product_variant_id' => 2,
                'inventory_location_id' => 1,
                'quantity_on_hand' => 1,
                'quantity_reserved' => 0,
                'reorder_point' => 1,
                'reorder_quantity' => 2,
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
