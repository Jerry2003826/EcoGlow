<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Enquiry extends Entity
{

    protected array $_accessible = [
        'first_name' => true,
        'last_name' => true,
        'email' => true,
        'contact_number' => true,
        'enquiry_message' => true,
        'created' => true,
    ];
}
