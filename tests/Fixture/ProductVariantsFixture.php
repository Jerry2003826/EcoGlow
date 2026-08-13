<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProductVariantsFixture
 */
class ProductVariantsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'product_id' => 1,
                'sku' => 'EGL-MARLOW-01',
                'name' => 'Default',
                'attributes' => [],
                'price_cents' => 24900,
                'cost_cents' => 11000,
                'tax_rate' => '0.10000',
                'dimensions_mm' => [],
                'is_default' => 1,
                'is_active' => 1,
                'track_inventory' => 1,
                'allow_backorder' => 0,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'product_id' => 2,
                'sku' => 'EGL-PALMER-01',
                'name' => 'Default',
                'attributes' => [],
                'price_cents' => 18900,
                'cost_cents' => 8000,
                'tax_rate' => '0.10000',
                'dimensions_mm' => [],
                'is_default' => 1,
                'is_active' => 1,
                'track_inventory' => 1,
                'allow_backorder' => 0,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
