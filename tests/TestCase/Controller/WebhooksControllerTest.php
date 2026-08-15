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
        'app.PaymentEffects',
        'app.CreditNoteItems',
        'app.CreditNotes',
        'app.PaymentAllocations',
        'app.InvoiceItems',
        'app.InvoiceStatusHistory',
        'app.Invoices',
        'app.IdempotencyRecords',
        'app.OrderAddresses',
        'app.OutboundMessages',
        'app.OutboundMessageEvents',
        'app.ContactMessages',
        'app.PaymentReconciliationAlerts',
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
        Configure::write('Stripe.gateway', new FakePaymentGateway());
        $connection = $this->fetchTable('SalesOrders')->getConnection();
        $connection->execute('SET FOREIGN_KEY_CHECKS=0');
        foreach (
            [
                'credit_note_items',
                'credit_notes',
                'payment_allocations',
                'invoice_items',
                'invoice_status_history',
                'invoices',
                'payment_effects',
            ] as $table
        ) {
            $connection->execute('DELETE FROM `' . $table . '`');
        }
        $connection->execute('SET FOREIGN_KEY_CHECKS=1');
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
        $this->assertResponseContains('invalid_webhook');
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
     * A failed PaymentIntent keeps the unpaid hold so the customer can retry.
     *
     * @return void
     */
    public function testPaymentFailedKeepsReservation(): void
    {
        $before = (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_on_hand;
        $order = $this->placeOrder('pi_fail_1');
        $reserved = (int)$this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ])->quantity_reserved;
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
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(SalesOrder::STATUS_DRAFT, $order->status);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame($before, (int)$balance->quantity_on_hand);
        $this->assertSame($reserved, (int)$balance->quantity_reserved);
        $this->assertSame(
            'failed',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_fail_1'])
                ->first()
                ->status,
        );
    }

    /**
     * A later success after payment_failed still confirms the same order.
     *
     * @return void
     */
    public function testFailedThenSucceededConfirmsOrder(): void
    {
        $order = $this->placeOrder('pi_retry_1');
        $this->postSigned($this->eventPayload(
            'evt_retry_fail',
            'payment_intent.payment_failed',
            'pi_retry_1',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->postSigned($this->eventPayload(
            'evt_retry_ok',
            'payment_intent.succeeded',
            'pi_retry_1',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
    }

    /**
     * Success after cancel must auto-refund instead of confirming the order.
     *
     * @return void
     */
    public function testCanceledThenSucceededCreatesAlert(): void
    {
        $order = $this->placeOrder('pi_cancel_1');
        $this->postSigned($this->eventPayload(
            'evt_cancel_1',
            'payment_intent.canceled',
            'pi_cancel_1',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('cancelled', $order->status);

        $this->postSigned($this->eventPayload(
            'evt_cancel_ok',
            'payment_intent.succeeded',
            'pi_cancel_1',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertResponseContains('refunded');
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(
            'refunded',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_cancel_1'])
                ->first()
                ?->status,
        );
        $this->assertSame(
            1,
            $this->fetchTable('PaymentReconciliationAlerts')->find()->count(),
        );
    }

    /**
     * Amount or currency mismatch must not confirm the order.
     *
     * @return void
     */
    public function testAmountMismatchDoesNotConfirm(): void
    {
        $order = $this->placeOrder('pi_amt_1');
        $this->postSigned($this->eventPayload(
            'evt_amt_1',
            'payment_intent.succeeded',
            'pi_amt_1',
            (int)$order->grand_total_cents + 100,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertResponseContains('conflict');
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('pending', $order->payment_status);
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
        $attemptId = '11111111-1111-4111-8111-' . substr(hash('sha256', $intentId), 0, 12);
        $result = $checkout->place($customer, 4, $carts->current(4, $token, false), [
            'recipient_name' => 'Casey Aitken',
            'line1' => '10 Flinders Lane',
            'suburb' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'phone' => '0400000004',
        ], false, $attemptId);

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
                    'amount_received' => $type === 'payment_intent.succeeded' ? $amountCents : 0,
                    'currency' => 'aud',
                    'status' => match ($type) {
                        'payment_intent.succeeded' => 'succeeded',
                        'payment_intent.canceled' => 'canceled',
                        default => 'requires_payment_method',
                    },
                    'metadata' => $metadata,
                ],
            ],
        ];

        return json_encode($event, JSON_THROW_ON_ERROR);
    }
}
