<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SalesOrderItem Entity
 *
 * @property int $id
 * @property int $sales_order_id
 * @property string $item_type
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string|null $sku_snapshot
 * @property string $item_name_snapshot
 * @property string|null $variant_name_snapshot
 * @property int $quantity
 * @property int $unit_price_cents
 * @property int $discount_cents
 * @property int $tax_cents
 * @property int $line_total_cents
 * @property int|null $cost_snapshot_cents
 * @property string|null $tax_rate_snapshot
 * @property array|null $metadata
 */
class SalesOrderItem extends Entity
{
    /**
     * Line snapshots are written by OrderService, never from request data.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
