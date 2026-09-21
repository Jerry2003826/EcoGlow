<?php
// src/Model/Entity/LandingBlock.php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LandingBlock extends Entity
{
    protected array $_accessible = [
        'BlockType' => true,
        'SortOrder' => true,
        'Title' => true,
        'Description' => true,
        'ButtonLabel' => true,
        'ButtonUrl' => true,
        'ImagePath' => true,
        'AltText' => true,
        'IsActive' => true,
    ];
}
