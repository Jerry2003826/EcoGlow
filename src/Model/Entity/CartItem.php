<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CartItem Entity
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property int $quantity
 * @property int $unit_price_snapshot_cents
 * @property \App\Model\Entity\ProductVariant|null $product_variant
 */
class CartItem extends Entity
{
    /**
     * Quantity is the only field a shopper form may submit.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'quantity' => true,
    ];
}
