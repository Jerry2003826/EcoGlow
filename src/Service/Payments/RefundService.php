<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use Cake\Database\Connection;
use Cake\Database\Exception\QueryException;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use function Cake\Core\env;

/**
 * Staff-initiated Stripe refunds. Full captured amount only.
 */
class RefundService
{
    use LocatorAwareTrait;

    /**
     * @var string
     */
    public const KIND_CUSTOMER_REFUND = 'customer_refund';

    /**
     * @var string
     */
    public const KIND_PARTIAL_CUSTOMER_REFUND = 'partial_customer_refund';

    /**
     * @var string
     */
    public const KIND_DUPLICATE_CAPTURE_REVERSAL = 'duplicate_capture_reversal';

    /**
     * @var string
     */
    public const KIND_CANCELLED_ORDER_REVERSAL = 'cancelled_order_reversal';

    /**
     * Refunds that reduce recognised order revenue.
     *
     * @var array<int, string>
     */
    public const REVENUE_KINDS = [
        self::KIND_CUSTOMER_REFUND,
        self::KIND_PARTIAL_CUSTOMER_REFUND,
    ];

    /**
     * Automatic capture reversals that must not change recognised sales.
     *
     * @var array<int, string>
     */
    public const REVERSAL_KINDS = [
        self::KIND_DUPLICATE_CAPTURE_REVERSAL,
        self::KIND_CANCELLED_ORDER_REVERSAL,
    ];

    /**
     * @var string
     */
    public const STATUS_RETRYABLE_FAILED = 'retryable_failed';

    /**
     * @var int
     */
    public const MAX_REVERSAL_ATTEMPTS = 5;

    /**
     * @var int
     */
    public const REVERSAL_RETRY_MINUTES = 5;

