<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SalesOrder extends Entity
{
    protected array $_accessible = [
        'order_number' => true,
        'customer_id' => true,
        'guest_name' => true,
        'guest_email' => true,
        'guest_phone' => true,
        'status' => true,
        'payment_status' => true,
        'currency' => true,
        'subtotal_cents' => true,
        'discount_cents' => true,
        'shipping_cents' => true,
        'tax_cents' => true,
        'grand_total_cents' => true,
        'placed_at' => true,
        'source_channel' => true,
        'created_by_user_id' => true,
        'created' => true,
        'modified' => true,
        'customer' => true,
    ];
}
