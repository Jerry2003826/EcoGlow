<?php
// src/Model/Entity/Invoice.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Invoice extends Entity
{
    protected array $_accessible = [
        'OrderId' => true,
        'InvoiceDate' => true,
        'TotalAmount' => true,
        'order' => true,
    ];
}
