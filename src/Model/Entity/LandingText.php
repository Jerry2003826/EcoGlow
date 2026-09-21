<?php
// src/Model/Entity/LandingText.php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LandingText extends Entity
{
    protected array $_accessible = [
        'TextKey' => true,
        'TextValue' => true,
        'FieldLabel' => true,
        'FieldType' => true,
        'SortOrder' => true,
    ];
}
