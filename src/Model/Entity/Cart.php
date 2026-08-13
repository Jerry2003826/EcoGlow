<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Cart Entity
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $anonymous_token_hash
 * @property string $status
 * @property array<\App\Model\Entity\CartItem> $cart_items
 */
class Cart extends Entity
{
    /**
     * Carts are written by CartService, never from a public form.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
