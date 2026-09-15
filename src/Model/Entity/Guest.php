<?php
// src/Model/Entity/Guest.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Guest extends Entity
{
    protected array $_accessible = [
        'GuestName' => true,
        'Email' => true,
        'Address' => true,
        'Phone' => true,
    ];
}
