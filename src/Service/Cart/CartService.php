<?php
declare(strict_types=1);

namespace App\Service\Cart;

use App\Model\Entity\Cart;
use App\Model\Entity\CartItem;
use App\Model\Entity\ProductVariant;
use App\Service\Money;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use Throwable;

/**
 * Persistent baskets: anonymous session token, customer merge, save-for-later.
 *
 * Line totals are always recomputed from current variant prices. Posted
 * amounts are ignored.
 */
class CartService
{
    use LocatorAwareTrait;

    public const SESSION_KEY = 'Cart.token';

    public const DEFAULT_FREE_THRESHOLD_CENTS = 15000;

    public const DEFAULT_FLAT_RATE_CENTS = 1495;

    public const DEFAULT_GST_RATE = '0.1';

    /**
     * Raw anonymous token stored in the session.
     *
     * @param \Cake\Http\ServerRequest $request Request.
     * @return string
     */
    public function token(ServerRequest $request): string
    {
        $session = $request->getSession();
        $token = (string)$session->read(self::SESSION_KEY);
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $session->write(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * @param string $token Raw session token.
     * @return string
     */
    public function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Active cart for a signed-in user or an anonymous token.
     *
     * @param int|null $userId Authenticated user id.
     * @param string $token Raw anonymous token.
     * @param bool $create Whether to insert an empty cart.
     * @return \App\Model\Entity\Cart|null
     */
    public function current(?int $userId, string $token, bool $create = true): ?Cart
    {
        $carts = $this->fetchTable('Carts');
        $hash = $this->tokenHash($token);

        if ($userId) {
            $cart = $carts->find()
                ->contain($this->containGraph())
                ->where(['Carts.user_id' => $userId, 'Carts.status' => 'active'])
                ->first();
            if ($cart) {
                return $cart;
            }
        }

        $cart = $carts->find()
            ->contain($this->containGraph())
            ->where(['Carts.anonymous_token_hash' => $hash, 'Carts.status' => 'active'])
            ->first();
        if ($cart && $userId) {
            $cart->set('user_id', $userId);
            $cart->set('anonymous_token_hash', null);
            $carts->saveOrFail($cart);

            return $this->reload($cart);
        }
        if ($cart || !$create) {
            return $cart;
        }

        $cart = $carts->newEmptyEntity();
        if ($userId) {
            $cart->set('user_id', $userId);
        } else {
            $cart->set('anonymous_token_hash', $hash);
        }
        $cart->set('status', 'active');
        $cart->set('currency', 'AUD');
        $carts->saveOrFail($cart);

        return $carts->get($cart->id, contain: $this->containGraph());
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $variantId Variant id.
     * @param int $quantity Quantity.
     * @param bool $requireStock When false, login merge may exceed available stock;
     *     checkout still refuses short stock.
     * @return \App\Model\Entity\Cart
     */
    public function add(Cart $cart, int $variantId, int $quantity, bool $requireStock = true): Cart
    {
        $variant = $this->requireActiveVariant($variantId);
        $this->assertQuantity($quantity);
        if ($requireStock) {
            $this->assertStock($variant, $this->quantityInCart($cart, $variantId) + $quantity);
        }
        $price = (int)$variant->get('price_cents');
        $items = $this->fetchTable('CartItems');
        $existing = $items->find()
            ->where(['cart_id' => $cart->id, 'product_variant_id' => $variantId])
            ->first();
        if ($existing) {
            $existing->set('quantity', min(99, (int)$existing->quantity + $quantity));
            $existing->set('unit_price_snapshot_cents', $price);
            $items->saveOrFail($existing);
        } else {
            $item = $items->newEmptyEntity();
            $item->set('cart_id', $cart->id);
            $item->set('product_variant_id', $variantId);
            $item->set('quantity', $quantity);
            $item->set('unit_price_snapshot_cents', $price);
            $items->saveOrFail($item);
        }

        return $this->reload($cart);
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $itemId Cart item id.
     * @param int $quantity Quantity.
     * @return \App\Model\Entity\Cart
     */
    public function updateQuantity(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $this->ownedItem($cart, $itemId);
        if ($quantity < 1) {
            $this->fetchTable('CartItems')->deleteOrFail($item);

            return $this->reload($cart);
        }
        $this->assertQuantity($quantity);
        $variant = $this->requireActiveVariant((int)$item->product_variant_id);
        $this->assertStock($variant, $quantity);
        $item->set('quantity', $quantity);
        $item->set('unit_price_snapshot_cents', (int)$variant->get('price_cents'));
        $this->fetchTable('CartItems')->saveOrFail($item);

        return $this->reload($cart);
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $itemId Cart item id.
     * @return \App\Model\Entity\Cart
     */
    public function remove(Cart $cart, int $itemId): Cart
    {
        $item = $this->ownedItem($cart, $itemId);
        $this->fetchTable('CartItems')->deleteOrFail($item);

        return $this->reload($cart);
    }

    /**
     * Move a cart line onto saved_cart_items.
     *
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $itemId Cart item id.
     * @param int|null $customerId Customer id when signed in.
     * @param int|null $userId User id when signed in.
     * @param string $token Raw anonymous token.
     * @return \App\Model\Entity\Cart
     */
    public function saveForLater(
        Cart $cart,
        int $itemId,
        ?int $customerId,
        ?int $userId,
        string $token,
    ): Cart {
        $item = $this->ownedItem($cart, $itemId);
        $this->upsertSaved(
            (int)$item->product_variant_id,
            (int)$item->quantity,
            $customerId,
            $userId,
            $token,
            (int)$cart->id,
        );
        $this->fetchTable('CartItems')->deleteOrFail($item);

        return $this->reload($cart);
    }

    /**
     * Move a saved line back into the cart.
     *
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $savedId saved_cart_items.id
     * @param int|null $customerId Customer id.
     * @param int|null $userId User id.
     * @param string $token Raw token.
     * @return \App\Model\Entity\Cart
     */
    public function moveToCart(
        Cart $cart,
        int $savedId,
        ?int $customerId,
        ?int $userId,
        string $token,
    ): Cart {
        $saved = $this->ownedSaved($savedId, $customerId, $userId, $token);
        $this->add($cart, (int)$saved->product_variant_id, (int)$saved->quantity);
        $this->fetchTable('SavedCartItems')->deleteOrFail($saved);

        return $this->reload($cart);
    }

    /**
     * Merge an anonymous cart into the signed-in customer's cart.
     *
     * @param int $userId User id.
     * @param string $token Raw anonymous token.
     * @return void
     */
    public function mergeOnLogin(int $userId, string $token): void
    {
        $this->connection()->transactional(function () use ($userId, $token): void {
            $hash = $this->tokenHash($token);
            $carts = $this->fetchTable('Carts');
            $anonymous = $carts->find()
                ->contain(['CartItems'])
                ->where(['Carts.anonymous_token_hash' => $hash, 'Carts.status' => 'active'])
                ->first();
            $userCart = $this->current($userId, $token, true);
            if ($userCart === null) {
                return;
            }

            if ($anonymous && (int)$anonymous->id !== (int)$userCart->id) {
                foreach ($anonymous->cart_items ?? [] as $item) {
                    $this->add(
                        $userCart,
                        (int)$item->product_variant_id,
                        (int)$item->quantity,
                        false,
                    );
                    $userCart = $this->reload($userCart);
                }
                $anonymous->set('status', 'merged');
                $anonymous->set('anonymous_token_hash', null);
                $carts->saveOrFail($anonymous);
            }

            $customer = $this->fetchTable('Customers')->find()
                ->where(['user_id' => $userId])
                ->first();
            $saved = $this->fetchTable('SavedCartItems')->find()
                ->where(['anonymous_token_hash' => $hash])
                ->all();
            foreach ($saved as $row) {
                $row->set('user_id', $userId);
                $row->set('customer_id', $customer?->id);
                $row->set('anonymous_token_hash', null);
                $this->fetchTable('SavedCartItems')->saveOrFail($row);
            }
        });
    }

    /**
     * Totals in integer cents, from live prices and site_settings.
     *
     * @param \App\Model\Entity\Cart|null $cart Cart.
     * @return array<string, int|string>
     */
    public function totals(?Cart $cart): array
    {
        $settings = $this->pricingSettings();
        $subtotal = 0;
        foreach ($cart->cart_items ?? [] as $item) {
            $price = $this->livePrice((int)$item->product_variant_id);
            $subtotal += $price * (int)$item->quantity;
        }
        $shipping = $subtotal === 0 || $subtotal >= $settings['free_threshold_cents']
            ? 0
            : $settings['flat_rate_cents'];
        $total = $subtotal + $shipping;
        $gst = Money::gstPortionInclusive($total, $settings['gst_rate']);

        return [
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $total,
            'gst_cents' => $gst,
            'free_threshold_cents' => $settings['free_threshold_cents'],
            'flat_rate_cents' => $settings['flat_rate_cents'],
            'gst_rate' => $settings['gst_rate'],
            'away_cents' => max(0, $settings['free_threshold_cents'] - $subtotal),
        ];
    }

    /**
     * Template lines for the existing cart markup.
     *
     * @param \App\Model\Entity\Cart|null $cart Cart.
     * @return list<array<string, mixed>>
     */
    public function viewLines(?Cart $cart): array
    {
        $lines = [];
        foreach ($cart->cart_items ?? [] as $item) {
            $lines[] = $this->lineFromItem($item);
        }

        return $lines;
    }

    /**
     * @param int|null $customerId Customer id.
     * @param int|null $userId User id.
     * @param string $token Raw token.
     * @return list<array<string, mixed>>
     */
    public function savedLines(?int $customerId, ?int $userId, string $token): array
    {
        $conditions = [];
        if ($customerId) {
            $conditions['SavedCartItems.customer_id'] = $customerId;
        } elseif ($userId) {
            $conditions['SavedCartItems.user_id'] = $userId;
        } else {
            $conditions['SavedCartItems.anonymous_token_hash'] = $this->tokenHash($token);
        }

        $rows = $this->fetchTable('SavedCartItems')->find()
            ->contain([
                'ProductVariants' => ['Products' => ['ProductImages']],
            ])
            ->where($conditions)
            ->orderBy(['SavedCartItems.id' => 'ASC'])
            ->all();

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = $this->lineFromSaved($row);
        }

        return $lines;
    }

    /**
     * @return array{free_threshold_cents: int, flat_rate_cents: int, gst_rate: string}
     */
    public function pricingSettings(): array
    {
        return [
            'free_threshold_cents' => $this->settingInt(
                'shipping.free_threshold_cents',
                self::DEFAULT_FREE_THRESHOLD_CENTS,
            ),
            'flat_rate_cents' => $this->settingInt(
                'shipping.standard_flat_rate_cents',
                self::DEFAULT_FLAT_RATE_CENTS,
            ),
            'gst_rate' => $this->settingRate('tax.gst_rate', self::DEFAULT_GST_RATE),
        ];
    }

    /**
     * @param int $variantId Variant id.
     * @return int
     */
    public function livePrice(int $variantId): int
    {
        return (int)$this->requireActiveVariant($variantId)->get('price_cents');
    }

    /**
     * @param int $variantId Variant id.
     * @return \App\Model\Entity\ProductVariant
     */
    private function requireActiveVariant(int $variantId): ProductVariant
    {
        if ($variantId < 1) {
            throw new InvalidArgumentException('That product is no longer in the catalogue.');
        }
        try {
            /** @var \App\Model\Entity\ProductVariant $variant */
            $variant = $this->fetchTable('ProductVariants')->get($variantId);
        } catch (RecordNotFoundException) {
            throw new InvalidArgumentException('That product is no longer in the catalogue.');
        }
        if (!$variant->get('is_active')) {
            throw new InvalidArgumentException('That finish is no longer available.');
        }

        return $variant;
    }

    /**
     * @param int $quantity Requested quantity.
     * @return void
     */
    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Please choose a quantity of at least 1.');
        }
        if ($quantity > 99) {
            throw new InvalidArgumentException('You can add at most 99 of this item.');
        }
    }

    /**
     * @param \App\Model\Entity\ProductVariant $variant Variant.
     * @param int $needed Units already in the basket plus the new request.
     * @return void
     */
    private function assertStock(ProductVariant $variant, int $needed): void
    {
        if (!$variant->get('track_inventory') || $variant->get('allow_backorder')) {
            return;
        }
        $available = $this->availableUnits((int)$variant->id);
        if ($needed <= $available) {
            return;
        }
        if ($available < 1) {
            throw new InvalidArgumentException('This item is temporarily out of stock.');
        }

        throw new InvalidArgumentException(sprintf(
            'Only %d of this item %s left.',
            $available,
            $available === 1 ? 'is' : 'are',
        ));
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $variantId Variant id.
     * @return int
     */
    private function quantityInCart(Cart $cart, int $variantId): int
    {
        $item = $this->fetchTable('CartItems')->find()
            ->where(['cart_id' => $cart->id, 'product_variant_id' => $variantId])
            ->first();

        return $item ? (int)$item->quantity : 0;
    }

    /**
     * @param int $variantId Variant id.
     * @return int
     */
    private function availableUnits(int $variantId): int
    {
        $row = $this->connection()->execute(
            'SELECT COALESCE(SUM(quantity_available), 0) AS available
               FROM inventory_balances
              WHERE product_variant_id = ?',
            [$variantId],
            ['integer'],
        )->fetch('assoc');

        return is_array($row) ? (int)$row['available'] : 0;
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @return \App\Model\Entity\Cart
     */
    private function reload(Cart $cart): Cart
    {
        return $this->fetchTable('Carts')->get($cart->id, contain: $this->containGraph());
    }

    /**
     * @return array<string, mixed>
     */
    private function containGraph(): array
    {
        return [
            'CartItems' => [
                'ProductVariants' => [
                    'Products' => ['ProductImages'],
                ],
            ],
        ];
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @param int $itemId Item id.
     * @return \App\Model\Entity\CartItem
     */
    private function ownedItem(Cart $cart, int $itemId): CartItem
    {
        $item = $this->fetchTable('CartItems')->find()
            ->where(['id' => $itemId, 'cart_id' => $cart->id])
            ->first();
        if ($item === null) {
            throw new InvalidArgumentException('That basket line is no longer there.');
        }

        return $item;
    }

    /**
     * @param int $savedId Saved id.
     * @param int|null $customerId Customer id.
     * @param int|null $userId User id.
     * @param string $token Raw token.
     * @return \App\Model\Entity\SavedCartItem
     */
    private function ownedSaved(int $savedId, ?int $customerId, ?int $userId, string $token): mixed
    {
        $conditions = ['SavedCartItems.id' => $savedId];
        if ($customerId) {
            $conditions['SavedCartItems.customer_id'] = $customerId;
        } elseif ($userId) {
            $conditions['SavedCartItems.user_id'] = $userId;
        } else {
            $conditions['SavedCartItems.anonymous_token_hash'] = $this->tokenHash($token);
        }
        $row = $this->fetchTable('SavedCartItems')->find()->where($conditions)->first();
        if ($row === null) {
            throw new InvalidArgumentException('That saved item is no longer there.');
        }

        return $row;
    }

    /**
     * @param int $variantId Variant id.
     * @param int $quantity Quantity.
     * @param int|null $customerId Customer id.
     * @param int|null $userId User id.
     * @param string $token Raw token.
     * @param int $cartId Source cart.
     * @return void
     */
    private function upsertSaved(
        int $variantId,
        int $quantity,
        ?int $customerId,
        ?int $userId,
        string $token,
        int $cartId,
    ): void {
        $saved = $this->fetchTable('SavedCartItems');
        $conditions = ['product_variant_id' => $variantId];
        if ($customerId) {
            $conditions['customer_id'] = $customerId;
        } elseif ($userId) {
            $conditions['user_id'] = $userId;
        } else {
            $conditions['anonymous_token_hash'] = $this->tokenHash($token);
        }
        $row = $saved->find()->where($conditions)->first();
        if ($row) {
            $row->set('quantity', min(99, (int)$row->quantity + $quantity));
            $saved->saveOrFail($row);

            return;
        }
        $row = $saved->newEmptyEntity();
        $row->set('product_variant_id', $variantId);
        $row->set('quantity', $quantity);
        $row->set('saved_from_cart_id', $cartId);
        $row->set('customer_id', $customerId);
        $row->set('user_id', $userId);
        if (!$customerId && !$userId) {
            $row->set('anonymous_token_hash', $this->tokenHash($token));
        }
        $saved->saveOrFail($row);
    }

    /**
     * @param \App\Model\Entity\CartItem $item Item.
     * @return array<string, mixed>
     */
    private function lineFromItem(CartItem $item): array
    {
        $mapped = $this->mapVariant($item->product_variant, (int)$item->quantity);
        $mapped['id'] = (int)$item->id;
        $mapped['price'] = $this->livePrice((int)$item->product_variant_id) / 100;

        return $mapped;
    }

    /**
     * @param \App\Model\Entity\SavedCartItem $row Saved row.
     * @return array<string, mixed>
     */
    private function lineFromSaved(mixed $row): array
    {
        $mapped = $this->mapVariant($row->product_variant, (int)$row->quantity);
        $mapped['id'] = (int)$row->id;
        $mapped['price'] = $this->livePrice((int)$row->product_variant_id) / 100;

        return $mapped;
    }

    /**
     * @param \App\Model\Entity\ProductVariant|null $variant Variant.
     * @param int $qty Quantity.
     * @return array<string, mixed>
     */
    private function mapVariant(mixed $variant, int $qty): array
    {
        $product = $variant?->product;
        $meta = is_array($product?->get('metadata')) ? $product->get('metadata') : [];
        $legacy = is_array($meta['legacy_frontend'] ?? null) ? $meta['legacy_frontend'] : [];
        $image = $legacy['image'] ?? 'marlow-floor-lamp.webp';
        foreach ($product->product_images ?? [] as $img) {
            if ((string)$img->get('image_role') === 'listing_primary') {
                $image = basename((string)$img->get('image_url'));
                break;
            }
        }
        $attributes = is_array($variant?->get('attributes')) ? $variant->get('attributes') : [];
        $swatches = $attributes['swatches'] ?? [];
        $finish = is_array($swatches[0] ?? null) ? (string)$swatches[0][0] : (string)($variant?->name ?? 'Default');

        return [
            'image' => $image,
            'name' => (string)($product?->name ?? $variant?->name ?? 'Item'),
            'meta' => (string)($product?->get('short_description') ?: ($legacy['meta'] ?? '')),
            'variant' => $finish,
            'qty' => $qty,
            'slug' => (string)($product?->slug ?? ''),
        ];
    }

    /**
     * @param string $key Setting key.
     * @param int $default Default cents.
     * @return int
     */
    private function settingInt(string $key, int $default): int
    {
        try {
            $row = $this->fetchTable('SiteSettings')->find()
                ->where(['setting_key' => $key])
                ->first();
        } catch (Throwable $exception) {
            return $default;
        }
        if ($row === null) {
            return $default;
        }
        $value = $row->get('setting_value');
        if (is_array($value)) {
            $value = $value['value'] ?? reset($value);
        }

        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * @param string $key Setting key.
     * @param string $default Default rate.
     * @return string
     */
    private function settingRate(string $key, string $default): string
    {
        try {
            $row = $this->fetchTable('SiteSettings')->find()
                ->where(['setting_key' => $key])
                ->first();
        } catch (Throwable $exception) {
            return $default;
        }
        if ($row === null) {
            return $default;
        }
        $value = $row->get('setting_value');
        if (is_array($value)) {
            $value = $value['value'] ?? reset($value);
        }

        return is_numeric($value) ? (string)$value : $default;
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Carts')->getConnection();
    }
}
