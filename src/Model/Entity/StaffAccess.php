<?php
// src/Model/Entity/StaffAccess.php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class StaffAccess extends Entity
{
    protected array $_accessible = [
        'PermissionKey' => true,
        'ModuleLabel' => true,
        'Allowed' => true,
    ];
}
