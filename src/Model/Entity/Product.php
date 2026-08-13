<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Product Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $status
 */
class Product extends Entity
{
    /**
     * Catalogue editing is a later batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
