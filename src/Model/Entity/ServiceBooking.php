<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ServiceBooking extends Entity
{
    protected array $_accessible = [
        'booking_number' => true,
        'customer_id' => true,
        'guest_name' => true,
        'guest_email' => true,
        'guest_phone' => true,
        'service_type' => true,
        'status' => true,
        'scheduled_at' => true,
        'notes' => true,
        'created_by_user_id' => true,
        'created' => true,
        'modified' => true,
        'customer' => true,
    ];
}
