<?php
// src/Model/Entity/Delivery.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Delivery extends Entity
{
    protected array $_accessible = [
        'OrderId' => true,
        'DeliveryAddress' => true,
        'DeliveryStatus' => true,
        'DeliveryDate' => true,
        'order' => true,
    ];
}
