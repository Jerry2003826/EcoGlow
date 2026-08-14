<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\Cart;
use App\Model\Entity\Invoice;
use App\Model\Entity\SalesOrder;
use App\Service\Cart\CartService;
use App\Service\Checkout\CheckoutService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentUncertainException;
use App\Service\Payments\RefundService;
use App\Service\Payments\StripePaymentGateway;
use App\Test\TestCase\Support\FakePaymentGateway;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Second-round audit guards: hold CAS, intent idempotency, attempt binding.
 */
class SecondRoundSecurityTest extends TestCase
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
        'app.Carts',
        'app.CartItems',
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.ReorderRules',
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
        'app.OutboundMessages',
        'app.OutboundMessageEvents',
        'app.ContactMessages',
        'app.PaymentReconciliationAlerts',
        'app.Addresses',
    ];

    /**
     * @var string
     */
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        Configure::write('Stripe.webhookSecret', self::WEBHOOK_SECRET);
        Configure::write('Stripe.secretKey', '');
        Configure::write('Stripe.publishableKey', 'pk_test_fake');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        putenv('SECURITY_REQUIRE_STAFF_MFA');
        parent::tearDown();
    }

    /**
     * A hold cleanup after capture must not cancel a paid order.
     *
     * @return void
     */
    public function testHoldCleanupAfterCaptureIsNoOp(): void
    {
        $before = $this->onHand();
        $order = $this->placeOrder('pi_hold_after');
        $this->postSigned($this->eventPayload(
            'evt_hold_after',
            'payment_intent.succeeded',
            'pi_hold_after',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();

        (new OrderService(new InventoryLedger()))->failUnpaid($order, 4, 'Checkout hold expired');
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->status);
        $this->assertSame($before - 1, $this->onHand());
        $this->assertSame(0, $this->reserved());
    }

    /**
     * Two Stripe event ids for one PaymentIntent credit the invoice once.
     *
     * @return void
     */
    public function testTwoEventIdsCreditInvoiceOnce(): void
    {
        $order = $this->placeOrder('pi_double_evt');
        $invoice = $this->issueInvoice($order);
        $this->postSigned($this->eventPayload(
            'evt_double_a',
            'payment_intent.succeeded',
            'pi_double_evt',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertSame(
            'captured',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_double_evt'])
                ->first()
                ?->status,
        );
        $this->postSigned($this->eventPayload(
            'evt_double_b',
            'payment_intent.succeeded',
            'pi_double_evt',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $this->assertSame((int)$order->grand_total_cents, (int)$invoice->amount_paid_cents);
        $this->assertSame(
            1,
            $this->fetchTable('PaymentAllocations')->find()
                ->where(['invoice_id' => $invoice->id, 'allocation_type' => 'capture'])
                ->count(),
        );
        $this->assertSame(
            1,
            $this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_double_evt', 'status' => 'captured'])
                ->count(),
        );
    }

    /**
     * A customer with an RBAC grant is treated as staff for MFA.
     *
     * @return void
     */
    public function testRbacGrantRequiresStaffMfa(): void
    {
        putenv('SECURITY_REQUIRE_STAFF_MFA=true');
        $roles = $this->fetchTable('UserRoles');
        $row = $roles->newEmptyEntity();
        $row->set('user_id', 4);
        $row->set('role_id', 3);
        $row->set('starts_at', DateTime::now('UTC')->subDays(1));
        $roles->saveOrFail($row);

        $this->session(['AuthV2' => 4, 'AuthVersion' => 1]);
        $this->get('/admin');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login/mfa-setup');
    }

    /**
     * An uncertain PaymentIntent error keeps the hold.
     *
     * @return void
     */
    public function testUncertainIntentKeepsHold(): void
    {
        $before = $this->onHand();
        $gateway = new FakePaymentGateway();
        $gateway->uncertainOnCreate = true;
        $checkout = $this->checkout($gateway, 'uncertain-token', 'pi_uncertain');
        $customer = $this->fetchTable('Customers')->get(2);
        try {
            $checkout->place($customer, 4, $this->cart(4, 'uncertain-token'), $this->address(), false, $this->attemptId('uncertain'));
            $this->fail('Uncertain create should throw.');
        } catch (PaymentUncertainException) {
            // expected
        }
        $order = $this->fetchTable('SalesOrders')->find()
            ->where(['customer_id' => 2])
            ->orderBy(['id' => 'DESC'])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame(SalesOrder::STATUS_DRAFT, $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($before, $this->onHand());
        $this->assertSame(1, $this->reserved());
    }

    /**
     * A pending refund must not restock or mark the payment refunded.
     *
     * @return void
     */
    public function testPendingRefundDoesNotComplete(): void
    {
        $order = $this->placeOrder('pi_refund_pending');
        $this->postSigned($this->eventPayload(
            'evt_refund_pay',
            'payment_intent.succeeded',
            'pi_refund_pending',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $consumed = $this->onHand();
        $gateway = new FakePaymentGateway();
        $gateway->refundStatus = 'pending';
        $gateway->nextRefundId = 're_pending_1';
        $payment = (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $this->assertSame('captured', (string)$payment->status);
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($consumed, $this->onHand());

        (new RefundService(new OrderService(new InventoryLedger()), new StripePaymentGateway()))
            ->applyWebhookStatus('re_pending_1', 'succeeded', 'pi_refund_pending');
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame($consumed + 1, $this->onHand());
    }

    /**
     * Another customer cannot resume a checkout attempt they do not own.
     *
     * @return void
     */
    public function testCheckoutAttemptIsBoundToIdentity(): void
    {
        $attempt = $this->attemptId('owned');
        $this->placeOrder('pi_owned', $attempt);
        $this->fetchTable('Carts')->updateAll(
            ['status' => 'abandoned'],
            ['user_id' => 5, 'status' => 'active'],
        );
        $carts = new CartService();
        $cart = $carts->current(5, 'b-token', true);
        $this->assertNotNull($cart);
        $carts->add($cart, 1, 1);
        $checkout = new CheckoutService(
            new OrderService(new InventoryLedger()),
            $carts,
            new FakePaymentGateway(),
        );
        $other = $this->fetchTable('Customers')->get(3);
        $this->expectException(InvalidArgumentException::class);
        $checkout->place($other, 5, $carts->current(5, 'b-token', false), $this->address(), false, $attempt);
    }

    /**
     * Session attempt id wins over a posted foreign UUID.
     *
     * @return void
     */
    public function testPostedForeignAttemptIdIsIgnoredWhenSessionBound(): void
    {
        Configure::write('Stripe.gateway', new FakePaymentGateway());
        Configure::write('Stripe.secretKey', 'sk_test_fake');
        $this->fillCart(4, 'sess-token');
        $mine = $this->attemptId('session');
        $this->session([
            'AuthV2' => 4,
            'AuthVersion' => 1,
            'Checkout.attempt_id' => $mine,
            CartService::SESSION_KEY => 'sess-token',
        ]);
        $this->post('/checkout', $this->address() + [
            'checkout_attempt_id' => $this->attemptId('foreign'),
        ]);
        $this->assertResponseOk();
        $order = $this->fetchTable('SalesOrders')->find()
            ->where(['customer_id' => 2])
            ->orderBy(['id' => 'DESC'])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame($mine, (string)$order->get('checkout_attempt_id'));
        $this->assertSame($mine, $this->getSession()->read('Checkout.attempt_id'));
    }

    /**
     * @param string $intentId Stripe id.
     * @param string|null $attemptId Checkout UUID.
     * @return \App\Model\Entity\SalesOrder
     */
    private function placeOrder(string $intentId, ?string $attemptId = null): SalesOrder
    {
        $token = 'wh-' . $intentId;
        $gateway = new FakePaymentGateway();
        $gateway->nextIntentId = $intentId;
        $checkout = $this->checkout($gateway, $token, $intentId);
        $customer = $this->fetchTable('Customers')->get(2);
        $result = $checkout->place(
            $customer,
            4,
            $this->cart(4, $token),
            $this->address(),
            false,
            $attemptId ?? $this->attemptId($intentId),
        );

        return $result['order'];
    }

    /**
     * @param \App\Test\TestCase\Support\FakePaymentGateway $gateway Gateway.
     * @param string $token Cart token.
     * @param string $intentId Intent id.
     * @return \App\Service\Checkout\CheckoutService
     */
    private function checkout(FakePaymentGateway $gateway, string $token, string $intentId): CheckoutService
    {
        $this->fetchTable('Carts')->updateAll(
            ['status' => 'abandoned'],
            ['user_id' => 4, 'status' => 'active'],
        );
        $gateway->nextIntentId = $intentId;
        $carts = new CartService();
        $cart = $carts->current(4, $token, true);
        $this->assertNotNull($cart);
        $carts->add($cart, 1, 1);

        return new CheckoutService(new OrderService(new InventoryLedger()), $carts, $gateway);
    }

    /**
     * @param int $userId User.
     * @param string $token Token.
     * @return \App\Model\Entity\Cart
     */
    private function cart(int $userId, string $token): Cart
    {
        $cart = (new CartService())->current($userId, $token, false);
        $this->assertNotNull($cart);

        return $cart;
    }

    /**
     * @param int $userId User.
     * @param string $token Token.
     * @return void
     */
    private function fillCart(int $userId, string $token): void
    {
        $this->fetchTable('Carts')->updateAll(
            ['status' => 'abandoned'],
            ['user_id' => $userId, 'status' => 'active'],
        );
        $carts = new CartService();
        $cart = $carts->current($userId, $token, true);
        $this->assertNotNull($cart);
        $carts->add($cart, 1, 1);
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return \App\Model\Entity\Invoice
     */
    private function issueInvoice(SalesOrder $order): Invoice
    {
        $invoices = $this->fetchTable('Invoices');
        $existing = $invoices->find()
            ->where(['sales_order_id' => $order->id, 'status !=' => Invoice::STATUS_VOID])
            ->first();
        if ($existing !== null) {
            $this->fetchTable('PaymentAllocations')->deleteAll(['invoice_id' => $existing->id]);
            $existing->amount_paid_cents = 0;
            $existing->status = Invoice::STATUS_ISSUED;
            $existing->paid_at = null;
            $invoices->saveOrFail($existing);

            return $existing;
        }
        $invoice = $invoices->newEmptyEntity();
        $invoice->invoice_number = 'INV-TEST-' . $order->id;
        $invoice->invoice_type = 'invoice';
        $invoice->sales_order_id = $order->id;
        $invoice->customer_id = $order->customer_id;
        $invoice->status = Invoice::STATUS_ISSUED;
        $invoice->currency = 'AUD';
        $invoice->subtotal_cents = (int)$order->subtotal_cents;
        $invoice->discount_cents = 0;
        $invoice->shipping_cents = 0;
        $invoice->tax_cents = (int)$order->tax_cents;
        $invoice->grand_total_cents = (int)$order->grand_total_cents;
        $invoice->amount_paid_cents = 0;
        $invoice->credit_applied_cents = 0;
        $invoice->business_snapshot = [];
        $invoice->customer_snapshot = [];
        $invoice->billing_address_snapshot = [];
        $invoice->metadata = [];
        $invoices->saveOrFail($invoice);

        return $invoice;
    }

    /**
     * @return array<string, string>
     */
    private function address(): array
    {
        return [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'phone' => '0400000004',
        ];
    }

    /**
     * @param string $seed Unique seed.
     * @return string
     */
    private function attemptId(string $seed): string
    {
        return '11111111-1111-4111-8111-' . substr(hash('sha256', $seed), 0, 12);
    }

    /**
     * @return int
     */
    private function onHand(): int
    {
        return (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_on_hand;
    }

    /**
     * @return int
     */
    private function reserved(): int
    {
        return (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_reserved;
    }

    /**
     * @param string $payload Raw JSON.
     * @return void
     */
    private function postSigned(string $payload): void
    {
        $timestamp = (string)time();
        $hmac = hash_hmac('sha256', $timestamp . '.' . $payload, self::WEBHOOK_SECRET);
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => 't=' . $timestamp . ',v1=' . $hmac,
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->post('/webhooks/stripe', $payload);
    }

    /**
     * @param string $eventId Event id.
     * @param string $type Type.
     * @param string $intentId Intent id.
     * @param int $amountCents Amount.
     * @param array<string, string> $metadata Metadata.
     * @return string
     */
    private function eventPayload(
        string $eventId,
        string $type,
        string $intentId,
        int $amountCents,
        array $metadata,
    ): string {
        $event = [
            'id' => $eventId,
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => ['id' => null, 'idempotency_key' => null],
            'type' => $type,
            'data' => [
                'object' => [
                    'id' => $intentId,
                    'object' => 'payment_intent',
                    'amount' => $amountCents,
                    'amount_received' => $type === 'payment_intent.succeeded' ? $amountCents : 0,
                    'currency' => 'aud',
                    'status' => $type === 'payment_intent.succeeded' ? 'succeeded' : 'requires_payment_method',
                    'metadata' => $metadata,
                ],
            ],
        ];

        return json_encode($event, JSON_THROW_ON_ERROR);
    }
}
