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
        'address_line_1' => true,
        'address_line_2' => true,
        'suburb' => true,
        'state' => true,
        'postcode' => true,
        'country' => true,
        'age' => true,
        'company' => true,
        'status' => true,
        'source' => true,
        'created' => true,
        'modified' => true,
        'deleted' => true,
    ];
}
