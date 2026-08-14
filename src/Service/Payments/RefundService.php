<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Orders\OrderService;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

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
            if ($already) {
                throw new InvalidArgumentException('A refund has already been recorded for this payment.');
            }

            $key = 'refund-payment-' . $payment->id;
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
            ];
        });

        /** @var \App\Model\Entity\Payment $payment */
        $payment = $prepared['payment'];
        /** @var \App\Model\Entity\PaymentRefund $refund */
        $refund = $prepared['refund'];

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
                if ($payment !== null) {
                    $row = $refunds->find()
                        ->where(['payment_id' => $payment->id, 'status' => 'pending'])
                        ->first();
                }
            }
            if ($row === null) {
                return;
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

        $payment = $this->fetchTable('Payments')->get((int)$row->get('payment_id'));
        $this->connection()->execute('SELECT id FROM payments WHERE id = ? FOR UPDATE', [$payment->id]);
        $payment = $this->fetchTable('Payments')->get($payment->id);
        $payment->status = 'refunded';
        $this->fetchTable('Payments')->saveOrFail($payment);

        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id);
        $this->orders->lockOrder((int)$order->id);
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $order->payment_status = 'refunded';
        $this->fetchTable('SalesOrders')->saveOrFail($order);
        $actor = $actorUserId > 0 ? $actorUserId : (int)($order->created_by_user_id ?: 0);
        $this->orders->restockIfUnshipped($order, $actor);
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Payments')->getConnection();
    }
}
