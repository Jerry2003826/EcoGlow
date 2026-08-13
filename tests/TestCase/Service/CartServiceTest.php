<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Cart\CartService;
use App\Service\Money;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Basket merge, save-for-later, and site_settings pricing.
 */
class CartServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Customers',
        'app.Products',
        'app.ProductVariants',
        'app.SiteSettings',
    ];

    /**
     * Anonymous lines are added onto the signed-in cart; quantities stack.
     *
     * @return void
     */
    public function testMergeOnLoginKeepsEveryLine(): void
    {
        $carts = new CartService();
        $token = 'anon-token-merge-test';

        $anonymous = $carts->current(null, $token, true);
        $this->assertNotNull($anonymous);
        $carts->add($anonymous, 1, 2);
        $carts->add($anonymous, 2, 1);

        $userCart = $carts->current(4, 'unused-user-token', true);
        $this->assertNotNull($userCart);
        $carts->add($userCart, 1, 1);

        $carts->mergeOnLogin(4, $token);

        $merged = $carts->current(4, $token, false);
        $this->assertNotNull($merged);
        $byVariant = [];
        foreach ($merged->cart_items as $item) {
            $byVariant[(int)$item->product_variant_id] = (int)$item->quantity;
        }
        $this->assertSame(3, $byVariant[1]);
        $this->assertSame(1, $byVariant[2]);

        $anonymous = $this->fetchTable('Carts')->get($anonymous->id);
        $this->assertSame('merged', $anonymous->status);
    }

    /**
     * Save for later removes the basket line; move-to-cart restores it.
     *
     * @return void
     */
    public function testSaveForLaterAndMoveBack(): void
    {
        $carts = new CartService();
        $token = 'anon-save-later';
        $cart = $carts->current(null, $token, true);
        $this->assertNotNull($cart);
        $cart = $carts->add($cart, 1, 2);
        $itemId = (int)$cart->cart_items[0]->id;

        $cart = $carts->saveForLater($cart, $itemId, null, null, $token);
        $this->assertSame([], $cart->cart_items);
        $saved = $carts->savedLines(null, null, $token);
        $this->assertCount(1, $saved);
        $this->assertSame(2, $saved[0]['qty']);

        $cart = $carts->moveToCart($cart, (int)$saved[0]['id'], null, null, $token);
        $this->assertCount(1, $cart->cart_items);
        $this->assertSame(2, (int)$cart->cart_items[0]->quantity);
        $this->assertSame([], $carts->savedLines(null, null, $token));
    }

    /**
     * Totals use live variant prices and site_settings, never posted amounts.
     *
     * @return void
     */
    public function testTotalsReadSiteSettingsAndGst(): void
    {
        $carts = new CartService();
        $settings = $carts->pricingSettings();
        $this->assertSame(15000, $settings['free_threshold_cents']);
        $this->assertSame(1495, $settings['flat_rate_cents']);
        $this->assertSame('0.1', $settings['gst_rate']);

        $token = 'pricing-token';
        $cart = $carts->current(null, $token, true);
        $this->assertNotNull($cart);

        $empty = $carts->totals($cart);
        $this->assertSame(0, $empty['subtotal_cents']);
        $this->assertSame(0, $empty['shipping_cents']);

        $cheap = $this->fetchTable('ProductVariants')->newEmptyEntity();
        $cheap->set('product_id', 1);
        $cheap->set('sku', 'EGL-CHEAP-01');
        $cheap->set('name', 'Shade only');
        $cheap->set('attributes', []);
        $cheap->set('price_cents', 2000);
        $cheap->set('cost_cents', 800);
        $cheap->set('tax_rate', '0.10000');
        $cheap->set('dimensions_mm', []);
        $cheap->set('is_default', 0);
        $cheap->set('is_active', 1);
        $cheap->set('track_inventory', 0);
        $cheap->set('allow_backorder', 0);
        $cheap->set('metadata', []);
        $this->fetchTable('ProductVariants')->saveOrFail($cheap);

        $cart = $carts->add($cart, (int)$cheap->id, 1);
        $below = $carts->totals($cart);
        $this->assertSame(2000, $below['subtotal_cents']);
        $this->assertSame(1495, $below['shipping_cents']);
        $this->assertSame(3495, $below['total_cents']);
        $this->assertSame(Money::gstPortionInclusive(3495, '0.1'), $below['gst_cents']);
        $this->assertSame(13000, $below['away_cents']);

        $cart = $carts->add($cart, 1, 1);
        $above = $carts->totals($cart);
        $this->assertSame(26900, $above['subtotal_cents']);
        $this->assertSame(0, $above['shipping_cents']);
        $this->assertSame(Money::gstPortionInclusive(26900, '0.1'), $above['gst_cents']);
    }

    /**
     * Posted unit prices are ignored; live variant price wins.
     *
     * @return void
     */
    public function testLivePriceIgnoresSnapshot(): void
    {
        $carts = new CartService();
        $request = new ServerRequest();
        $token = $carts->token($request);
        $cart = $carts->current(null, $token, true);
        $this->assertNotNull($cart);
        $cart = $carts->add($cart, 1, 1);
        $item = $cart->cart_items[0];
        $item->set('unit_price_snapshot_cents', 1);
        $this->fetchTable('CartItems')->saveOrFail($item);

        $totals = $carts->totals($this->fetchTable('Carts')->get($cart->id, contain: ['CartItems']));
        $this->assertSame(24900, $totals['subtotal_cents']);
    }
}
