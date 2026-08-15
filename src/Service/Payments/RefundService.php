<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Orders\OrderService;
use Cake\Database\Connection;
use Cake\Database\Exception\QueryException;
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
                'SELECT id, status, provider_refund_id, idempotency_key, amount_cents
                   FROM payment_refunds WHERE payment_id = ? FOR UPDATE',
                [(int)$paymentRow['id']],
            );
            foreach ($siblings as $sibling) {
                if (in_array((string)$sibling['status'], ['succeeded', 'completed'], true)) {
                    throw new InvalidArgumentException('A refund has already been recorded for this payment.');
                }
            }
            foreach ($siblings as $sibling) {
                if ((string)$sibling['status'] !== 'pending') {
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
            'SELECT id, amount_paid_cents, grand_total_cents, status
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
            $this->connection()->execute(
                "UPDATE payment_refunds
                    SET status = 'failed', provider_refund_id = ?
                  WHERE id = ?",
                [$bindId !== '' ? $bindId : null, $refundId],
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
        if ($order !== null && (string)$order['payment_status'] !== 'refunded') {
            $this->connection()->execute(
                'UPDATE sales_orders SET payment_status = ? WHERE id = ?',
                [$nextStatus, (int)$order['id']],
            );
        }

        $this->reverseInvoiceCreditFromLock(
            (int)$payment['id'],
            $invoice,
            $applyAmount,
            $refundId,
        );

        $staffInitiated = $actorUserId > 0 || (string)$refund['reason'] === 'Staff refund';
        if ($staffInitiated && $nextStatus === 'refunded' && $order !== null) {
            $actor = $actorUserId > 0 ? $actorUserId : (int)($order['created_by_user_id'] ?: 0);
            /** @var \App\Model\Entity\SalesOrder $orderEntity */
            $orderEntity = $this->fetchTable('SalesOrders')->newEmptyEntity();
            $orderEntity->id = (int)$order['id'];
            $orderEntity->status = (string)$order['status'];
            $orderEntity->order_number = (string)$order['order_number'];
            $orderEntity->setNew(false);
            $this->orders->restockIfUnshipped($orderEntity, $actor);
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

        $paid = max(0, (int)$invoice['amount_paid_cents'] - $amountCents);
        $status = (string)$invoice['status'];
        if (
            $paid < (int)$invoice['grand_total_cents']
            && in_array($status, [Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE], true)
        ) {
            $status = Invoice::STATUS_ISSUED;
            $this->connection()->execute(
                'UPDATE invoices
                    SET amount_paid_cents = ?, status = ?, paid_at = NULL
                  WHERE id = ?',
                [$paid, $status, (int)$invoice['id']],
            );

            return;
        }
        $this->connection()->execute(
            'UPDATE invoices SET amount_paid_cents = ? WHERE id = ?',
            [$paid, (int)$invoice['id']],
        );
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
