<?php
// src/Model/Entity/Enquiry.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Enquiry extends Entity
{
    protected array $_accessible = [
        'Name' => true,
        'Email' => true,
        'Subject' => true,
        'Message' => true,
    ];
}
