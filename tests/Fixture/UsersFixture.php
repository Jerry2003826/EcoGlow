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
                // Hashed value of the plain-text password 'password'
                'password' => '$2y$12$hngbEPZOp.dqjti8iqiw.eEwjrPb9.LyPjh24vR1XWE4JMKWz0w5.',
                'created' => '2026-08-06 00:00:00',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
