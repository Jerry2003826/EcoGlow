<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InventoryMovement Entity
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $inventory_location_id
 * @property string $movement_type
 * @property int $on_hand_delta
 * @property int $reserved_delta
 */
class InventoryMovement extends Entity
{
    /**
     * Ledger rows are written by the stored procedure.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
