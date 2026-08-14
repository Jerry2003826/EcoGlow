<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Entity\PaymentRefund;
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
                    $this->applyProviderResult((int)$refund->id, $retrieved->id, $retrieved->status, $actorUserId);
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
            $this->applyProviderResult((int)$refund->id, $result->id, $result->status, $actorUserId);
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
                $this->applyProviderResult((int)$row->id, $result->id, $result->status, 0);
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
     * @param string $providerRefundId Stripe refund id.
     * @param string $status Stripe refund status.
     * @param string $paymentIntentId PaymentIntent id when the local row is missing.
     * @return void
     */
    public function applyWebhookStatus(string $providerRefundId, string $status, string $paymentIntentId = ''): void
    {
        if ($providerRefundId === '') {
            return;
        }
        $this->connection()->transactional(function () use ($providerRefundId, $status, $paymentIntentId): void {
            $refunds = $this->fetchTable('PaymentRefunds');
            $row = $refunds->find()
                ->where(['provider_refund_id' => $providerRefundId])
                ->first();
            if ($row === null && $paymentIntentId !== '') {
                $payment = $this->fetchTable('Payments')->find()
                    ->where(['provider' => 'stripe', 'provider_payment_id' => $paymentIntentId])
                    ->first();
                if ($payment === null) {
                    throw new RuntimeException('Stripe refund does not match a local payment yet.');
                }
                $row = $refunds->find()
                    ->where(['payment_id' => $payment->id, 'status' => 'pending'])
                    ->first();
                if ($row === null) {
                    $row = $this->recordExternalRefund($payment, $providerRefundId);
                }
            }
            if ($row === null) {
                throw new RuntimeException('Stripe refund does not match a local payment yet.');
            }
            $this->applyProviderResult((int)$row->id, $providerRefundId, $status, 0);
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
     * @return void
     */
    private function applyProviderResult(
        int $refundId,
        string $providerRefundId,
        string $status,
        int $actorUserId,
    ): void {
        $refunds = $this->fetchTable('PaymentRefunds');
        $row = $refunds->get($refundId);
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

        $row->set('status', 'succeeded');
        $row->set('completed_at', DateTime::now('UTC'));
        $refunds->saveOrFail($row);

        $payment = $this->fetchTable('Payments')->get($payment->id);
        $payment->status = 'refunded';
        $this->fetchTable('Payments')->saveOrFail($payment);

        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id);
        $order->payment_status = 'refunded';
        $this->fetchTable('SalesOrders')->saveOrFail($order);
        $this->reverseInvoiceCredit($payment, (int)$row->get('amount_cents'));
        $actor = $actorUserId > 0 ? $actorUserId : (int)($order->created_by_user_id ?: 0);
        $this->orders->restockIfUnshipped($order, $actor);
    }

    /**
     * Record a Dashboard / external Stripe refund that has no local pending row.
     *
     * @param \App\Model\Entity\Payment $payment Captured payment.
     * @param string $providerRefundId Stripe refund id.
     * @return \App\Model\Entity\PaymentRefund
     */
    private function recordExternalRefund(Payment $payment, string $providerRefundId): PaymentRefund
    {
        $refunds = $this->fetchTable('PaymentRefunds');
        $row = $refunds->newEmptyEntity();
        $row->set('payment_id', $payment->id);
        $row->set('provider_refund_id', $providerRefundId);
        $row->set('idempotency_key', 'webhook-refund-' . $providerRefundId);
        $row->set('status', 'pending');
        $row->set('amount_cents', (int)$payment->amount_cents);
        $row->set('reason', 'External Stripe refund');
        try {
            $refunds->saveOrFail($row);
        } catch (Throwable $exception) {
            $existing = $refunds->find()
                ->where(['provider_refund_id' => $providerRefundId])
                ->first();
            if ($existing === null) {
                throw $exception;
            }

            return $existing;
        }

        return $row;
    }

    /**
     * Reverse a capture allocation after a succeeded refund.
     *
     * @param \App\Model\Entity\Payment $payment Refunded payment.
     * @param int $amountCents Refunded amount.
     * @return void
     */
    private function reverseInvoiceCredit(Payment $payment, int $amountCents): void
    {
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $payment->sales_order_id, 'status !=' => Invoice::STATUS_VOID])
            ->first();
        if ($invoice === null || $amountCents <= 0) {
            return;
        }
        $this->connection()->execute('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoice->id]);
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $allocations = $this->fetchTable('PaymentAllocations');
        $existing = $allocations->find()
            ->where([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'allocation_type' => 'refund',
            ])
            ->first();
        if ($existing !== null) {
            return;
        }
        $row = $allocations->newEmptyEntity();
        $row->set('payment_id', $payment->id);
        $row->set('invoice_id', $invoice->id);
        $row->set('allocation_type', 'refund');
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
