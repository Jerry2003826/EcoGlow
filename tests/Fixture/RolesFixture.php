<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesFixture
 */
class RolesFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'business_id' => null,
                'role_key' => 'master',
                'name' => 'Master access',
                'description' => 'Product Owner full access',
                'is_system' => 1,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'business_id' => null,
                'role_key' => 'elevated_staff',
                'name' => 'Elevated staff',
                'description' => 'Nominated staff with near/PO-equivalent access',
                'is_system' => 1,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 3,
                'business_id' => null,
                'role_key' => 'standard_staff',
                'name' => 'Standard staff',
                'description' => 'Restricted operational access',
                'is_system' => 1,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 4,
                'business_id' => null,
                'role_key' => 'customer',
                'name' => 'Customer',
                'description' => 'Customer portal access',
                'is_system' => 1,
                'is_active' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
