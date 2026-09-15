<?php
// src/Model/Entity/Product.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Product extends Entity
{
    protected array $_accessible = [
        'CategoryId' => true,
        'SupplierId' => true,
        'ProductName' => true,
        'Description' => true,
        'Price' => true,
        'Image' => true,
        'product_category' => true,
        'supplier' => true,
        'inventory' => true,
    ];
}
