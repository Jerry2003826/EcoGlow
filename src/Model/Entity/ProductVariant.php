<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProductVariant Entity
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string $name
 * @property int $price_cents
 * @property int|null $cost_cents
 * @property string $tax_rate
 * @property bool $is_active
 * @property bool $track_inventory
 * @property bool $allow_backorder
 * @property \App\Model\Entity\Product|null $product
 */
class ProductVariant extends Entity
{
    /**
     * Catalogue editing is a later batch.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
