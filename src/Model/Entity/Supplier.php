<?php
// src/Model/Entity/Supplier.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Supplier extends Entity
{
    protected array $_accessible = [
        'code' => true,
        'name' => true,
        'email' => true,
        'phone' => true,
        'address_line_1' => true,
        'suburb' => true,
        'state' => true,
        'postcode' => true,
        'notes' => true,
        'created' => true,
        'modified' => true,
        'products' => true,
    ];
}
