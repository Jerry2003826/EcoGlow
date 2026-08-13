<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProductsFixture
 */
class ProductsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'slug' => 'marlow-floor-lamp',
                'name' => 'Marlow Floor Lamp',
                'product_type' => 'floor-lamp',
                'status' => 'active',
                'specifications' => [],
                'tags' => [],
                'is_featured' => 0,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'slug' => 'palmer-pendant',
                'name' => 'Palmer Pendant',
                'product_type' => 'pendant',
                'status' => 'active',
                'specifications' => [],
                'tags' => [],
                'is_featured' => 0,
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
