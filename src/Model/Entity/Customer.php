<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Customer extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'email' => true,
        'phone' => true,
        'first_name' => true,
        'last_name' => true,
        'company' => true,
        'status' => true,
        'source' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
    ];
}
