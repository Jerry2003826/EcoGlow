<?php
// src/Model/Entity/Quotation.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Quotation extends Entity
{
    protected array $_accessible = [
        'quotation_number' => true,
        'customer_id' => true,
        'category' => true,
        'status' => true,
        'valid_until' => true,
        'notes' => true,
        'subtotal_cents' => true,
        'tax_cents' => true,
        'grand_total_cents' => true,
        'emailed_at' => true,
        'created_by_user_id' => true,
        'quotation_items' => true,
        'customer' => true,
    ];
}
