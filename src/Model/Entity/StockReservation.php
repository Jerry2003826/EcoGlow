<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * StockReservation Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $sales_order_item_id
 * @property int $product_variant_id
 * @property int $inventory_location_id
 * @property int $quantity
 * @property string $status
 */
class StockReservation extends Entity
{
    /**
     * Reservations are written by OrderService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
