<?php
// src/Model/Entity/Supplier.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Supplier extends Entity
{
    protected array $_accessible = [
        'SupplierName' => true,
        'Email' => true,
        'Phone' => true,
        'Address' => true,
        'products' => true,
    ];
}
