<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Service\Cart\CartService;
use App\Test\TestCase\Support\FakePaymentGateway;
use Authentication\Identity;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Checkout: login gate, server-side totals, Stripe stub.
 */
class CheckoutControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.RolePermissions',
        'app.UserRoles',
        'app.UserPermissionOverrides',
        'app.Customers',
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.ReorderRules',
        'app.Carts',
        'app.CartItems',
        'app.SalesOrders',
        'app.SalesOrderItems',
        'app.OrderStatusHistory',
        'app.OrderNotes',
        'app.StockReservations',
        'app.InventoryMovements',
        'app.SiteSettings',
        'app.FeatureFlags',
        'app.Payments',
        'app.PaymentRefunds',
        'app.IdempotencyRecords',
        'app.OrderAddresses',
        'app.Addresses',
        'app.ContactMessages',
    ];

    /**
     * @var \App\Test\TestCase\Support\FakePaymentGateway
     */
    private FakePaymentGateway $gateway;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->gateway = new FakePaymentGateway();
        Configure::write('Stripe.gateway', $this->gateway);
        Configure::write('Stripe.publishableKey', 'pk_test_fake');
        Configure::write('Stripe.secretKey', 'sk_test_fake');
        Configure::write('Stripe.webhookSecret', 'whsec_test');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        Configure::delete('Stripe.gateway');
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testIndexRedirectsGuestsToCustomerLogin(): void
    {
        $this->get('/checkout');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/account/login');
        $this->assertRedirectContains('redirect=');
    }

    /**
     * @return void
     */
    public function testIndexOkWhenBasketEmpty(): void
    {
        $this->loginCustomer(4, 'empty-checkout-token');
        $this->get('/checkout');
        $this->assertResponseOk();
        $this->assertResponseContains('Your basket is empty');
    }

    /**
     * Posted totals are ignored; the PaymentIntent uses live catalogue prices.
     *
     * @return void
     */
    public function testPostedPricesAreIgnored(): void
    {
        $this->fillCart(4, 'tamper-token', 1, 1);
        $this->loginCustomer(4, 'tamper-token');

        $this->post('/checkout', [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'phone' => '0400000004',
            'subtotal_cents' => 1,
            'shipping_cents' => 1,
            'total_cents' => 1,
        ]);

        $this->assertResponseOk();
        $this->assertSame(24900, $this->gateway->lastAmountCents);

        $order = $this->fetchTable('SalesOrders')->find()
            ->where(['customer_id' => 2, 'source_channel' => 'web'])
            ->orderBy(['id' => 'DESC'])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame(24900, (int)$order->grand_total_cents);
        $this->assertSame(0, (int)$order->shipping_cents);
        $this->assertSame('pending', $order->payment_status);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(5, (int)$balance->quantity_on_hand);
        $this->assertSame(1, (int)$balance->quantity_reserved);
    }

    /**
     * @return void
     */
    public function testPaymentsFlagOffDoesNotCharge(): void
    {
        $this->fetchTable('FeatureFlags')->updateAll(
            ['enabled' => 0],
            ['flag_key' => 'commerce.online_payments'],
        );
        $this->fillCart(4, 'flag-off-token', 1, 1);
        $this->loginCustomer(4, 'flag-off-token');

        $this->get('/checkout');
        $this->assertResponseOk();
        $this->assertResponseContains('Online payment is not open yet');
        $this->assertResponseContains('checkout-notice');
        $this->assertResponseContains('id="checkout-pay-heading"');
        $this->assertResponseNotContains('Continue to payment');

        $this->post('/checkout', [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
        ]);
        $this->assertResponseOk();
        $this->assertSame(0, $this->gateway->lastAmountCents);
        $this->assertSame(
            0,
            $this->fetchTable('SalesOrders')->find()->where(['customer_id' => 2])->count(),
        );
    }

    /**
     * @return void
     */
    public function testConfirmationRejectsOtherCustomersOrder(): void
    {
        $orders = $this->fetchTable('SalesOrders');
        $order = $orders->newEmptyEntity();
        $order->set('order_number', 'SO-B-CHECKOUT');
        $order->set('customer_id', 3);
        $order->set('status', 'draft');
        $order->set('source_channel', 'web');
        $order->set('subtotal_cents', 18900);
        $order->set('grand_total_cents', 18900);
        $order->set('metadata', []);
        $orders->saveOrFail($order);

        $this->loginCustomer(4);
        $this->get('/checkout/confirmation/' . $order->id);
        $this->assertResponseCode(404);
    }

    /**
     * Missing Stripe keys are an expected local state: show a notice, do not charge.
     *
     * @return void
     */
    public function testMissingStripeKeysShowsNoticeAndDoesNotCharge(): void
    {
        Configure::write('Stripe.publishableKey', '');
        Configure::write('Stripe.secretKey', '');
        $this->fillCart(4, 'no-stripe-token', 1, 1);
        $this->loginCustomer(4, 'no-stripe-token');

        $this->get('/checkout');
        $this->assertResponseOk();
        $this->assertResponseContains('Marlow Floor Lamp');
        $this->assertResponseContains('Card payment is not configured on this server yet');
        $this->assertResponseContains('checkout-notice');
        $this->assertResponseContains('id="checkout-pay-heading"');
        $this->assertResponseNotContains('Continue to payment');

        $this->post('/checkout', [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
        ]);
        $this->assertResponseOk();
        $this->assertSame(0, $this->gateway->lastAmountCents);
        $this->assertSame(
            0,
            $this->fetchTable('SalesOrders')->find()->where(['customer_id' => 2])->count(),
        );
        $this->assertFlashMessage(
            'Card payment is not configured on this server yet. ' .
            'Your basket is held; please contact us to complete the order.',
        );
    }

    /**
     * @param int $userId UsersFixture id.
     * @param string|null $cartToken Session cart token.
     * @return void
     */
    private function loginCustomer(int $userId, ?string $cartToken = null): void
    {
        $session = [
            'Auth' => new Identity($this->fetchTable('Users')->get($userId)),
        ];
        if ($cartToken !== null) {
            $session[CartService::SESSION_KEY] = $cartToken;
        }
        $this->session($session);
    }

    /**
     * @param int $userId User id.
     * @param string $token Cart token.
     * @param int $variantId Variant.
     * @param int $quantity Qty.
     * @return void
     */
    private function fillCart(int $userId, string $token, int $variantId, int $quantity): void
    {
        $this->fetchTable('Carts')->updateAll(
            ['status' => 'abandoned'],
            ['user_id' => $userId, 'status' => 'active'],
        );
        $carts = new CartService();
        $cart = $carts->current($userId, $token, true);
        $this->assertNotNull($cart);
        $carts->add($cart, $variantId, $quantity);
    }
}
