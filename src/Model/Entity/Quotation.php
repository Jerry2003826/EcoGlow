<?php
// src/Model/Entity/Quotation.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Quotation extends Entity
{
    protected array $_accessible = [
        'CustomerId' => true,
        'QuotationDate' => true,
        'TotalAmount' => true,
        'Status' => true,
        'Notes' => true,
        'EmailedAt' => true,
        'customer' => true,
    ];
}
