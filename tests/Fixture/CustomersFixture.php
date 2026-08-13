<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CustomersFixture
 */
class CustomersFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'email' => 'alex@example.com',
                'phone' => '0400000099',
                'first_name' => 'Alex',
                'last_name' => 'Nguyen',
                'status' => 'active',
                'source' => 'phone',
                'tags' => [],
                'customer_type' => 'individual',
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 4,
                'email' => 'customer-a@example.com',
                'phone' => '0400000004',
                'first_name' => 'Casey',
                'last_name' => 'Aitken',
                'status' => 'active',
                'source' => 'web',
                'tags' => [],
                'customer_type' => 'individual',
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 3,
                'user_id' => 5,
                'email' => 'customer-b@example.com',
                'phone' => '0400000005',
                'first_name' => 'Blair',
                'last_name' => 'Nguyen',
                'status' => 'active',
                'source' => 'web',
                'tags' => [],
                'customer_type' => 'individual',
                'metadata' => [],
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
