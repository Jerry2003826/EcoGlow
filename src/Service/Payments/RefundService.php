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
        $payment = $this->capturedStripePayment($order);
        if ($payment === null) {
            throw new InvalidArgumentException('This order has no captured Stripe payment to refund.');
        }
        $already = $this->fetchTable('PaymentRefunds')->find()
            ->where(['payment_id' => $payment->id, 'status IN' => ['pending', 'succeeded', 'completed']])
            ->first();
        if ($already) {
            throw new InvalidArgumentException('A refund has already been recorded for this payment.');
        }

        $key = 'refund-payment-' . $payment->id;
        $result = $this->gateway->refund(
            (string)$payment->provider_payment_id,
            (int)$payment->amount_cents,
            $key,
        );

        return $this->connection()->transactional(function () use ($order, $payment, $result, $actorUserId, $key) {
            $refunds = $this->fetchTable('PaymentRefunds');
            $row = $refunds->newEmptyEntity();
            $row->set('payment_id', $payment->id);
            $row->set('provider_refund_id', $result->id);
            $row->set('idempotency_key', $key);
            $row->set('status', $result->status === 'failed' ? 'failed' : 'succeeded');
            $row->set('amount_cents', (int)$payment->amount_cents);
            $row->set('reason', 'Staff refund');
            $row->set('provider_metadata', ['refund' => $result->id]);
            $row->set('requested_by_user_id', $actorUserId);
            $row->set('completed_at', DateTime::now('UTC'));
            $refunds->saveOrFail($row);

            $payment->status = 'refunded';
            $this->fetchTable('Payments')->saveOrFail($payment);

            $order->payment_status = 'refunded';
            $this->fetchTable('SalesOrders')->saveOrFail($order);
            $this->orders->restockIfUnshipped($order, $actorUserId);

            return $payment;
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
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Payments')->getConnection();
    }
}
