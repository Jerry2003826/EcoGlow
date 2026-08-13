<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SavedCartItem Entity
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property string|null $anonymous_token_hash
 * @property int $product_variant_id
 * @property int $quantity
 */
class SavedCartItem extends Entity
{
    /**
     * Saved lines are written by CartService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
