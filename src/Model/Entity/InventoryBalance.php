<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InventoryBalance Entity
 *
 * @property int $product_variant_id
 * @property int $inventory_location_id
 * @property int $quantity_on_hand
 * @property int $quantity_reserved
 * @property int $quantity_available
 * @property int $reorder_point
 */
class InventoryBalance extends Entity
{
    /**
     * Balances are only mutated through the inventory stored procedure.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