    /**
     * @param \App\Service\Orders\OrderService $orders Restock helper.
     * @param \App\Service\Payments\PaymentGatewayInterface $gateway Stripe or test double.
     */
    public function __construct(
        private OrderService $orders,
        private PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param int $actorUserId Staff user.
     * @return \App\Model\Entity\Payment
     */
    public function refundOrder(SalesOrder $order, int $actorUserId): Payment
    {
        $prepared = $this->connection()->transactional(function () use ($order, $actorUserId) {
            $this->orders->lockOrder((int)$order->id);
            $paymentRow = $this->lockAssoc(
                "SELECT id, status, amount_cents, provider_payment_id, currency
                   FROM payments
                  WHERE sales_order_id = ?
                    AND provider = 'stripe'
                    AND status = 'captured'
                  ORDER BY id DESC
                  LIMIT 1
                  FOR UPDATE",
                [(int)$order->id],
            );
            if ($paymentRow === null) {
                throw new InvalidArgumentException('This order has no captured Stripe payment to refund.');
            }
            $siblings = $this->lockAll(
                'SELECT id, status, provider_refund_id, idempotency_key, amount_cents, refund_kind
                   FROM payment_refunds WHERE payment_id = ? FOR UPDATE',
                [(int)$paymentRow['id']],
            );
            foreach ($siblings as $sibling) {
                if (self::isReversalKind((string)($sibling['refund_kind'] ?? ''))) {
                    throw new InvalidArgumentException(
                        'An automatic capture reversal is already in progress for this payment.',
                    );
                }
            }
            foreach ($siblings as $sibling) {
                if (
                    in_array((string)$sibling['status'], ['succeeded', 'completed'], true)
                    && self::isRevenueKind((string)($sibling['refund_kind'] ?? self::KIND_CUSTOMER_REFUND))
                ) {
                    throw new InvalidArgumentException('A refund has already been recorded for this payment.');
                }
            }
            foreach ($siblings as $sibling) {
                if ((string)$sibling['status'] !== 'pending') {
                    continue;
                }
                if (!self::isRevenueKind((string)($sibling['refund_kind'] ?? self::KIND_CUSTOMER_REFUND))) {
                    continue;
                }
                $providerId = (string)($sibling['provider_refund_id'] ?? '');

                return [
                    'payment_id' => (int)$paymentRow['id'],
                    'provider_payment_id' => (string)$paymentRow['provider_payment_id'],
                    'amount_cents' => (int)$paymentRow['amount_cents'],
                    'currency' => strtolower((string)($paymentRow['currency'] ?: 'aud')),
                    'refund_id' => (int)$sibling['id'],
                    'provider_refund_id' => $providerId,
                    'key' => (string)($sibling['idempotency_key'] ?: ''),
                    'retry' => $providerId === '',
                ];
            }

            $refunds = $this->fetchTable('PaymentRefunds');
            $row = $refunds->newEmptyEntity();
            $draftKey = 'refund-draft-' . bin2hex(random_bytes(16));
            $row->set('payment_id', (int)$paymentRow['id']);
            $row->set('idempotency_key', $draftKey);
            $row->set('status', 'pending');
            $row->set('amount_cents', (int)$paymentRow['amount_cents']);
            $row->set('reason', 'Staff refund');
            $row->set('refund_kind', self::KIND_CUSTOMER_REFUND);
            $row->set('requested_by_user_id', $actorUserId);
            $refunds->saveOrFail($row);
            $key = 'refund-payment-' . $paymentRow['id'] . '-attempt-' . $row->id;
            $row->set('idempotency_key', $key);
            $refunds->saveOrFail($row);

            return [
                'payment_id' => (int)$paymentRow['id'],
                'provider_payment_id' => (string)$paymentRow['provider_payment_id'],
                'amount_cents' => (int)$paymentRow['amount_cents'],
                'currency' => strtolower((string)($paymentRow['currency'] ?: 'aud')),
                'refund_id' => (int)$row->id,
                'provider_refund_id' => '',
                'key' => $key,
                'retry' => true,
            ];
        });

        if (!(bool)$prepared['retry']) {
            $retrieved = $this->gateway->retrieveRefund((string)$prepared['provider_refund_id']);
            if ($retrieved !== null) {
                $this->applyProviderResult(
                    (int)$prepared['refund_id'],
                    $retrieved->id,
                    $retrieved->status,
                    $actorUserId,
                    $retrieved->amountCents,
                    $retrieved->currency,
                );
            }

            /** @var \App\Model\Entity\Payment $payment */
            $payment = $this->fetchTable('Payments')->get((int)$prepared['payment_id']);

            return $payment;
        }

        $result = $this->gateway->refund(
            (string)$prepared['provider_payment_id'],
            (int)$prepared['amount_cents'],
            (string)$prepared['key'],
            [
                'local_refund_id' => (string)$prepared['refund_id'],
                'local_payment_id' => (string)$prepared['payment_id'],
                'refund_binding_token' => self::bindingToken(
                    (int)$prepared['refund_id'],
                    (int)$prepared['payment_id'],
                    (int)$prepared['amount_cents'],
                    (string)$prepared['currency'],
                ),
            ],
        );
        $this->applyProviderResult(
            (int)$prepared['refund_id'],
            $result->id,
            $result->status,
            $actorUserId,
            $result->amountCents,
            $result->currency,
        );

        /** @var \App\Model\Entity\Payment $payment */
        $payment = $this->fetchTable('Payments')->get((int)$prepared['payment_id']);

        return $payment;
    }

    /**
     * Reverse a Stripe capture that the local order can no longer accept.
     *
     * Pending is a saved local state, not an error. Uncertain network
     * results stay retryable with the same idempotency key. A Stripe
     * failed/canceled result starts a new attempt with a new key.
     *
     * @param int $paymentId Local Stripe payment.
     * @param string $kind duplicate_capture_reversal|cancelled_order_reversal.
     * @return \App\Service\Payments\ReversalResult
     */
    public function reverseUnexpectedCapture(int $paymentId, string $kind): ReversalResult
    {
        if (!self::isReversalKind($kind)) {
            throw new InvalidArgumentException('Unknown unexpected-capture refund kind.');
        }
        $prepared = $this->connection()->transactional(function () use ($paymentId, $kind) {
            return $this->prepareUnexpectedCapture($paymentId, $kind);
        });
        if (($prepared['outcome'] ?? '') !== 'call' && ($prepared['outcome'] ?? '') !== 'retrieve') {
            return $this->reversalResultFromRow(
                (int)($prepared['refund_id'] ?? 0),
                (string)($prepared['status'] ?? 'failed'),
                isset($prepared['provider_refund_id']) ? (string)$prepared['provider_refund_id'] : null,
            );
        }

        if (($prepared['outcome'] ?? '') === 'retrieve') {
            $retrieved = $this->gateway->retrieveRefund((string)$prepared['provider_refund_id']);
            if ($retrieved !== null) {
                $this->applyProviderResult(
                    (int)$prepared['refund_id'],
                    $retrieved->id,
                    $retrieved->status,
                    0,
                    $retrieved->amountCents,
                    $retrieved->currency,
                );
            }

            return $this->reversalSnapshot((int)$prepared['refund_id']);
        }

        try {
            $result = $this->gateway->refund(
                (string)$prepared['provider_payment_id'],
                (int)$prepared['amount_cents'],
                (string)$prepared['key'],
                [
                    'local_refund_id' => (string)$prepared['refund_id'],
                    'local_payment_id' => (string)$prepared['payment_id'],
                    'refund_binding_token' => self::bindingToken(
                        (int)$prepared['refund_id'],
                        (int)$prepared['payment_id'],
                        (int)$prepared['amount_cents'],
                        (string)$prepared['currency'],
                    ),
                    'refund_type' => $kind,
                ],
            );
        } catch (PaymentUncertainException $exception) {
            throw new RuntimeException(
                'Unexpected Stripe capture could not be refunded yet.',
                0,
                $exception,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Unexpected Stripe capture could not be refunded yet.',
                0,
                $exception,
            );
        }
        $this->applyProviderResult(
            (int)$prepared['refund_id'],
            $result->id,
            $result->status,
            0,
            $result->amountCents,
            $result->currency,
        );

        return $this->reversalSnapshot((int)$prepared['refund_id']);
    }

    /**
     * Pull the latest Stripe status for pending refunds that already have a provider id.
     *
     * @return int Number of rows updated.
     */
    public function reconcilePending(): int
    {
        $pending = $this->fetchTable('PaymentRefunds')->find()
            ->where([
                'status' => 'pending',
                'provider_refund_id IS NOT' => null,
                'provider_refund_id !=' => '',
            ])
            ->all();
        $updated = 0;
        foreach ($pending as $row) {
            $result = $this->gateway->retrieveRefund((string)$row->get('provider_refund_id'));
            if ($result === null) {
                continue;
            }
            $before = (string)$row->get('status');
            $this->applyProviderResult(
                (int)$row->id,
                $result->id,
                $result->status,
                (int)($row->get('requested_by_user_id') ?: 0),
                $result->amountCents,
                $result->currency,
            );
            $after = (string)$this->fetchTable('PaymentRefunds')->get($row->id)->get('status');
            if ($after !== $before) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Start a new automatic-reversal attempt after Stripe reported a hard failure.
     *
     * @return int Number of payments that started a new attempt.
     */
    public function retryFailedReversals(): int
    {
        $due = $this->fetchTable('PaymentRefunds')->find()
            ->where([
                'status' => self::STATUS_RETRYABLE_FAILED,
                'refund_kind IN' => self::REVERSAL_KINDS,
                'OR' => [
                    'retry_scheduled_at IS' => null,
                    'retry_scheduled_at <=' => DateTime::now('UTC')->format('Y-m-d H:i:s'),
                ],
            ])
            ->all();
        $retried = 0;
        $seen = [];
        foreach ($due as $row) {
            $paymentId = (int)$row->get('payment_id');
            if (isset($seen[$paymentId])) {
                continue;
            }
            $seen[$paymentId] = true;
            $kind = (string)$row->get('refund_kind');
            $before = (string)$row->get('idempotency_key');
            $result = $this->reverseUnexpectedCapture($paymentId, $kind);
            if ($result->refundId > 0 && $result->refundId !== (int)$row->get('id')) {
                $retried++;
                continue;
            }
            $after = (string)$this->fetchTable('PaymentRefunds')->get($row->get('id'))->get('idempotency_key');
            if ($after !== $before || $result->status !== 'failed') {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Complete or fail a refund from a Stripe webhook.
     *
     * Unknown Dashboard refunds raise a reconciliation alert and do not
     * change order, invoice, or inventory state.
     *
     * @param string $providerRefundId Stripe refund id.
     * @param string $status Stripe refund status.
     * @param string $paymentIntentId PaymentIntent id when the local row is missing.
     * @param int $amountCents Stripe refund amount.
     * @param string $currency Stripe currency.
     * @param array<string, string> $metadata Stripe refund metadata.
     * @return void
     */
    public function applyWebhookStatus(
        string $providerRefundId,
        string $status,
        string $paymentIntentId = '',
        int $amountCents = 0,
        string $currency = '',
        array $metadata = [],
    ): void {
        if ($providerRefundId === '') {
            return;
        }
        $payment = $this->findPaymentByIntent($paymentIntentId);
        $refund = $this->findRefundByProvider($providerRefundId);
        if ($refund === null) {
            $refund = $this->findExactLocalRefund($metadata, $payment, $amountCents, $providerRefundId);
        }
        if ($refund === null) {
            if ($payment === null && $paymentIntentId !== '') {
                throw new RuntimeException('Stripe refund does not match a local payment yet.');
            }
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                $paymentIntentId,
                $payment !== null ? (int)$payment['sales_order_id'] : null,
                $amountCents,
                $currency,
            );

            return;
        }
        if (
            $payment !== null
            && (int)$refund['payment_id'] !== (int)$payment['id']
        ) {
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                $paymentIntentId,
                (int)$payment['sales_order_id'],
                $amountCents,
                $currency,
                'Refund metadata does not match the local payment.',
            );

            return;
        }
        $this->applyProviderResult(
            (int)$refund['id'],
            $providerRefundId,
            $status,
            (int)($refund['requested_by_user_id'] ?: 0),
            $amountCents,
            $currency,
        );
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return \App\Model\Entity\Payment|null
     */
    public function capturedStripePayment(SalesOrder $order): ?Payment
    {
        /** @var \App\Model\Entity\Payment|null $payment */
        $payment = $this->fetchTable('Payments')->find()
            ->where([
                'sales_order_id' => $order->id,
                'provider' => 'stripe',
                'status' => 'captured',
            ])
            ->orderBy(['Payments.id' => 'DESC'])
            ->first();

        return $payment;
    }

    /**
     * @param int $refundId Local refund id.
     * @param string $providerRefundId Stripe refund id.
     * @param string $status Stripe status.
     * @param int $actorUserId Actor, or 0 for webhooks.
     * @param int $amountCents Stripe amount when known.
     * @param string $currency Stripe currency when known.
     * @return void
     */
    private function applyProviderResult(
        int $refundId,
        string $providerRefundId,
        string $status,
        int $actorUserId,
        int $amountCents = 0,
        string $currency = '',
    ): void {
        $ids = $this->refundGraph($refundId);
        if ($ids === null) {
            return;
        }
        $this->withRefundLockRetry(function () use (
            $ids,
            $refundId,
            $providerRefundId,
            $status,
            $actorUserId,
            $amountCents,
            $currency,
        ): void {
            $this->applyLockedProviderResult(
                (int)$ids['sales_order_id'],
                (int)$ids['payment_id'],
                $refundId,
                $providerRefundId,
                $status,
                $actorUserId,
                $amountCents,
                $currency,
            );
        });
    }

    /**
     * First statements in this transaction are locking reads.
     *
     * @param int $orderId Order id.
     * @param int $paymentId Payment id.
     * @param int $refundId Local refund id.
     * @param string $providerRefundId Stripe refund id.
     * @param string $status Stripe status.
     * @param int $actorUserId Actor, or 0 for webhooks.
     * @param int $amountCents Stripe amount when known.
     * @param string $currency Stripe currency when known.
     * @return void
     */
    private function applyLockedProviderResult(
        int $orderId,
        int $paymentId,
        int $refundId,
        string $providerRefundId,
        string $status,
        int $actorUserId,
        int $amountCents,
        string $currency,
    ): void {
        $this->orders->lockOrder($orderId);
        $payment = $this->lockAssoc(
            'SELECT * FROM payments WHERE id = ? FOR UPDATE',
            [$paymentId],
        );
        if ($payment === null) {
            return;
        }
        $order = $this->lockAssoc(
            'SELECT * FROM sales_orders WHERE id = ? FOR UPDATE',
            [$orderId],
        );
        $refund = $this->lockAssoc(
            'SELECT * FROM payment_refunds WHERE id = ? FOR UPDATE',
            [$refundId],
        );
        if ($refund === null) {
            return;
        }
        $existingProvider = (string)($refund['provider_refund_id'] ?? '');
        if ($existingProvider !== '' && $providerRefundId !== '' && $existingProvider !== $providerRefundId) {
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                (string)$payment['provider_payment_id'],
                (int)$payment['sales_order_id'],
                $amountCents,
                $currency,
                'Refund is already bound to a different Stripe refund.',
            );

            return;
        }
        $siblings = $this->lockAll(
            'SELECT id, amount_cents, status FROM payment_refunds WHERE payment_id = ? FOR UPDATE',
            [(int)$payment['id']],
        );
        $invoice = $this->lockAssoc(
            'SELECT id, amount_paid_cents, grand_total_cents, status, paid_at,
                    sales_order_id, customer_id, currency, tax_cents, subtotal_cents
               FROM invoices
              WHERE sales_order_id = ? AND status != ?
              FOR UPDATE',
            [(int)$payment['sales_order_id'], Invoice::STATUS_VOID],
        );

        if (in_array((string)$refund['status'], ['succeeded', 'completed'], true)) {
            return;
        }

        $normalized = strtolower($status);
        if ($currency !== '') {
            $expected = strtolower((string)($payment['currency'] ?: 'AUD'));
            if (strtolower($currency) !== $expected) {
                $this->raiseExternalRefundAlert(
                    $providerRefundId,
                    (string)$payment['provider_payment_id'],
                    (int)$payment['sales_order_id'],
                    $amountCents,
                    $currency,
                    'Refund currency does not match the captured payment.',
                );

                return;
            }
        }

        $bindId = $providerRefundId !== '' ? $providerRefundId : $existingProvider;
        if (in_array($normalized, ['failed', 'canceled', 'cancelled'], true)) {
            $this->markRefundFailed(
                $refund,
                $payment,
                $bindId !== '' ? $bindId : null,
                $normalized,
            );

            return;
        }

        if (!in_array($normalized, ['succeeded', 'paid'], true)) {
            $this->connection()->execute(
                "UPDATE payment_refunds
                    SET status = 'pending', provider_refund_id = ?
                  WHERE id = ?",
                [$bindId !== '' ? $bindId : null, $refundId],
            );

            return;
        }

        $applyAmount = $amountCents > 0 ? $amountCents : (int)$refund['amount_cents'];
        if ($amountCents > 0 && (int)$refund['amount_cents'] !== $amountCents) {
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                (string)$payment['provider_payment_id'],
                (int)$payment['sales_order_id'],
                $amountCents,
                $currency,
                'Refund amount does not match the local refund.',
            );

            return;
        }
        $refundedTotal = 0;
        foreach ($siblings as $sibling) {
            if (
                (int)$sibling['id'] !== $refundId
                && in_array((string)$sibling['status'], ['succeeded', 'completed'], true)
            ) {
                $refundedTotal += (int)$sibling['amount_cents'];
            }
        }
        $captured = (int)$payment['amount_cents'];
        if ($applyAmount <= 0 || $refundedTotal + $applyAmount > $captured) {
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                (string)$payment['provider_payment_id'],
                (int)$payment['sales_order_id'],
                $amountCents,
                $currency,
                'Succeeded refunds exceed the captured amount.',
            );

            return;
        }

        $this->connection()->execute(
            "UPDATE payment_refunds
                SET status = 'succeeded',
                    provider_refund_id = ?,
                    amount_cents = ?,
                    completed_at = ?
              WHERE id = ?",
            [$bindId, $applyAmount, DateTime::now('UTC')->format('Y-m-d H:i:s'), $refundId],
        );

        $refundedTotal += $applyAmount;
        $nextStatus = $refundedTotal >= $captured ? 'refunded' : 'partially_refunded';
        if ((string)$payment['status'] !== 'refunded') {
            $this->connection()->execute(
                'UPDATE payments SET status = ? WHERE id = ?',
                [$nextStatus, (int)$payment['id']],
            );
        }
        $kind = (string)($refund['refund_kind'] ?? self::KIND_CUSTOMER_REFUND);
        if ($kind === '') {
            $kind = self::KIND_CUSTOMER_REFUND;
        }
        $affectsRevenue = in_array($kind, self::REVENUE_KINDS, true);
        if (
            $affectsRevenue
            && $order !== null
            && (string)$order['payment_status'] !== 'refunded'
        ) {
            $this->connection()->execute(
                'UPDATE sales_orders SET payment_status = ? WHERE id = ?',
                [$nextStatus, (int)$order['id']],
            );
        }

        if ($affectsRevenue) {
            $this->reverseInvoiceCreditFromLock(
                (int)$payment['id'],
                $invoice,
                $applyAmount,
                $refundId,
            );
        }

        $staffInitiated = $actorUserId > 0 || (string)$refund['reason'] === 'Staff refund';
        if (
            $affectsRevenue
            && $staffInitiated
            && $nextStatus === 'refunded'
            && $order !== null
        ) {
            $actor = $actorUserId > 0 ? $actorUserId : (int)($order['created_by_user_id'] ?: 0);
            /** @var \App\Model\Entity\SalesOrder $orderEntity */
            $orderEntity = $this->fetchTable('SalesOrders')->newEmptyEntity();
            $orderEntity->id = (int)$order['id'];
            $orderEntity->status = (string)$order['status'];
            $orderEntity->order_number = (string)$order['order_number'];
            $orderEntity->setNew(false);
            $this->orders->restockIfUnshipped($orderEntity, $actor);
            if (
                !in_array((string)$order['status'], [
                    SalesOrder::STATUS_DISPATCHED,
                    SalesOrder::STATUS_COMPLETED,
                    SalesOrder::STATUS_CANCELLED,
                ], true)
            ) {
                $this->orders->changeStatus(
                    $orderEntity,
                    SalesOrder::STATUS_CANCELLED,
                    $actor > 0 ? $actor : 1,
                    'Full refund; unshipped order cancelled.',
                );
            }
        }
    }

    /**
     * @param int $refundId Local refund id.
     * @return array<string, mixed>|null
     */
    private function refundGraph(int $refundId): ?array
    {
        $row = $this->connection()->execute(
            'SELECT r.id AS refund_id, p.id AS payment_id, p.sales_order_id
               FROM payment_refunds r
               INNER JOIN payments p ON p.id = r.payment_id
              WHERE r.id = ?',
            [$refundId],
        )->fetch('assoc');

        return is_array($row) ? $row : null;
    }

    /**
     * Retry lock conflicts from MySQL snapshot isolation or deadlocks.
     *
     * @param \Closure(): void $operation Locked refund application.
     * @return void
     */
    private function withRefundLockRetry(Closure $operation): void
    {
        $attempt = 0;
        while (true) {
            try {
                $this->connection()->transactional($operation);

                return;
            } catch (QueryException $exception) {
                $attempt++;
                if ($attempt >= 5 || !$this->isRetryableLockFailure($exception)) {
                    throw $exception;
                }
                usleep(20_000 * $attempt);
            }
        }
    }

    /**
     * @param \Throwable $exception Database error.
     * @return bool
     */
    private function isRetryableLockFailure(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, '1020')
            || str_contains($message, '1213')
            || str_contains($message, 'Deadlock')
            || str_contains($message, 'Record has changed since last read');
    }

    /**
     * @param string $paymentIntentId Stripe PaymentIntent id.
     * @return array<string, mixed>|null
     */
    private function findPaymentByIntent(string $paymentIntentId): ?array
    {
        if ($paymentIntentId === '') {
            return null;
        }
        $statement = $this->connection()->execute(
            "SELECT * FROM payments WHERE provider = 'stripe' AND provider_payment_id = ?",
            [$paymentIntentId],
        );
        $row = $statement->fetch('assoc');

        return is_array($row) ? $row : null;
    }

    /**
     * @param string $providerRefundId Stripe refund id.
     * @return array<string, mixed>|null
     */
    private function findRefundByProvider(string $providerRefundId): ?array
    {
        $statement = $this->connection()->execute(
            'SELECT * FROM payment_refunds WHERE provider_refund_id = ?',
            [$providerRefundId],
        );
        $row = $statement->fetch('assoc');

        return is_array($row) ? $row : null;
    }

    /**
     * Bind a webhook only when metadata identifies one local refund exactly.
     *
     * @param array<string, string> $metadata Stripe metadata.
     * @param array<string, mixed>|null $payment Payment for this PaymentIntent.
     * @param int $amountCents Stripe amount.
     * @param string $providerRefundId Stripe refund id.
     * @return array<string, mixed>|null
     */
    private function findExactLocalRefund(
        array $metadata,
        ?array $payment,
        int $amountCents,
        string $providerRefundId,
    ): ?array {
        $localRefundId = (int)($metadata['local_refund_id'] ?? 0);
        if ($localRefundId < 1) {
            return null;
        }
        $statement = $this->connection()->execute(
            'SELECT * FROM payment_refunds WHERE id = ?',
            [$localRefundId],
        );
        $refund = $statement->fetch('assoc');
        if (!is_array($refund)) {
            return null;
        }
        $existingProvider = (string)($refund['provider_refund_id'] ?? '');
        if ($existingProvider !== '' && $existingProvider !== $providerRefundId) {
            return null;
        }
        if ($payment !== null && (int)$refund['payment_id'] !== (int)$payment['id']) {
            return null;
        }
        $expectedPaymentId = (int)($metadata['local_payment_id'] ?? 0);
        if ($expectedPaymentId > 0 && (int)$refund['payment_id'] !== $expectedPaymentId) {
            return null;
        }
        if ($amountCents > 0 && (int)$refund['amount_cents'] !== $amountCents) {
            return null;
        }
        $currency = strtolower((string)($payment['currency'] ?? 'aud'));
        $expected = self::bindingToken(
            $localRefundId,
            (int)$refund['payment_id'],
            (int)$refund['amount_cents'],
            $currency,
        );
        $given = (string)($metadata['refund_binding_token'] ?? '');
        if ($given === '' || !hash_equals($expected, $given)) {
            return null;
        }

        return $refund;
    }

    /**
     * Unforgeable binding for a staff-created Stripe refund.
     *
     * @param int $refundId Local refund id.
     * @param int $paymentId Local payment id.
     * @param int $amountCents Refund amount.
     * @param string $currency ISO currency.
     * @return string
     */
    public static function bindingToken(
        int $refundId,
        int $paymentId,
        int $amountCents,
        string $currency,
    ): string {
        return hash_hmac(
            'sha256',
            $refundId . '|' . $paymentId . '|' . $amountCents . '|' . strtolower($currency),
            self::bindingSecret(),
        );
    }

    /**
     * @return string
     */
    private static function bindingSecret(): string
    {
        $dedicated = (string)env('REFUND_BINDING_KEY', '');
        if ($dedicated !== '') {
            return $dedicated;
        }

        return Security::getSalt();
    }

    /**
     * @param int $paymentId Payment id.
     * @param array<string, mixed>|null $invoice Locked invoice.
     * @param int $amountCents Refunded amount.
     * @param int $refundId Local refund id.
     * @return void
     */
    private function reverseInvoiceCreditFromLock(
        int $paymentId,
        ?array $invoice,
        int $amountCents,
        int $refundId,
    ): void {
        if ($invoice === null || $amountCents <= 0 || $refundId < 1) {
            return;
        }
        $now = DateTime::now('UTC')->format('Y-m-d H:i:s');
        $inserted = $this->connection()->execute(
            'INSERT INTO payment_allocations (
                payment_id, invoice_id, payment_refund_id, allocation_type, effect_key,
                amount_cents, allocated_at, created
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id',
            [
                $paymentId,
                (int)$invoice['id'],
                $refundId,
                'refund',
                'refund-' . $refundId,
                $amountCents,
                $now,
                $now,
            ],
        )->rowCount() === 1;
        if (!$inserted) {
            return;
        }

        $status = (string)$invoice['status'];
        $paidCents = (int)$invoice['amount_paid_cents'];
        $grandCents = (int)$invoice['grand_total_cents'];
        $settled = in_array($status, [Invoice::STATUS_PAID, Invoice::STATUS_CREDITED], true)
            || ($grandCents > 0 && $paidCents >= $grandCents);
        if ($settled) {
            $this->issueRefundCreditNote($invoice, $amountCents, $refundId);
            $credited = $this->connection()->execute(
                "UPDATE invoices
                    SET status = ?
                  WHERE id = ?
                    AND status IN (?, ?)
                    AND grand_total_cents <= (
                        SELECT COALESCE(SUM(amount_cents), 0)
                          FROM payment_allocations
                         WHERE invoice_id = ?
                           AND allocation_type = 'refund'
                    )",
                [
                    Invoice::STATUS_CREDITED,
                    (int)$invoice['id'],
                    Invoice::STATUS_PAID,
                    Invoice::STATUS_OVERDUE,
                    (int)$invoice['id'],
                ],
            )->rowCount() === 1;
            if ($credited) {
                $this->connection()->execute(
                    'INSERT INTO invoice_status_history
                        (invoice_id, from_status, to_status, note, created)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        (int)$invoice['id'],
                        $status,
                        Invoice::STATUS_CREDITED,
                        'Refund credited; paid history retained.',
                        $now,
                    ],
                );
            }

            return;
        }

        $paid = max(0, $paidCents - $amountCents);
        $this->connection()->execute(
            'UPDATE invoices SET amount_paid_cents = ? WHERE id = ?',
            [$paid, (int)$invoice['id']],
        );
    }

