<?php
// src/Model/Entity/ServiceRequest.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ServiceRequest extends Entity
{
    protected array $_accessible = [
        'CustomerId' => true,
        'RequestType' => true,
        'Description' => true,
        'RequestedDate' => true,
        'customer' => true,
    ];
}
