<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\SalesOrder;
use App\Service\Cart\CartService;
use App\Service\Checkout\CheckoutService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Test\TestCase\Support\FakePaymentGateway;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stripe webhook signature, idempotency, and inventory side effects.
 */
class WebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Customers',
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
        Configure::write('Stripe.webhookSecret', self::WEBHOOK_SECRET);
        Configure::write('Stripe.secretKey', '');
        Configure::write('Stripe.publishableKey', '');
    }

    /**
     * CSRF is skipped for this path only; a bad HMAC is still refused.
     *
     * @return void
     */
    public function testInvalidSignatureIsRejected(): void
    {
        $this->configRequest([
            'headers' => ['Stripe-Signature' => 't=1,v1=not-a-real-signature'],
        ]);
        $this->post('/webhooks/stripe', '{"id":"evt_x"}');
        $this->assertResponseCode(400);
        $this->assertResponseNotContains('CSRF');
    }

    /**
     * @return void
     */
    public function testUnknownEventTypeReturnsOk(): void
    {
        $payload = $this->eventPayload('evt_ignore_1', 'customer.created', 'pi_none', 0, []);
        $this->postSigned($payload);
        $this->assertResponseOk();
        $this->assertResponseContains('received');
    }

    /**
     * A captured PaymentIntent consumes the reservation (sale movement).
     *
     * @return void
     */
    public function testPaymentSucceededConsumesReservation(): void
    {
        $before = (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_on_hand;
        $order = $this->placeOrder('pi_ok_1');
        $payload = $this->eventPayload(
            'evt_ok_1',
            'payment_intent.succeeded',
            'pi_ok_1',
            (int)$order->grand_total_cents,
            [
                'order_id' => (string)$order->id,
                'order_number' => (string)$order->order_number,
            ],
        );
        $this->postSigned($payload);
        $this->assertResponseOk();

        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame($before - 1, (int)$balance->quantity_on_hand);
        $this->assertSame(0, (int)$balance->quantity_reserved);

        $this->postSigned($payload);
        $this->assertResponseOk();
        $this->assertResponseContains('duplicate');
        $this->assertSame(
            1,
            $this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_ok_1', 'status' => 'captured'])
                ->count(),
        );
        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame($before - 1, (int)$balance->quantity_on_hand);
        $this->assertSame(0, (int)$balance->quantity_reserved);
    }

    /**
     * A failed PaymentIntent releases the reservation.
     *
     * @return void
     */
    public function testPaymentFailedReleasesReservation(): void
    {
        $before = (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_on_hand;
        $order = $this->placeOrder('pi_fail_1');
        $payload = $this->eventPayload(
            'evt_fail_1',
            'payment_intent.payment_failed',
            'pi_fail_1',
            (int)$order->grand_total_cents,
            [
                'order_id' => (string)$order->id,
                'order_number' => (string)$order->order_number,
            ],
        );
        $this->postSigned($payload);
        $this->assertResponseOk();

        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame($before, (int)$balance->quantity_on_hand);
        $this->assertSame(0, (int)$balance->quantity_reserved);
    }

    /**
     * @param string $intentId Stripe PaymentIntent id.
     * @return \App\Model\Entity\SalesOrder
     */
    private function placeOrder(string $intentId): SalesOrder
    {
        $this->fetchTable('Carts')->updateAll(
            ['status' => 'abandoned'],
            ['user_id' => 4, 'status' => 'active'],
        );
        $gateway = new FakePaymentGateway();
        $gateway->nextIntentId = $intentId;
        $carts = new CartService();
        $token = 'wh-' . $intentId;
        $cart = $carts->current(4, $token, true);
        $this->assertNotNull($cart);
        $carts->add($cart, 1, 1);
        $checkout = new CheckoutService(
            new OrderService(new InventoryLedger()),
            $carts,
            $gateway,
        );
        $customer = $this->fetchTable('Customers')->get(2);
        $result = $checkout->place($customer, 4, $carts->current(4, $token, false), [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'phone' => '0400000004',
        ]);

        return $result['order'];
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
     * @param string $eventId Stripe event id.
     * @param string $type Event type.
     * @param string $intentId PaymentIntent id.
     * @param int $amountCents Amount.
     * @param array<string, string> $metadata Intent metadata.
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
                    'currency' => 'aud',
                    'status' => $type === 'payment_intent.succeeded' ? 'succeeded' : 'failed',
                    'metadata' => $metadata,
                ],
            ],
        ];

        return json_encode($event, JSON_THROW_ON_ERROR);
    }
}
