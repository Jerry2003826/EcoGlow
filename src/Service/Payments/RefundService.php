<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Orders\OrderService;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
            $payment = $this->capturedStripePayment($order);
            if ($payment === null) {
                throw new InvalidArgumentException('This order has no captured Stripe payment to refund.');
            }
            $this->connection()->execute('SELECT id FROM payments WHERE id = ? FOR UPDATE', [$payment->id]);
            $payment = $this->fetchTable('Payments')->get($payment->id);
            if ((string)$payment->status !== 'captured') {
                throw new InvalidArgumentException('This order has no captured Stripe payment to refund.');
            }
            $already = $this->fetchTable('PaymentRefunds')->find()
                ->where(['payment_id' => $payment->id, 'status IN' => ['pending', 'succeeded', 'completed']])
                ->first();
            $key = 'refund-payment-' . $payment->id;
            if ($already) {
                if (in_array((string)$already->get('status'), ['succeeded', 'completed'], true)) {
                    throw new InvalidArgumentException('A refund has already been recorded for this payment.');
                }
                if ((string)($already->get('provider_refund_id') ?? '') !== '') {
                    return [
                        'payment' => $payment,
                        'refund' => $already,
                        'key' => $key,
                        'retry' => false,
                    ];
                }

                return [
                    'payment' => $payment,
                    'refund' => $already,
                    'key' => (string)($already->get('idempotency_key') ?: $key),
                    'retry' => true,
                ];
            }
            $refunds = $this->fetchTable('PaymentRefunds');
            $row = $refunds->newEmptyEntity();
            $row->set('payment_id', $payment->id);
            $row->set('idempotency_key', $key);
            $row->set('status', 'pending');
            $row->set('amount_cents', (int)$payment->amount_cents);
            $row->set('reason', 'Staff refund');
            $row->set('requested_by_user_id', $actorUserId);
            $refunds->saveOrFail($row);

            return [
                'payment' => $payment,
                'refund' => $row,
                'key' => $key,
                'retry' => true,
            ];
        });

        /** @var \App\Model\Entity\Payment $payment */
        $payment = $prepared['payment'];
        /** @var \App\Model\Entity\PaymentRefund $refund */
        $refund = $prepared['refund'];

        if (!(bool)$prepared['retry']) {
            $providerId = (string)($refund->get('provider_refund_id') ?? '');
            $retrieved = $this->gateway->retrieveRefund($providerId);
            if ($retrieved !== null) {
                $this->connection()->transactional(function () use ($refund, $retrieved, $actorUserId): void {
                    $this->applyProviderResult(
                        (int)$refund->id,
                        $retrieved->id,
                        $retrieved->status,
                        $actorUserId,
                        $retrieved->amountCents,
                        $retrieved->currency,
                    );
                });
            }

            return $this->fetchTable('Payments')->get($payment->id);
        }

        $result = $this->gateway->refund(
            (string)$payment->provider_payment_id,
            (int)$payment->amount_cents,
            (string)$prepared['key'],
        );

        $this->connection()->transactional(function () use ($refund, $result, $actorUserId): void {
            $this->applyProviderResult(
                (int)$refund->id,
                $result->id,
                $result->status,
                $actorUserId,
                $result->amountCents,
                $result->currency,
            );
        });

        return $this->fetchTable('Payments')->get($payment->id);
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
            $this->connection()->transactional(function () use ($row, $result): void {
                $this->applyProviderResult(
                    (int)$row->id,
                    $result->id,
                    $result->status,
                    (int)($row->get('requested_by_user_id') ?: 0),
                    $result->amountCents,
                    $result->currency,
                );
            });
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
     * @return void
     */
    public function applyWebhookStatus(
        string $providerRefundId,
        string $status,
        string $paymentIntentId = '',
        int $amountCents = 0,
        string $currency = '',
    ): void {
        if ($providerRefundId === '') {
            return;
        }
        $this->connection()->transactional(function () use (
            $providerRefundId,
            $status,
            $paymentIntentId,
            $amountCents,
            $currency,
        ): void {
            $refunds = $this->fetchTable('PaymentRefunds');
            $row = $refunds->find()
                ->where(['provider_refund_id' => $providerRefundId])
                ->first();
            /** @var \App\Model\Entity\Payment|null $payment */
            $payment = null;
            if ($paymentIntentId !== '') {
                $payment = $this->fetchTable('Payments')->find()
                    ->where(['provider' => 'stripe', 'provider_payment_id' => $paymentIntentId])
                    ->first();
            }
            if ($row === null && $payment !== null) {
                $row = $refunds->find()
                    ->where(['payment_id' => $payment->id, 'status' => 'pending'])
                    ->first();
            }
            if ($row === null) {
                if ($payment === null && $paymentIntentId !== '') {
                    throw new RuntimeException('Stripe refund does not match a local payment yet.');
                }
                $this->raiseExternalRefundAlert(
                    $providerRefundId,
                    $paymentIntentId,
                    $payment !== null ? (int)$payment->sales_order_id : null,
                    $amountCents,
                    $currency,
                );

                return;
            }
            $this->applyProviderResult(
                (int)$row->id,
                $providerRefundId,
                $status,
                (int)($row->get('requested_by_user_id') ?: 0),
                $amountCents,
                $currency,
            );
        });
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
        $refunds = $this->fetchTable('PaymentRefunds');
        $row = $refunds->get($refundId);
        /** @var \App\Model\Entity\Payment $payment */
        $payment = $this->fetchTable('Payments')->get((int)$row->get('payment_id'));
        $this->orders->lockOrder((int)$payment->sales_order_id);
        $this->connection()->execute('SELECT id FROM payments WHERE id = ? FOR UPDATE', [$payment->id]);
        $this->connection()->execute('SELECT id FROM payment_refunds WHERE id = ? FOR UPDATE', [$refundId]);
        $row = $refunds->get($refundId);
        if (in_array((string)$row->get('status'), ['succeeded', 'completed'], true)) {
            return;
        }

        $normalized = strtolower($status);
        $row->set('provider_refund_id', $providerRefundId !== '' ? $providerRefundId : $row->get('provider_refund_id'));
        $row->set('provider_metadata', ['refund' => $providerRefundId, 'status' => $normalized]);
        if ($amountCents > 0) {
            $row->set('amount_cents', $amountCents);
        }
        if ($currency !== '') {
            $expected = strtolower((string)($payment->currency ?: 'AUD'));
            if (strtolower($currency) !== $expected) {
                $this->raiseExternalRefundAlert(
                    $providerRefundId,
                    (string)$payment->provider_payment_id,
                    (int)$payment->sales_order_id,
                    $amountCents,
                    $currency,
                    'Refund currency does not match the captured payment.',
                );
                $refunds->saveOrFail($row);

                return;
            }
        }

        if (in_array($normalized, ['failed', 'canceled', 'cancelled'], true)) {
            $row->set('status', 'failed');
            $refunds->saveOrFail($row);

            return;
        }

        if (!in_array($normalized, ['succeeded', 'paid'], true)) {
            $row->set('status', 'pending');
            $refunds->saveOrFail($row);

            return;
        }

        $applyAmount = $amountCents > 0 ? $amountCents : (int)$row->get('amount_cents');
        $refundedTotal = $this->succeededRefundTotal((int)$payment->id);
        $captured = (int)$payment->amount_cents;
        if ($applyAmount <= 0 || $refundedTotal + $applyAmount > $captured) {
            $this->raiseExternalRefundAlert(
                $providerRefundId,
                (string)$payment->provider_payment_id,
                (int)$payment->sales_order_id,
                $amountCents,
                $currency,
                'Succeeded refunds exceed the captured amount.',
            );
            $refunds->saveOrFail($row);

            return;
        }

        $row->set('status', 'succeeded');
        $row->set('completed_at', DateTime::now('UTC'));
        $refunds->saveOrFail($row);

        /** @var \App\Model\Entity\Payment $payment */
        $payment = $this->fetchTable('Payments')->get($payment->id);
        $refundedTotal = $this->succeededRefundTotal((int)$payment->id);
        $nextStatus = $refundedTotal >= $captured ? 'refunded' : 'partially_refunded';
        if (!in_array((string)$payment->status, ['refunded'], true)) {
            $payment->status = $nextStatus;
            $this->fetchTable('Payments')->saveOrFail($payment);
        }

        /** @var \App\Model\Entity\SalesOrder $order */
        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id);
        if ((string)$order->payment_status !== 'refunded') {
            $order->payment_status = $nextStatus;
            $this->fetchTable('SalesOrders')->saveOrFail($order);
        }
        $this->reverseInvoiceCredit($payment, (int)$row->get('amount_cents'), (int)$row->id);
        $staffInitiated = $actorUserId > 0 || (string)$row->get('reason') === 'Staff refund';
        if ($staffInitiated && $nextStatus === 'refunded') {
            $actor = $actorUserId > 0 ? $actorUserId : (int)($order->created_by_user_id ?: 0);
            $this->orders->restockIfUnshipped($order, $actor);
        }
    }

    /**
     * @param int $paymentId Payment id.
     * @return int
     */
    private function succeededRefundTotal(int $paymentId): int
    {
        $total = 0;
        $rows = $this->fetchTable('PaymentRefunds')->find()
            ->where(['payment_id' => $paymentId, 'status IN' => ['succeeded', 'completed']])
            ->all();
        foreach ($rows as $row) {
            $total += (int)$row->get('amount_cents');
        }

        return $total;
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
        $alerts = $this->fetchTable('PaymentReconciliationAlerts');
        $row = $alerts->newEmptyEntity();
        $row->set('event_id', 'refund:' . $providerRefundId);
        $row->set('provider_payment_id', $paymentIntentId !== '' ? $paymentIntentId : $providerRefundId);
        $row->set('sales_order_id', $orderId);
        $row->set('reason', substr($reason, 0, 64));
        $row->set('detail', $reason . ' amount=' . $amountCents . ' currency=' . $currency);
        $row->set('payload_digest', hash('sha256', $providerRefundId . $reason . $amountCents));
        $row->set('created', DateTime::now('UTC'));
        try {
            $alerts->saveOrFail($row);
        } catch (Throwable) {
            // Duplicate alert for the same refund is acceptable.
        }
    }

    /**
     * Reverse a capture allocation after a succeeded refund.
     *
     * @param \App\Model\Entity\Payment $payment Refunded payment.
     * @param int $amountCents Refunded amount.
     * @param int $refundId Local refund id.
     * @return void
     */
    private function reverseInvoiceCredit(Payment $payment, int $amountCents, int $refundId = 0): void
    {
        /** @var \App\Model\Entity\Invoice|null $invoice */
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $payment->sales_order_id, 'status !=' => Invoice::STATUS_VOID])
            ->first();
        if ($invoice === null || $amountCents <= 0) {
            return;
        }
        $this->connection()->execute('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoice->id]);
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $allocations = $this->fetchTable('PaymentAllocations');
        $existingQuery = [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocation_type' => 'refund',
        ];
        if ($refundId > 0 && $allocations->getSchema()->hasColumn('payment_refund_id')) {
            $existingQuery['payment_refund_id'] = $refundId;
        }
        $existing = $allocations->find()->where($existingQuery)->first();
        if ($existing !== null) {
            return;
        }
        $row = $allocations->newEmptyEntity();
        $row->set('payment_id', $payment->id);
        $row->set('invoice_id', $invoice->id);
        $row->set('allocation_type', 'refund');
        if ($allocations->getSchema()->hasColumn('payment_refund_id') && $refundId > 0) {
            $row->set('payment_refund_id', $refundId);
        }
        if ($allocations->getSchema()->hasColumn('effect_key')) {
            $row->set('effect_key', $refundId > 0 ? 'refund-' . $refundId : 'refund');
        }
        $row->set('amount_cents', $amountCents);
        $row->set('allocated_at', DateTime::now('UTC'));
        $row->set('created', DateTime::now('UTC'));
        try {
            $allocations->saveOrFail($row);
        } catch (Throwable $exception) {
            if (
                str_contains($exception->getMessage(), 'UNIQUE')
                || str_contains($exception->getMessage(), 'Duplicate')
                || str_contains($exception->getMessage(), '1062')
            ) {
                return;
            }
            throw $exception;
        }
        $invoice->amount_paid_cents = max(0, (int)$invoice->amount_paid_cents - $amountCents);
        if (
            (int)$invoice->amount_paid_cents < (int)$invoice->grand_total_cents
            && in_array((string)$invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE], true)
        ) {
            $invoice->status = Invoice::STATUS_ISSUED;
            $invoice->paid_at = null;
        }
        $this->fetchTable('Invoices')->saveOrFail($invoice);
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Payments')->getConnection();
    }
}
