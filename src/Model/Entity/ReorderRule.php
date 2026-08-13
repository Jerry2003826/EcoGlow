<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ReorderRule Entity
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $inventory_location_id
 * @property int $reorder_point
 * @property bool $enabled
 */
class ReorderRule extends Entity
{
    /**
     * Reorder rules are not edited from a form in this batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
