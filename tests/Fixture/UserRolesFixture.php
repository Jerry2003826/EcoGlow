<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UserRolesFixture
 */
class UserRolesFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'user_id' => 1,
                'role_id' => 1,
                'business_id' => null,
                'starts_at' => '2026-01-01 00:00:00',
                'ends_at' => null,
                'revoked_at' => null,
                'created' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'role_id' => 3,
                'business_id' => null,
                'starts_at' => '2026-01-01 00:00:00',
                'ends_at' => null,
                'revoked_at' => null,
                'created' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
