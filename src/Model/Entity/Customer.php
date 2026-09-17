<?php
// src/Model/Entity/Customer.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Customer extends Entity
{
    protected array $_accessible = [
        'CustomerName' => true,
        'Email' => true,
        'Address' => true,
        'Phone' => true,
        'orders' => true,
        'quotations' => true,
    ];
}
