<?php
// src/Model/Entity/ProductCategory.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ProductCategory extends Entity
{
    protected array $_accessible = [
        'CategoryName' => true,
        'products' => true,
    ];
}
