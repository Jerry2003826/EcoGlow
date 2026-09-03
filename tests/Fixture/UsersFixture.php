<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'username' => 'Lorem ipsum dolor sit amet',
                'user_password' => 'Lorem ipsum dolor sit amet',
                'user_role' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
