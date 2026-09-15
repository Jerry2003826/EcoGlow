<?php
// src/Model/Entity/Inventory.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Inventory extends Entity
{
    protected array $_accessible = [
        'ProductId' => true,
        'Quantity' => true,
        'LowStockThreshold' => true,
        'product' => true,
    ];
}
