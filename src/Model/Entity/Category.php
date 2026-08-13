<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Category Entity
 *
 * @property int $id
 * @property string $name
 */
class Category extends Entity
{
    /**
     * Catalogue is not edited in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