    /**
     * @param array<string, mixed> $invoice Locked invoice.
     * @param int $amountCents Refunded amount.
     * @param int $refundId Local refund id.
     * @return void
     */
    private function issueRefundCreditNote(array $invoice, int $amountCents, int $refundId): void
    {
        $existing = $this->connection()->execute(
            "SELECT id FROM credit_notes
              WHERE invoice_id = ?
                AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.payment_refund_id')) = ?",
            [(int)$invoice['id'], (string)$refundId],
        )->fetch('assoc');
        if (is_array($existing)) {
            return;
        }

        $number = (new InventoryLedger())->nextDocumentNumber('credit_note', 'CN');
        $now = DateTime::now('UTC')->format('Y-m-d H:i:s');
        $taxCents = $this->refundTaxCents($invoice, $amountCents);
        $subtotalCents = $amountCents - $taxCents;
        $metadata = json_encode(
            [
                'payment_refund_id' => (string)$refundId,
                'source' => 'stripe_refund',
            ],
            JSON_UNESCAPED_SLASHES,
        );
        $this->connection()->execute(
            'INSERT INTO credit_notes (
                credit_note_number, invoice_id, sales_order_id, customer_id, status,
                reason_code, reason, currency, subtotal_cents, tax_cents, total_cents,
                applied_cents, issue_date, issued_at, metadata, created, modified
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $number,
                (int)$invoice['id'],
                $invoice['sales_order_id'] !== null ? (int)$invoice['sales_order_id'] : null,
                $invoice['customer_id'] !== null ? (int)$invoice['customer_id'] : null,
                'issued',
                'refund',
                'Stripe refund ' . $refundId,
                (string)($invoice['currency'] ?: 'AUD'),
                $subtotalCents,
                $taxCents,
                $amountCents,
                $amountCents,
                Date::now('Australia/Melbourne')->format('Y-m-d'),
                $now,
                $metadata,
                $now,
                $now,
            ],
        );
        $creditNoteId = (int)($this->connection()->execute(
            'SELECT LAST_INSERT_ID() AS id',
        )->fetch('assoc')['id'] ?? 0);
        if ($creditNoteId < 1) {
            throw new RuntimeException('Credit note insert did not return an id.');
        }
        $this->connection()->execute(
            'INSERT INTO credit_note_items (
                credit_note_id, line_number, description_snapshot, quantity,
                unit_amount_cents, tax_cents, line_total_cents, created
             ) VALUES (?, 1, ?, 1, ?, ?, ?, ?)',
            [
                $creditNoteId,
                'Refund allocation for payment refund ' . $refundId,
                $subtotalCents,
                $taxCents,
                $amountCents,
                $now,
            ],
        );
    }

    /**
     * GST portion of a refund, matching the dashboard remaining-tax formula.
     *
     * @param array<string, mixed> $invoice Locked invoice.
     * @param int $amountCents Refunded amount.
     * @return int
     */
    private function refundTaxCents(array $invoice, int $amountCents): int
    {
        $grand = (int)$invoice['grand_total_cents'];
        $tax = (int)$invoice['tax_cents'];
        if ($grand <= 0 || $tax <= 0 || $amountCents <= 0) {
            return 0;
        }

        return min($amountCents, intdiv($tax * $amountCents, $grand));
    }

    /**
     * @param int $paymentId Local payment.
     * @param string $kind Reversal kind.
     * @return array<string, mixed>
     */
    private function prepareUnexpectedCapture(int $paymentId, string $kind): array
    {
        $payment = $this->lockAssoc(
            'SELECT * FROM payments WHERE id = ? FOR UPDATE',
            [$paymentId],
        );
        if ($payment === null) {
            throw new InvalidArgumentException('Payment not found.');
        }
        $siblings = $this->lockAll(
            'SELECT id, status, provider_refund_id, idempotency_key, refund_kind, attempt_count
               FROM payment_refunds WHERE payment_id = ? FOR UPDATE',
            [$paymentId],
        );
        foreach ($siblings as $sibling) {
            if (in_array((string)$sibling['status'], ['succeeded', 'completed'], true)) {
                if ((string)$payment['status'] !== 'refunded') {
                    $this->connection()->execute(
                        "UPDATE payments SET status = 'refunded' WHERE id = ?",
                        [$paymentId],
                    );
                }

                return [
                    'outcome' => 'done',
                    'status' => 'succeeded',
                    'refund_id' => (int)$sibling['id'],
                    'provider_refund_id' => (string)($sibling['provider_refund_id'] ?? ''),
                ];
            }
        }
        if (in_array((string)$payment['status'], ['refunded', 'partially_refunded'], true)) {
            return [
                'outcome' => 'done',
                'status' => 'succeeded',
                'refund_id' => (int)($siblings[0]['id'] ?? 0),
                'provider_refund_id' => (string)($siblings[0]['provider_refund_id'] ?? ''),
            ];
        }
        foreach ($siblings as $sibling) {
            if ((string)$sibling['status'] !== 'pending') {
                continue;
            }
            $providerId = (string)($sibling['provider_refund_id'] ?? '');

            return [
                'outcome' => $providerId === '' ? 'call' : 'retrieve',
                'payment_id' => $paymentId,
                'provider_payment_id' => (string)$payment['provider_payment_id'],
                'amount_cents' => (int)$payment['amount_cents'],
                'currency' => strtolower((string)($payment['currency'] ?: 'aud')),
                'refund_id' => (int)$sibling['id'],
                'provider_refund_id' => $providerId,
                'key' => (string)($sibling['idempotency_key'] ?? ''),
            ];
        }

        $attempt = 1;
        $last = null;
        foreach ($siblings as $sibling) {
            $attempt = max($attempt, (int)($sibling['attempt_count'] ?? 1) + 1);
            $last = $sibling;
        }
        if ($attempt > self::MAX_REVERSAL_ATTEMPTS) {
            return [
                'outcome' => 'done',
                'status' => 'failed',
                'refund_id' => (int)($last['id'] ?? 0),
                'provider_refund_id' => (string)($last['provider_refund_id'] ?? ''),
            ];
        }

        $this->connection()->execute(
            "UPDATE payment_refunds
                SET status = 'failed', retry_scheduled_at = NULL
              WHERE payment_id = ?
                AND status = ?",
            [$paymentId, self::STATUS_RETRYABLE_FAILED],
        );

        $refunds = $this->fetchTable('PaymentRefunds');
        $row = $refunds->newEmptyEntity();
        $draftKey = 'reversal-draft-' . bin2hex(random_bytes(16));
        $row->set('payment_id', $paymentId);
        $row->set('idempotency_key', $draftKey);
        $row->set('status', 'pending');
        $row->set('amount_cents', (int)$payment['amount_cents']);
        $row->set('reason', 'Unexpected Stripe capture');
        $row->set('refund_kind', $kind);
        $row->set('attempt_count', $attempt);
        $refunds->saveOrFail($row);
        $key = 'unexpected-capture-' . $paymentId . '-attempt-' . (int)$row->get('id');
        $row->set('idempotency_key', $key);
        $refunds->saveOrFail($row);

        return [
            'outcome' => 'call',
            'payment_id' => $paymentId,
            'provider_payment_id' => (string)$payment['provider_payment_id'],
            'amount_cents' => (int)$payment['amount_cents'],
            'currency' => strtolower((string)($payment['currency'] ?: 'aud')),
            'refund_id' => (int)$row->get('id'),
            'provider_refund_id' => '',
            'key' => $key,
        ];
    }

    /**
     * @param array<string, mixed> $refund Locked refund.
     * @param array<string, mixed> $payment Locked payment.
     * @param string|null $providerRefundId Stripe refund id.
     * @param string $status Stripe failure status.
     * @return void
     */
    private function markRefundFailed(
        array $refund,
        array $payment,
        ?string $providerRefundId,
        string $status,
    ): void {
        $refundId = (int)$refund['id'];
        $kind = (string)($refund['refund_kind'] ?? '');
        $attempts = max(1, (int)($refund['attempt_count'] ?? 1));
        $retryable = self::isReversalKind($kind) && $attempts < self::MAX_REVERSAL_ATTEMPTS;
        $nextStatus = $retryable ? self::STATUS_RETRYABLE_FAILED : 'failed';
        $retryAt = $retryable
            ? DateTime::now('UTC')->addMinutes(self::REVERSAL_RETRY_MINUTES)->format('Y-m-d H:i:s')
            : null;
        $this->connection()->execute(
            'UPDATE payment_refunds
                SET status = ?,
                    provider_refund_id = ?,
                    failure_reason = ?,
                    retry_scheduled_at = ?
              WHERE id = ?',
            [$nextStatus, $providerRefundId, $status, $retryAt, $refundId],
        );
        if (!$retryable && self::isReversalKind($kind)) {
            $this->raiseExternalRefundAlert(
                $providerRefundId ?: 'reversal-' . $refundId,
                (string)$payment['provider_payment_id'],
                (int)$payment['sales_order_id'],
                (int)$refund['amount_cents'],
                (string)($payment['currency'] ?? ''),
                'Automatic capture reversal exhausted retries.',
            );
        }
    }

    /**
     * @param int $refundId Local refund id.
     * @return \App\Service\Payments\ReversalResult
     */
    private function reversalSnapshot(int $refundId): ReversalResult
    {
        $row = $this->fetchTable('PaymentRefunds')->get($refundId);

        return $this->reversalResultFromRow(
            $refundId,
            (string)$row->get('status'),
            (string)$row->get('provider_refund_id') ?: null,
        );
    }

    /**
     * @param int $refundId Local refund id.
     * @param string $status Local refund status.
     * @param string|null $providerRefundId Stripe refund id.
     * @return \App\Service\Payments\ReversalResult
     */
    private function reversalResultFromRow(
        int $refundId,
        string $status,
        ?string $providerRefundId,
    ): ReversalResult {
        $normalized = match ($status) {
            'succeeded', 'completed', 'paid' => 'succeeded',
            'pending' => 'pending',
            default => 'failed',
        };

        return new ReversalResult(
            $normalized,
            $refundId,
            $providerRefundId !== '' ? $providerRefundId : null,
        );
    }

    /**
     * @param string $kind Refund kind.
     * @return bool
     */
    public static function isRevenueKind(string $kind): bool
    {
        return in_array($kind, self::REVENUE_KINDS, true);
    }

    /**
     * @param string $kind Refund kind.
     * @return bool
     */
    public static function isReversalKind(string $kind): bool
    {
        return in_array($kind, self::REVERSAL_KINDS, true);
    }

    /**
     * @param string $providerRefundId Stripe refund id.
     * @param string $paymentIntentId PaymentIntent id.
     * @param int|null $orderId Order id when known.
     * @param int $amountCents Stripe amount.
     * @param string $currency Stripe currency.
     * @param string $reason Alert reason.
     * @return void
     */
    private function raiseExternalRefundAlert(
        string $providerRefundId,
        string $paymentIntentId,
        ?int $orderId,
        int $amountCents,
        string $currency,
        string $reason = 'External Stripe refund requires reconciliation.',
    ): void {
        $this->connection()->execute(
            'INSERT INTO payment_reconciliation_alerts (
                event_id, provider_payment_id, sales_order_id, reason, detail, payload_digest, created
             ) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id',
            [
                'refund:' . $providerRefundId,
                $paymentIntentId !== '' ? $paymentIntentId : $providerRefundId,
                $orderId,
                substr($reason, 0, 64),
                $reason . ' amount=' . $amountCents . ' currency=' . $currency,
                hash('sha256', $providerRefundId . $reason . $amountCents),
                DateTime::now('UTC')->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * @param string $sql Locked read.
     * @param array<int, mixed> $params Bound values.
     * @return array<string, mixed>|null
     */
    private function lockAssoc(string $sql, array $params): ?array
    {
        $statement = $this->connection()->execute($sql, $params);
        $row = $statement->fetch('assoc');

        return is_array($row) ? $row : null;
    }

    /**
     * @param string $sql Locked read.
     * @param array<int, mixed> $params Bound values.
     * @return list<array<string, mixed>>
     */
    private function lockAll(string $sql, array $params): array
    {
        $statement = $this->connection()->execute($sql, $params);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll('assoc');

        return $rows;
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        $connection = $this->fetchTable('Payments')->getConnection();
        if (!$connection instanceof Connection) {
            throw new RuntimeException('Payments require a SQL connection.');
        }

        return $connection;
    }
}
