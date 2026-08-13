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
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 2,
                'email' => 'staff@example.com',
                'password' => self::HASH,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'id' => 3,
                'email' => 'none@example.com',
                'password' => self::HASH,
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
