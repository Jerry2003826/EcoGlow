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
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;

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
        $this->assertSame(
            1,
            $this->fetchTable('PaymentEffects')->find()
                ->where([
                    'provider' => 'stripe',
                    'provider_payment_id' => 'pi_double_evt',
                    'effect_type' => 'capture',
                ])
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
     * A Dashboard refund without a local pending row is alerted, not auto-applied.
     *
     * @return void
     */
    public function testExternalRefundCreatesReconciliationAlert(): void
    {
        $order = $this->placeOrder('pi_dash_refund');
        $invoice = $this->issueInvoice($order);
        $this->postSigned($this->eventPayload(
            'evt_dash_pay',
            'payment_intent.succeeded',
            'pi_dash_refund',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $consumed = $this->onHand();
        $this->postSigned($this->refundEventPayload(
            'evt_dash_refund',
            'refund.updated',
            're_dash_1',
            'pi_dash_refund',
            'succeeded',
            10,
        ));
        $this->assertResponseOk();
        $this->assertSame(
            0,
            $this->fetchTable('PaymentRefunds')->find()
                ->where(['provider_refund_id' => 're_dash_1'])
                ->count(),
        );
        $this->assertGreaterThan(
            0,
            $this->fetchTable('PaymentReconciliationAlerts')->find()
                ->where(['event_id' => 'refund:re_dash_1'])
                ->count(),
        );
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $this->assertSame((int)$order->grand_total_cents, (int)$invoice->amount_paid_cents);
        $this->assertSame(Invoice::STATUS_PAID, (string)$invoice->status);
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($consumed, $this->onHand());
    }

    /**
     * A Dashboard refund must not bind to an unrelated local pending refund.
     *
     * @return void
     */
    public function testDashboardRefundDoesNotHijackPendingStaffRefund(): void
    {
        $order = $this->placeOrder('pi_hijack_refund');
        $this->postSigned($this->eventPayload(
            'evt_hijack_pay',
            'payment_intent.succeeded',
            'pi_hijack_refund',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $gateway = new FakePaymentGateway();
        $gateway->refundStatus = 'pending';
        $gateway->nextRefundId = 're_staff_pending';
        (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $this->assertSame(
            ['local_refund_id', 'local_payment_id', 'refund_binding_token'],
            array_keys($gateway->lastRefundMetadata),
        );
        $payment = $this->fetchTable('Payments')->find()
            ->where(['provider_payment_id' => 'pi_hijack_refund'])
            ->first();
        $this->assertNotNull($payment);
        $staffId = (int)$this->fetchTable('PaymentRefunds')->find()
            ->where(['payment_id' => $payment->id])
            ->first()
            ?->id;
        $this->assertSame(
            RefundService::bindingToken(
                $staffId,
                (int)$payment->id,
                (int)$payment->amount_cents,
                strtolower((string)($payment->currency ?: 'aud')),
            ),
            $gateway->lastRefundMetadata['refund_binding_token'],
        );

        $this->postSigned($this->refundEventPayload(
            'evt_hijack_dash',
            'refund.updated',
            're_dashboard_10',
            'pi_hijack_refund',
            'succeeded',
            10,
        ));
        $this->assertResponseOk();
        $staff = $this->fetchTable('PaymentRefunds')->find()
            ->where(['provider_refund_id' => 're_staff_pending'])
            ->first();
        $this->assertNotNull($staff);
        $this->assertSame('pending', (string)$staff->get('status'));
        $this->assertSame(
            0,
            $this->fetchTable('PaymentRefunds')->find()
                ->where(['provider_refund_id' => 're_dashboard_10'])
                ->count(),
        );
        $this->assertGreaterThan(
            0,
            $this->fetchTable('PaymentReconciliationAlerts')->find()
                ->where(['event_id' => 'refund:re_dashboard_10'])
                ->count(),
        );
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
    }

    /**
     * Copied local IDs without a valid HMAC cannot bind a pending staff refund.
     *
     * @return void
     */
    public function testForgedRefundBindingTokenDoesNotHijackPendingRefund(): void
    {
        $order = $this->placeOrder('pi_forged_token');
        $this->postSigned($this->eventPayload(
            'evt_forged_pay',
            'payment_intent.succeeded',
            'pi_forged_token',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $gateway = new FakePaymentGateway();
        $gateway->refundStatus = 'pending';
        $gateway->nextRefundId = 're_staff_forged';
        (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $payment = $this->fetchTable('Payments')->find()
            ->where(['provider_payment_id' => 'pi_forged_token'])
            ->first();
        $this->assertNotNull($payment);
        $staff = $this->fetchTable('PaymentRefunds')->find()
            ->where(['payment_id' => $payment->id])
            ->first();
        $this->assertNotNull($staff);

        $this->postSigned($this->refundEventPayload(
            'evt_forged_token',
            'refund.updated',
            're_forged_dash',
            'pi_forged_token',
            'succeeded',
            (int)$staff->get('amount_cents'),
            [
                'local_refund_id' => (string)$staff->id,
                'local_payment_id' => (string)$payment->id,
                'refund_binding_token' => str_repeat('a', 64),
            ],
        ));
        $this->assertResponseOk();
        $staff = $this->fetchTable('PaymentRefunds')->get($staff->id);
        $this->assertSame('pending', (string)$staff->get('status'));
        $this->assertSame('re_staff_forged', (string)$staff->get('provider_refund_id'));
        $this->assertGreaterThan(
            0,
            $this->fetchTable('PaymentReconciliationAlerts')->find()
                ->where(['event_id' => 'refund:re_forged_dash'])
                ->count(),
        );
    }

    /**
     * A Stripe-failed refund can be retried as a new attempt with a new key.
     *
     * @return void
     */
    public function testFailedRefundCanBeRetriedWithNewIdempotencyKey(): void
    {
        $order = $this->placeOrder('pi_refund_failed_retry');
        $this->postSigned($this->eventPayload(
            'evt_failed_retry_pay',
            'payment_intent.succeeded',
            'pi_refund_failed_retry',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $first = new FakePaymentGateway();
        $first->refundStatus = 'pending';
        $first->nextRefundId = 're_failed_attempt';
        (new RefundService(new OrderService(new InventoryLedger()), $first))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $firstKey = $first->lastRefundIdempotencyKey;
        $this->postSigned($this->refundEventPayload(
            'evt_failed_retry_fail',
            'refund.failed',
            're_failed_attempt',
            'pi_refund_failed_retry',
            'failed',
            (int)$order->grand_total_cents,
        ));
        $this->assertResponseOk();

        $second = new FakePaymentGateway();
        $second->nextRefundId = 're_failed_retry_ok';
        $payment = (new RefundService(new OrderService(new InventoryLedger()), $second))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $this->assertSame('refunded', (string)$payment->status);
        $this->assertNotSame($firstKey, $second->lastRefundIdempotencyKey);
        $this->assertSame(
            2,
            $this->fetchTable('PaymentRefunds')->find()
                ->where(['payment_id' => $payment->id])
                ->count(),
        );
        $this->assertSame(
            'succeeded',
            (string)$this->fetchTable('PaymentRefunds')->find()
                ->where(['provider_refund_id' => 're_failed_retry_ok'])
                ->first()
                ?->get('status'),
        );
    }

    /**
     * A succeeded refund must write refund-{id} even when ORM schema is stale.
     *
     * @return void
     */
    public function testSucceededRefundIgnoresStaleAllocationSchema(): void
    {
        $order = $this->placeOrder('pi_stale_schema');
        $this->issueInvoice($order);
        $this->postSigned($this->eventPayload(
            'evt_stale_schema_pay',
            'payment_intent.succeeded',
            'pi_stale_schema',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();

        $table = $this->fetchTable('PaymentAllocations');
        $schema = $table->getSchema();
        if ($schema->hasColumn('effect_key')) {
            $schema->removeColumn('effect_key');
        }
        if ($schema->hasColumn('payment_refund_id')) {
            $schema->removeColumn('payment_refund_id');
        }
        $table->setSchema($schema);
        try {
            $payment = (new RefundService(new OrderService(new InventoryLedger()), new FakePaymentGateway()))
                ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
            $this->assertSame('refunded', (string)$payment->status);
            $refund = $this->fetchTable('PaymentRefunds')->find()
                ->where(['payment_id' => $payment->id, 'status' => 'succeeded'])
                ->first();
            $this->assertNotNull($refund);
            $row = ConnectionManager::get('test')->execute(
                'SELECT effect_key, payment_refund_id
                   FROM payment_allocations
                  WHERE payment_id = ? AND allocation_type = ?',
                [$payment->id, 'refund'],
            )->fetch('assoc');
            $this->assertIsArray($row);
            $this->assertSame('refund-' . $refund->id, (string)$row['effect_key']);
            $this->assertSame((string)$refund->id, (string)$row['payment_refund_id']);
        } finally {
            $this->getTableLocator()->remove('PaymentAllocations');
        }
    }

    /**
     * Two overlapping partial refunds must not lose invoice or status updates.
     *
     * @return void
     */
    public function testConcurrentPartialRefundsDoNotLoseInvoiceUpdates(): void
    {
        $config = ConnectionManager::get('test')->config();
        $driver = (string)($config['driver'] ?? '');
        if (!str_contains($driver, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL row locks.');
        }
        $order = $this->placeOrder('pi_concurrent_refund');
        $invoice = $this->issueInvoice($order);
        $this->postSigned($this->eventPayload(
            'evt_concurrent_pay',
            'payment_intent.succeeded',
            'pi_concurrent_refund',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $payment = $this->fetchTable('Payments')->find()
            ->where(['provider_payment_id' => 'pi_concurrent_refund'])
            ->first();
        $this->assertNotNull($payment);
        $total = (int)$payment->amount_cents;
        $firstAmount = intdiv($total, 2);
        $secondAmount = $total - $firstAmount;
        $refunds = $this->fetchTable('PaymentRefunds');
        $first = $refunds->newEmptyEntity();
        $first->set('payment_id', $payment->id);
        $first->set('provider_refund_id', 're_conc_a');
        $first->set('idempotency_key', 'refund-conc-a');
        $first->set('status', 'pending');
        $first->set('amount_cents', $firstAmount);
        $refunds->saveOrFail($first);
        $second = $refunds->newEmptyEntity();
        $second->set('payment_id', $payment->id);
        $second->set('provider_refund_id', 're_conc_b');
        $second->set('idempotency_key', 'refund-conc-b');
        $second->set('status', 'pending');
        $second->set('amount_cents', $secondAmount);
        $refunds->saveOrFail($second);

        $connection = ConnectionManager::get('test');
        if ($connection->inTransaction()) {
            $connection->commit();
        }

        $firstChild = $this->startRefundChild('re_conc_a', 'succeeded', 'pi_concurrent_refund', $firstAmount, (string)$first->id);
        $secondChild = $this->startRefundChild('re_conc_b', 'succeeded', 'pi_concurrent_refund', $secondAmount, (string)$second->id);
        $firstResult = $this->finishRefundChild($firstChild);
        $secondResult = $this->finishRefundChild($secondChild);
        $this->assertSame(0, $firstResult['code'], $firstResult['output']);
        $this->assertSame(0, $secondResult['code'], $secondResult['output']);

        $pdo = $this->secondMysqlConnection($config);
        $paymentStatus = $pdo->query(
            'SELECT status FROM payments WHERE id = ' . (int)$payment->id,
        )->fetchColumn();
        $orderStatus = $pdo->query(
            'SELECT payment_status FROM sales_orders WHERE id = ' . (int)$order->id,
        )->fetchColumn();
        $invoicePaid = $pdo->query(
            'SELECT amount_paid_cents FROM invoices WHERE id = ' . (int)$invoice->id,
        )->fetchColumn();
        $allocationCount = $pdo->query(
            'SELECT COUNT(*) FROM payment_allocations
              WHERE payment_id = ' . (int)$payment->id . "
                AND allocation_type = 'refund'",
        )->fetchColumn();
        $this->assertSame('refunded', (string)$paymentStatus);
        $this->assertSame('refunded', (string)$orderStatus);
        $this->assertSame(0, (int)$invoicePaid);
        $this->assertSame(2, (int)$allocationCount);
    }

    /**
     * Late failed/canceled events cannot move a refunded payment backwards.
     *
     * @return void
     */
    public function testLateFailureDoesNotUndoRefundedPayment(): void
    {
        $order = $this->placeOrder('pi_late_fail');
        $this->postSigned($this->eventPayload(
            'evt_late_pay',
            'payment_intent.succeeded',
            'pi_late_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $gateway = new FakePaymentGateway();
        $gateway->nextRefundId = 're_late_1';
        (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $this->assertSame(
            'refunded',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_late_fail'])
                ->first()
                ?->status,
        );

        $this->postSigned($this->eventPayload(
            'evt_late_fail',
            'payment_intent.payment_failed',
            'pi_late_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->postSigned($this->eventPayload(
            'evt_late_cancel',
            'payment_intent.canceled',
            'pi_late_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertSame(
            'refunded',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_late_fail'])
                ->first()
                ?->status,
        );
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertNotSame(SalesOrder::STATUS_CANCELLED, $order->status);
    }

    /**
     * A failed attempt can still be captured when the later succeeded event arrives.
     *
     * @return void
     */
    public function testFailedThenSucceededCapturesPayment(): void
    {
        $order = $this->placeOrder('pi_fail_then_ok');
        $this->postSigned($this->eventPayload(
            'evt_first_fail',
            'payment_intent.payment_failed',
            'pi_fail_then_ok',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertSame(
            'failed',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_fail_then_ok'])
                ->first()
                ?->status,
        );
        $this->postSigned($this->eventPayload(
            'evt_later_ok',
            'payment_intent.succeeded',
            'pi_fail_then_ok',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertSame(
            'captured',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_fail_then_ok'])
                ->first()
                ?->status,
        );
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
    }

    /**
     * A late failure after capture must not rewrite the payment.
     *
     * @return void
     */
    public function testSucceededThenFailedKeepsCapture(): void
    {
        $order = $this->placeOrder('pi_ok_then_fail');
        $this->postSigned($this->eventPayload(
            'evt_ok_first',
            'payment_intent.succeeded',
            'pi_ok_then_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->postSigned($this->eventPayload(
            'evt_fail_later',
            'payment_intent.payment_failed',
            'pi_ok_then_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->assertSame(
            'captured',
            (string)$this->fetchTable('Payments')->find()
                ->where(['provider_payment_id' => 'pi_ok_then_fail'])
                ->first()
                ?->status,
        );
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
    }

    /**
     * An unmatched Stripe refund must not be marked complete.
     *
     * @return void
     */
    public function testUnknownRefundIsNotMarkedComplete(): void
    {
        $this->postSigned($this->refundEventPayload(
            'evt_unknown_refund',
            'refund.updated',
            're_unknown',
            'pi_does_not_exist',
            'succeeded',
        ));
        $this->assertResponseCode(500);
        $record = $this->fetchTable('IdempotencyRecords')->find()
            ->where([
                'scope' => 'stripe_webhook',
                'idempotency_key' => 'evt_unknown_refund',
            ])
            ->first();
        $this->assertNotNull($record);
        $this->assertNull($record->get('completed_at'));
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
     * A completed attempt must not be reused for a second cart.
     *
     * @return void
     */
    public function testSecondPurchaseRequiresNewAttempt(): void
    {
        $attempt = $this->attemptId('second-buy');
        $order = $this->placeOrder('pi_second_buy', $attempt);
        $this->postSigned($this->eventPayload(
            'evt_second_buy',
            'payment_intent.succeeded',
            'pi_second_buy',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $this->fillCart(4, 'second-cart');
        $checkout = new CheckoutService(
            new OrderService(new InventoryLedger()),
            new CartService(),
            new FakePaymentGateway(),
        );
        $customer = $this->fetchTable('Customers')->get(2);
        $this->expectException(InvalidArgumentException::class);
        $checkout->place(
            $customer,
            4,
            $this->cart(4, 'second-cart'),
            $this->address(),
            false,
            $attempt,
        );
    }

    /**
     * The confirmation page drops the checkout attempt so the next buy is new.
     *
     * @return void
     */
    public function testConfirmationClearsCheckoutAttempt(): void
    {
        $attempt = $this->attemptId('confirm-clear');
        $order = $this->placeOrder('pi_confirm_clear', $attempt);
        $this->session([
            'AuthV2' => 4,
            'AuthVersion' => 1,
            'Checkout.attempt_id' => $attempt,
        ]);
        $this->get('/checkout/confirmation/' . $order->id);
        $this->assertResponseOk();
        $this->assertNull($this->getSession()->read('Checkout.attempt_id'));
    }

    /**
     * After A logs out, B cannot resume A's checkout UUID.
     *
     * @return void
     */
    public function testSharedBrowserLogoutDropsAttempt(): void
    {
        Configure::write('Stripe.gateway', new FakePaymentGateway());
        Configure::write('Stripe.secretKey', 'sk_test_fake');
        $this->fillCart(4, 'a-shared');
        $attempt = $this->attemptId('shared-browser');
        $this->session([
            'AuthV2' => 4,
            'AuthVersion' => 1,
            'Checkout.attempt_id' => $attempt,
            CartService::SESSION_KEY => 'a-shared',
        ]);
        $this->post('/checkout', $this->address());
        $this->assertResponseOk();
        $order = $this->fetchTable('SalesOrders')->find()
            ->where(['checkout_attempt_id' => $attempt])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame(2, (int)$order->customer_id);

        $this->post('/logout');
        $this->assertNull($this->getSession()->read('Checkout.attempt_id'));

        $this->session([
            'AuthV2' => 5,
            'AuthVersion' => 1,
            CartService::SESSION_KEY => 'b-shared',
        ]);
        $this->fillCart(5, 'b-shared');
        $this->enableCsrfToken();
        $this->post('/checkout', $this->address() + [
            'checkout_attempt_id' => $attempt,
        ]);
        $stolen = $this->fetchTable('SalesOrders')->find()
            ->where(['checkout_attempt_id' => $attempt])
            ->first();
        $this->assertNotNull($stolen);
        $this->assertSame(2, (int)$stolen->customer_id);
        $this->assertNotSame($attempt, $this->getSession()->read('Checkout.attempt_id'));
    }

    /**
     * Create-intent timeout keeps a short hold and the same Idempotency-Key resumes it.
     *
     * @return void
     */
    public function testUncertainTimeoutRetryReusesIntent(): void
    {
        $gateway = new FakePaymentGateway();
        $gateway->nextIntentId = 'pi_timeout_reuse';
        $gateway->createThenTimeout = true;
        $attempt = $this->attemptId('timeout-reuse');
        $checkout = $this->checkout($gateway, 'timeout-token', 'pi_timeout_reuse');
        $customer = $this->fetchTable('Customers')->get(2);
        try {
            $checkout->place($customer, 4, $this->cart(4, 'timeout-token'), $this->address(), false, $attempt);
            $this->fail('First create should time out after Stripe accepted the intent.');
        } catch (PaymentUncertainException) {
            // expected
        }
        $order = $this->fetchTable('SalesOrders')->find()
            ->where(['checkout_attempt_id' => $attempt])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame(SalesOrder::STATUS_DRAFT, $order->status);
        $expires = $order->get('hold_expires_at');
        $this->assertInstanceOf(DateTime::class, $expires);
        $this->assertTrue($expires->lessThanOrEquals(DateTime::now('UTC')->addMinutes(5)));

        $resumeCart = (new CartService())->current(4, 'timeout-token', true);
        $this->assertNotNull($resumeCart);
        $result = $checkout->place($customer, 4, $resumeCart, $this->address(), false, $attempt);
        $this->assertSame('pi_timeout_reuse_secret_test', $result['client_secret']);
        $this->assertSame($attempt, $gateway->lastIdempotencyKey);
    }

    /**
     * A failed refund must not restock or mark the order refunded.
     *
     * @return void
     */
    public function testPendingRefundFailedKeepsStock(): void
    {
        $order = $this->placeOrder('pi_refund_fail');
        $this->postSigned($this->eventPayload(
            'evt_refund_fail_pay',
            'payment_intent.succeeded',
            'pi_refund_fail',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $consumed = $this->onHand();
        $gateway = new FakePaymentGateway();
        $gateway->refundStatus = 'pending';
        $gateway->nextRefundId = 're_fail_1';
        (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);

        $this->postSigned($this->refundEventPayload(
            'evt_refund_fail',
            'refund.failed',
            're_fail_1',
            'pi_refund_fail',
            'failed',
        ));
        $this->assertResponseOk();
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($consumed, $this->onHand());
        $this->assertSame(
            'failed',
            (string)$this->fetchTable('PaymentRefunds')->find()
                ->where(['provider_refund_id' => 're_fail_1'])
                ->first()
                ?->get('status'),
        );
    }

    /**
     * A refund that never reached Stripe can be retried from the pending row.
     *
     * @return void
     */
    public function testRefundRetriesAfterGatewayFailure(): void
    {
        $order = $this->placeOrder('pi_refund_retry');
        $this->postSigned($this->eventPayload(
            'evt_refund_retry_pay',
            'payment_intent.succeeded',
            'pi_refund_retry',
            (int)$order->grand_total_cents,
            ['order_id' => (string)$order->id],
        ));
        $this->assertResponseOk();
        $gateway = new FakePaymentGateway();
        $gateway->throwOnRefund = true;
        try {
            (new RefundService(new OrderService(new InventoryLedger()), $gateway))
                ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
            $this->fail('The first refund call should fail before Stripe accepts it.');
        } catch (InvalidArgumentException) {
            // expected
        }
        $this->assertSame(
            1,
            $this->fetchTable('PaymentRefunds')->find()
                ->where(['payment_id' => $this->fetchTable('Payments')->find()
                    ->where(['provider_payment_id' => 'pi_refund_retry'])
                    ->first()
                    ?->id])
                ->count(),
        );
        $gateway->throwOnRefund = false;
        $gateway->refundStatus = 'pending';
        $gateway->nextRefundId = 're_retry_1';
        $payment = (new RefundService(new OrderService(new InventoryLedger()), $gateway))
            ->refundOrder($this->fetchTable('SalesOrders')->get($order->id), 1);
        $this->assertSame('captured', (string)$payment->status);
        $this->assertSame(
            'pending',
            (string)$this->fetchTable('PaymentRefunds')->find()
                ->where(['provider_refund_id' => 're_retry_1'])
                ->first()
                ?->get('status'),
        );
    }

    /**
     * Hold cleanup must skip a row another connection already locked.
     *
     * @return void
     */
    public function testHoldCleanupSkipsLockedMysqlRow(): void
    {
        $config = ConnectionManager::get('test')->config();
        $driver = (string)($config['driver'] ?? '');
        if (!str_contains($driver, 'Mysql')) {
            $this->markTestSkipped('Requires MySQL SKIP LOCKED.');
        }
        $order = $this->placeOrder('pi_skip_locked');
        $order->set('hold_expires_at', DateTime::now('UTC')->subMinutes(1));
        $this->fetchTable('SalesOrders')->saveOrFail($order);

        $pdo = $this->secondMysqlConnection($config);
        $pdo->beginTransaction();
        $locked = $pdo->query('SELECT id FROM sales_orders WHERE id = ' . (int)$order->id . ' FOR UPDATE');
        $this->assertNotFalse($locked);
        $this->assertNotSame([], $locked->fetchAll(PDO::FETCH_ASSOC));
        try {
            $started = microtime(true);
            $released = (new OrderService(new InventoryLedger()))->releaseExpiredHolds();
            $this->assertLessThan(2.0, microtime(true) - $started);
            $this->assertSame(0, $released);
            $this->assertSame(
                SalesOrder::STATUS_DRAFT,
                $this->fetchTable('SalesOrders')->get($order->id)->status,
            );
        } finally {
            $pdo->rollBack();
        }

        $this->assertSame(1, (new OrderService(new InventoryLedger()))->releaseExpiredHolds());
        $this->assertSame(
            SalesOrder::STATUS_CANCELLED,
            $this->fetchTable('SalesOrders')->get($order->id)->status,
        );
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

    /**
     * @param string $eventId Event id.
     * @param string $type Type.
     * @param string $refundId Refund id.
     * @param string $intentId PaymentIntent id.
     * @param string $status Stripe refund status.
     * @param int $amountCents Refund amount.
     * @param array<string, string> $metadata Stripe refund metadata.
     * @return string
     */
    private function refundEventPayload(
        string $eventId,
        string $type,
        string $refundId,
        string $intentId,
        string $status,
        int $amountCents = 100,
        array $metadata = [],
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
                    'id' => $refundId,
                    'object' => 'refund',
                    'amount' => $amountCents,
                    'currency' => 'aud',
                    'status' => $status,
                    'payment_intent' => $intentId,
                    'metadata' => $metadata,
                ],
            ],
        ];

        return json_encode($event, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $providerRefundId Stripe refund id.
     * @param string $status Stripe status.
     * @param string $intentId PaymentIntent id.
     * @param int $amountCents Amount.
     * @param string $localRefundId Local refund id.
     * @return array{process: resource, pipes: array<int, resource>}
     */
    private function startRefundChild(
        string $providerRefundId,
        string $status,
        string $intentId,
        int $amountCents,
        string $localRefundId,
    ): array {
        $config = ConnectionManager::get('test')->config();
        $script = dirname(__DIR__, 2) . '/bin/apply_refund_webhook.php';
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        $env['DEBUG'] = 'true';
        $env['DATABASE_URL'] = $this->mysqlUrl($config);
        if (($env['SECURITY_SALT'] ?? '') === '') {
            $env['SECURITY_SALT'] = str_repeat('a', 64);
        }
        $process = proc_open(
            [PHP_BINARY, $script, $providerRefundId, $status, $intentId, (string)$amountCents, $localRefundId],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 3),
            $env,
        );
        $this->assertIsResource($process);

        return ['process' => $process, 'pipes' => $pipes];
    }

    /**
     * @param array{process: resource, pipes: array<int, resource>} $child Child process.
     * @return array{code: int, output: string}
     */
    private function finishRefundChild(array $child): array
    {
        $stdout = stream_get_contents($child['pipes'][1]) ?: '';
        $stderr = stream_get_contents($child['pipes'][2]) ?: '';
        fclose($child['pipes'][1]);
        fclose($child['pipes'][2]);

        return [
            'code' => proc_close($child['process']),
            'output' => $stdout . $stderr,
        ];
    }

    /**
     * @param array<string, mixed> $config Cake datasource config.
     * @return string
     */
    private function mysqlUrl(array $config): string
    {
        if (!empty($config['url'])) {
            return (string)$config['url'];
        }

        return sprintf(
            'mysql://%s:%s@%s:%s/%s?encoding=utf8mb4',
            rawurlencode((string)($config['username'] ?? '')),
            rawurlencode((string)($config['password'] ?? '')),
            (string)($config['host'] ?? '127.0.0.1'),
            (string)($config['port'] ?? '3306'),
            (string)($config['database'] ?? ''),
        );
    }

    /**
     * @param array<string, mixed> $config Cake test datasource config.
     * @return \PDO
     */
    private function secondMysqlConnection(array $config): PDO
    {
        $host = (string)($config['host'] ?? '');
        $port = (string)($config['port'] ?? '3306');
        $database = (string)($config['database'] ?? '');
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');
        if ($host === '' && !empty($config['url'])) {
            $parts = parse_url((string)$config['url']);
            $host = (string)($parts['host'] ?? '127.0.0.1');
            $port = (string)($parts['port'] ?? '3306');
            $database = ltrim((string)($parts['path'] ?? ''), '/');
            $username = (string)($parts['user'] ?? '');
            $password = (string)($parts['pass'] ?? '');
        }

        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }
}
