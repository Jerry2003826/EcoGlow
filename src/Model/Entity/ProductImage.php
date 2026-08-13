<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProductImage Entity
 *
 * @property int $id
 * @property int $product_id
 * @property string $image_url
 * @property string|null $image_role
 */
class ProductImage extends Entity
{
    /**
     * Catalogue editing is a later batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
