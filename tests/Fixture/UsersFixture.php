<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Hashed value of the plain-text password 'password'
     *
     * @var string
     */
    private const HASH = '$2y$12$hngbEPZOp.dqjti8iqiw.eEwjrPb9.LyPjh24vR1XWE4JMKWz0w5.';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'email' => 'admin@example.com',
                'password' => self::HASH,
                'first_name' => 'Ada',
                'last_name' => 'Admin',
                'role' => 'owner',
                'status' => 'active',
                'auth_version' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'email' => 'staff@example.com',
                'password' => self::HASH,
                'first_name' => 'Sam',
                'last_name' => 'Staff',
                'role' => 'staff',
                'status' => 'active',
                'auth_version' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 3,
                'email' => 'none@example.com',
                'password' => self::HASH,
                'first_name' => 'No',
                'last_name' => 'Access',
                'role' => 'staff',
                'status' => 'active',
                'auth_version' => 1,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 4,
                'email' => 'customer-a@example.com',
                'password' => self::HASH,
                'first_name' => 'Casey',
                'last_name' => 'Aitken',
                'phone' => '0400000004',
                'role' => 'customer',
                'status' => 'active',
                'auth_version' => 1,
                'email_verified_at' => '2026-08-06 00:00:00',
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 5,
                'email' => 'customer-b@example.com',
                'password' => self::HASH,
                'first_name' => 'Blair',
                'last_name' => 'Nguyen',
                'phone' => '0400000005',
                'role' => 'customer',
                'status' => 'active',
                'auth_version' => 1,
                'email_verified_at' => '2026-08-06 00:00:00',
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
