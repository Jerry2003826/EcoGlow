<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Invoice;
use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\OutboundQueue;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use RuntimeException;
use Stripe\Event;
use Throwable;

/**
 * Applies verified Stripe events. Order status never follows the browser.
 */
class StripeWebhookService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Service\Orders\OrderService $orders Order mutations.
     * @param \App\Service\Payments\IdempotencyService $idempotency Event-id lock.
     * @param \App\Service\OutboundQueue $queue Confirmation mail queue.
     */
    public function __construct(
        private OrderService $orders,
        private IdempotencyService $idempotency,
        private OutboundQueue $queue,
    ) {
    }

    /**
     * @param \Stripe\Event $event Verified Stripe event.
     * @return array{status: int, body: array<string, mixed>}
     */
    public function handle(Event $event): array
    {
        $eventId = (string)$event->id;
        $claim = $this->idempotency->claimStatus(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId);
        if ($claim['status'] === IdempotencyService::COMPLETED) {
            return ['status' => 200, 'body' => ['received' => true, 'duplicate' => true]];
        }
        if ($claim['status'] === IdempotencyService::IN_FLIGHT) {
            return ['status' => 500, 'body' => ['error' => 'retry']];
        }
        $owner = $claim['owner'];

        $type = (string)$event->type;
        try {
            if ($type === 'payment_intent.succeeded') {
                return $this->finish($eventId, $this->onSucceeded($event), $owner);
            }
            if ($type === 'payment_intent.payment_failed') {
                $this->onPaymentFailed($event);

                return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]], $owner);
            }
            if ($type === 'payment_intent.canceled') {
                $this->onCanceled($event);

                return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]], $owner);
            }
            if (
                $type === 'refund.updated'
                || $type === 'refund.failed'
                || $type === 'refund.canceled'
                || $type === 'refund.created'
                || $type === 'charge.refunded'
            ) {
                $this->onRefund($event);

                return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]], $owner);
            }
            Log::info('Ignoring Stripe event type: ' . $type);

            return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]], $owner);
        } catch (RuntimeException $exception) {
            $this->idempotency->release(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId, $owner);
            Log::error('Stripe webhook transient failure: ' . $exception->getMessage());

            return ['status' => 500, 'body' => ['error' => 'retry']];
        } catch (InvalidArgumentException $exception) {
            $alert = $this->alert($event, $exception->getMessage());
            $result = [
                'status' => 200,
                'body' => [
                    'received' => true,
                    'conflict' => true,
                    'alert_id' => $alert,
                ],
            ];
            $this->idempotency->complete(
                IdempotencyService::SCOPE_STRIPE_WEBHOOK,
                $eventId,
                200,
                $result['body'],
                $owner,
            );
            Log::error('Stripe webhook conflict [' . $alert . ']: ' . $exception->getMessage());

            return $result;
        }
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return array{status: int, body: array<string, mixed>}
     */
    private function onSucceeded(Event $event): array
    {
        $intent = $event->data->object;
        $this->assertIntentSucceeded($event, $intent);
        $payment = $this->boundPendingPayment($intent);
        if (in_array((string)$payment->status, ['partially_refunded', 'refunded'], true)) {
            return ['status' => 200, 'body' => ['received' => true, 'duplicate' => true]];
        }
        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id, contain: [
            'Customers',
            'OrderAddresses',
        ]);
        $this->assertAmountAndCurrency($intent, $payment, $order);

        $actorId = (int)($order->created_by_user_id ?: 0);
        $amount = $this->capturedAmount($intent);
        $action = 'duplicate';
        $this->connection()->transactional(function () use (
            $order,
            $payment,
            $amount,
            $actorId,
            $intent,
            &$action,
        ): void {
            $this->orders->lockOrder((int)$order->id);
            $order = $this->fetchTable('SalesOrders')->get((int)$order->get('id'));
            $this->lockPayment((int)$payment->id);
            $payment = $this->fetchTable('Payments')->get((int)$payment->id);
            if (in_array((string)$payment->status, ['partially_refunded', 'refunded'], true)) {
                $action = 'duplicate';

                return;
            }
            $unexpected = $this->isUnexpectedStripeCapture($order, $payment);
            if ((string)$payment->status === 'captured') {
                $action = $unexpected ? 'refund' : 'duplicate';

                return;
            }
            $firstCapture = $this->markCaptured($payment, $amount, $intent);
            if (!$firstCapture) {
                $action = $unexpected ? 'refund' : 'duplicate';

                return;
            }
            if ($unexpected) {
                $action = 'refund';

                return;
            }
            $this->orders->confirmPaid($order, $actorId > 0 ? $actorId : (int)$order->customer_id);
            $this->creditInvoiceIfPresent($order, $amount, $payment);
            $action = 'confirm';
        });

        if ($action === 'refund') {
            $reversal = $this->refundUnexpectedCaptureOrRetry($payment, $order, $event);

            return [
                'status' => 200,
                'body' => [
                    'received' => true,
                    'refunded' => $reversal->status === 'succeeded',
                    'refund_status' => $reversal->status,
                ],
            ];
        }
        if ($action === 'confirm') {
            try {
                $this->queueConfirmation($order);
            } catch (Throwable $exception) {
                Log::error('Order confirmation email could not be queued: ' . $exception->getMessage());
            }
        }

        return ['status' => 200, 'body' => ['received' => true]];
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return void
     */
    private function onPaymentFailed(Event $event): void
    {
        $intent = $event->data->object;
        $payment = $this->boundPendingPayment($intent, false);
        $this->connection()->transactional(function () use ($payment, $intent): void {
            $this->lockPayment((int)$payment->id);
            $this->markFailedIfPending((int)$payment->id, $this->failureMessage($intent));
        });
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return void
     */
    private function onCanceled(Event $event): void
    {
        $intent = $event->data->object;
        $payment = $this->boundPendingPayment($intent, false);
        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id);
        $actorId = (int)($order->created_by_user_id ?: 0);
        $this->connection()->transactional(function () use ($order, $payment, $actorId, $intent): void {
            $this->orders->lockOrder((int)$order->id);
            $this->lockPayment((int)$payment->id);
            $this->markFailedIfPending((int)$payment->id, $this->failureMessage($intent));
            $this->orders->failUnpaid(
                $order,
                $actorId > 0 ? $actorId : (int)$order->customer_id,
                'Stripe payment_intent.canceled',
            );
        });
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return void
     */
    private function onRefund(Event $event): void
    {
        $object = $event->data->object;
        $intentId = (string)($object->payment_intent ?? '');
        if ($event->type === 'charge.refunded') {
            $items = $object->refunds->data ?? [];
            if (!is_iterable($items)) {
                return;
            }
            foreach ($items as $item) {
                if (is_object($item)) {
                    $this->applyStripeRefundObject($item, $intentId);
                }
            }

            return;
        }
        $status = (string)($object->status ?? '');
        if ($event->type === 'refund.failed') {
            $status = 'failed';
        } elseif ($event->type === 'refund.canceled') {
            $status = 'canceled';
        }
        $this->refunds()->applyWebhookStatus(
            (string)($object->id ?? ''),
            $status !== '' ? $status : 'pending',
            $intentId,
            (int)($object->amount ?? 0),
            strtolower((string)($object->currency ?? '')),
            $this->refundMetadata($object),
        );
    }

    /**
     * @param \Stripe\Event $event Event.
     * @param object $intent PaymentIntent.
     * @return void
     */
    private function assertIntentSucceeded(Event $event, object $intent): void
    {
        if ((string)($intent->status ?? '') !== 'succeeded') {
            throw new InvalidArgumentException('PaymentIntent status is not succeeded.');
        }
        $expectedLive = str_starts_with((string)Configure::read('Stripe.secretKey'), 'sk_live_');
        if ((bool)$event->livemode !== $expectedLive) {
            throw new InvalidArgumentException('Stripe livemode does not match this deployment.');
        }
    }

    /**
     * @param object $intent PaymentIntent.
     * @param bool $requirePending Whether a missing row is a retryable race.
     * @return \App\Model\Entity\Payment
     */
    private function boundPendingPayment(object $intent, bool $requirePending = true): Payment
    {
        $intentId = (string)($intent->id ?? '');
        if ($intentId === '') {
            throw new InvalidArgumentException('PaymentIntent id is missing.');
        }
        $payment = $this->fetchTable('Payments')->find()
            ->where(['provider' => 'stripe', 'provider_payment_id' => $intentId])
            ->first();
        if ($payment === null) {
            throw new RuntimeException('Pending Stripe payment is not recorded yet.');
        }
        $orderId = $this->metadataOrderId($intent);
        if ($orderId > 0 && (int)$payment->sales_order_id !== $orderId) {
            throw new InvalidArgumentException('PaymentIntent order_id does not match the stored payment.');
        }
        if ($requirePending && !in_array((string)$payment->status, ['pending', 'failed'], true)) {
            if (in_array((string)$payment->status, ['captured', 'partially_refunded', 'refunded'], true)) {
                return $payment;
            }
            throw new InvalidArgumentException('Stored payment is not awaiting capture.');
        }

        return $payment;
    }

    /**
     * @param object $intent PaymentIntent.
     * @param \App\Model\Entity\Payment $payment Stored payment.
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return void
     */
    private function assertAmountAndCurrency(object $intent, Payment $payment, SalesOrder $order): void
    {
        $amount = $this->capturedAmount($intent);
        if ($amount !== (int)$payment->amount_cents || $amount !== (int)$order->grand_total_cents) {
            throw new InvalidArgumentException('Captured amount does not match the order.');
        }
        $currency = strtolower((string)($intent->currency ?? ''));
        $expected = strtolower((string)($payment->currency ?: $order->currency ?: 'AUD'));
        if ($currency !== $expected) {
            throw new InvalidArgumentException('Captured currency does not match the order.');
        }
    }

    /**
     * @param object $intent PaymentIntent.
     * @return int
     */
    private function capturedAmount(object $intent): int
    {
        $received = (int)($intent->amount_received ?? 0);

        return $received > 0 ? $received : (int)($intent->amount ?? 0);
    }

    /**
     * @param object $intent PaymentIntent.
     * @return int
     */
    private function metadataOrderId(object $intent): int
    {
        $metadata = $intent->metadata ?? null;
        if (is_array($metadata) || is_object($metadata)) {
            return (int)($metadata['order_id'] ?? 0);
        }

        return 0;
    }

    /**
     * @param \App\Model\Entity\Payment $payment Payment.
     * @param int $amountCents Captured amount.
     * @param object $intent Stripe object.
     * @return bool True when this call flipped pending/failed to captured.
     */
    private function markCaptured(Payment $payment, int $amountCents, object $intent): bool
    {
        if (in_array((string)$payment->status, ['captured', 'partially_refunded', 'refunded'], true)) {
            return false;
        }
        if (!$this->recordCaptureEffect((string)$intent->id)) {
            return false;
        }
        $payment->status = 'captured';
        $payment->amount_cents = $amountCents;
        $payment->captured_at = DateTime::now('UTC');
        $payment->authorised_at = DateTime::now('UTC');
        $payment->provider_metadata = ['payment_intent' => (string)$intent->id];
        $this->fetchTable('Payments')->saveOrFail($payment);

        return true;
    }

    /**
     * @param string $providerPaymentId Stripe PaymentIntent id.
     * @return bool False when this capture effect already exists.
     */
    private function recordCaptureEffect(string $providerPaymentId): bool
    {
        if ($providerPaymentId === '') {
            return false;
        }

        return $this->connection()->execute(
            'INSERT INTO payment_effects (provider, provider_payment_id, effect_type, created)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id',
            ['stripe', $providerPaymentId, 'capture', DateTime::now('UTC')->format('Y-m-d H:i:s')],
        )->rowCount() === 1;
    }

    /**
     * @param int $paymentId Payment id.
     * @return void
     */
    private function lockPayment(int $paymentId): void
    {
        $this->connection()->execute('SELECT id FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
    }

    /**
     * Failed/canceled events may not move captured or refunded payments backwards.
     *
     * @param int $paymentId Payment id.
     * @param string $reason Failure reason.
     * @return void
     */
    private function markFailedIfPending(int $paymentId, string $reason): void
    {
        $this->connection()->execute(
            "UPDATE payments
                SET status = 'failed', failed_at = ?, failure_reason = ?
              WHERE id = ?
                AND status IN ('pending', 'failed')",
            [DateTime::now('UTC')->format('Y-m-d H:i:s'), $reason, $paymentId],
        );
    }

    /**
     * @param object $refund Stripe refund object.
     * @param string $intentId PaymentIntent id.
     * @return void
     */
    private function applyStripeRefundObject(object $refund, string $intentId): void
    {
        $intent = $intentId !== '' ? $intentId : (string)($refund->payment_intent ?? '');
        $this->refunds()->applyWebhookStatus(
            (string)($refund->id ?? ''),
            (string)($refund->status ?? 'pending'),
            $intent,
            (int)($refund->amount ?? 0),
            strtolower((string)($refund->currency ?? '')),
            $this->refundMetadata($refund),
        );
    }

    /**
     * Stripe captured money that the local order can no longer accept.
     *
     * @param \Cake\Datasource\EntityInterface $order Locked order.
     * @param \Cake\Datasource\EntityInterface $payment Locked Stripe payment.
     * @return bool
     */
    private function isUnexpectedStripeCapture(EntityInterface $order, EntityInterface $payment): bool
    {
        if ((string)$order->get('status') === SalesOrder::STATUS_CANCELLED) {
            return true;
        }
        if ((string)$order->get('payment_status') === 'paid') {
            return $this->hasOtherSettledPayment((int)$order->get('id'), (int)$payment->get('id'));
        }
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $order->get('id'), 'status !=' => Invoice::STATUS_VOID])
            ->first();
        if ($invoice === null) {
            return false;
        }
        if (!in_array((string)$invoice->get('status'), [Invoice::STATUS_PAID, Invoice::STATUS_CREDITED], true)) {
            return false;
        }

        return !$this->hasCaptureAllocation((int)$invoice->id, (int)$payment->get('id'));
    }

    /**
     * @param int $orderId Order id.
     * @param int $paymentId Current Stripe payment.
     * @return bool
     */
    private function hasOtherSettledPayment(int $orderId, int $paymentId): bool
    {
        return $this->fetchTable('Payments')->exists([
            'sales_order_id' => $orderId,
            'id !=' => $paymentId,
            'status IN' => ['captured', 'partially_refunded', 'refunded'],
        ]);
    }

    /**
     * @param int $invoiceId Invoice id.
     * @param int $paymentId Payment id.
     * @return bool
     */
    private function hasCaptureAllocation(int $invoiceId, int $paymentId): bool
    {
        return $this->fetchTable('PaymentAllocations')->exists([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'allocation_type' => 'capture',
        ]);
    }

    /**
     * Refund a Stripe capture that would double-settle or pay a cancelled order.
     *
     * @param \Cake\Datasource\EntityInterface $payment Stripe payment.
     * @param \Cake\Datasource\EntityInterface $order Order.
     * @param \Stripe\Event $event Source event.
     * @return \App\Service\Payments\ReversalResult
     */
    private function refundUnexpectedCaptureOrRetry(
        EntityInterface $payment,
        EntityInterface $order,
        Event $event,
    ): ReversalResult {
        $kind = (string)$order->get('status') === SalesOrder::STATUS_CANCELLED
            ? RefundService::KIND_CANCELLED_ORDER_REVERSAL
            : RefundService::KIND_DUPLICATE_CAPTURE_REVERSAL;
        $result = $this->refunds()->reverseUnexpectedCapture((int)$payment->get('id'), $kind);
        $detail = match ($result->status) {
            'succeeded' => 'Unexpected Stripe capture was automatically refunded for order ',
            'pending' => 'Unexpected Stripe capture reversal is pending for order ',
            default => 'Unexpected Stripe capture reversal failed for order ',
        };
        $this->alert($event, $detail . (string)$order->get('order_number') . '.');

        return $result;
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Paid order.
     * @param int $amountCents Captured amount.
     * @param \App\Model\Entity\Payment $payment Captured payment.
     * @return void
     */
    private function creditInvoiceIfPresent(SalesOrder $order, int $amountCents, Payment $payment): void
    {
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $order->id, 'status !=' => 'void'])
            ->first();
        if ($invoice === null) {
            return;
        }
        $status = (string)$invoice->get('status');
        if (in_array($status, [Invoice::STATUS_PAID, Invoice::STATUS_CREDITED], true)) {
            return;
        }
        $paid = (int)$invoice->get('amount_paid_cents');
        $grand = (int)$invoice->get('grand_total_cents');
        if ($grand > 0 && $paid + $amountCents > $grand) {
            return;
        }
        $now = DateTime::now('UTC')->format('Y-m-d H:i:s');
        $inserted = $this->connection()->execute(
            'INSERT INTO payment_allocations (
                payment_id, invoice_id, allocation_type, effect_key, amount_cents, allocated_at, created
             ) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id',
            [(int)$payment->id, (int)$invoice->id, 'capture', 'capture', $amountCents, $now, $now],
        )->rowCount() === 1;
        if (!$inserted) {
            return;
        }
        $this->connection()->execute('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoice->id]);
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        if (in_array((string)$invoice->get('status'), [Invoice::STATUS_PAID, Invoice::STATUS_CREDITED], true)) {
            return;
        }
        $invoice->amount_paid_cents = (int)$invoice->amount_paid_cents + $amountCents;
        if ((int)$invoice->amount_paid_cents >= (int)$invoice->grand_total_cents) {
            $invoice->set('status', 'paid');
            $invoice->paid_at = DateTime::now('UTC');
        }
        $this->fetchTable('Invoices')->saveOrFail($invoice);
    }

    /**
     * @param \Stripe\Event $event Event.
     * @param string $reason Conflict reason.
     * @return int Alert id.
     */
    private function alert(Event $event, string $reason): int
    {
        $intent = $event->data->object ?? null;
        $eventId = (string)$event->id;
        $this->connection()->execute(
            'INSERT INTO payment_reconciliation_alerts (
                event_id, provider_payment_id, sales_order_id, reason, detail, payload_digest, created
             ) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = id',
            [
                $eventId,
                is_object($intent) ? (string)($intent->id ?? '') : null,
                is_object($intent) ? $this->metadataOrderId($intent) : null,
                substr($reason, 0, 64),
                $reason,
                hash('sha256', $eventId . $reason),
                DateTime::now('UTC')->format('Y-m-d H:i:s'),
            ],
        );
        $saved = $this->connection()->execute(
            'SELECT id FROM payment_reconciliation_alerts WHERE event_id = ?',
            [$eventId],
        )->fetch('assoc');

        return is_array($saved) ? (int)$saved['id'] : 0;
    }

    /**
     * @param string $eventId Event id.
     * @param array{status: int, body: array<string, mixed>} $result Result.
     * @param string $owner Lease owner.
     * @return array{status: int, body: array<string, mixed>}
     */
    private function finish(string $eventId, array $result, string $owner = ''): array
    {
        $this->idempotency->complete(
            IdempotencyService::SCOPE_STRIPE_WEBHOOK,
            $eventId,
            $result['status'],
            $result['body'],
            $owner,
        );

        return $result;
    }

    /**
     * @return \App\Service\Payments\RefundService
     */
    private function refunds(): RefundService
    {
        return new RefundService($this->orders, PaymentGatewayFactory::create());
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return void
     */
    private function queueConfirmation(SalesOrder $order): void
    {
        $order = $this->fetchTable('SalesOrders')->get($order->id, contain: [
            'Customers',
            'OrderAddresses',
        ]);
        $email = (string)($order->customer->email ?? '');
        if ($email === '') {
            return;
        }
        $actor = (int)($order->created_by_user_id ?: 0);
        $address = $this->shippingSummary($order);
        $promised = $order->promised_delivery_date
            ? $order->promised_delivery_date->format('d M Y')
            : 'to be confirmed';
        $body = implode("\n", [
            sprintf('Thank you. Order %s is confirmed.', $order->order_number),
            sprintf('Total: %s AUD cents (GST inclusive).', (string)$order->grand_total_cents),
            $address,
            'Promised delivery: ' . $promised . '.',
            'We will send tracking when the order ships.',
        ]);
        $this->queue->enqueue([
            'reference_number' => (new InventoryLedger())->nextDocumentNumber('outbound_message', 'OUT'),
            'customer_id' => $order->customer_id,
            'channel' => 'email',
            'recipient' => $email,
            'template_key' => 'order_confirmation',
            'subject' => 'Order ' . $order->order_number . ' confirmed',
            'body_text' => $body,
            'related_entity_type' => 'sales_order',
            'related_entity_id' => $order->id,
            'metadata' => ['order_number' => $order->order_number],
        ], $actor > 0 ? $actor : 1);
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return string
     */
    private function shippingSummary(SalesOrder $order): string
    {
        foreach ($order->order_addresses ?? [] as $row) {
            if ($row->address_type === 'shipping') {
                return sprintf(
                    'Deliver to %s, %s, %s %s %s',
                    $row->recipient_name,
                    $row->line1,
                    $row->suburb,
                    $row->state,
                    $row->postcode,
                );
            }
        }

        return 'Delivery address as supplied at checkout.';
    }

    /**
     * @param object $intent Stripe PaymentIntent.
     * @return string
     */
    private function failureMessage(object $intent): string
    {
        $error = $intent->last_payment_error ?? null;
        if (is_object($error) && isset($error->message)) {
            return (string)$error->message;
        }
        if (is_array($error) && isset($error['message'])) {
            return (string)$error['message'];
        }

        return 'payment_failed';
    }

    /**
     * @param object $refund Stripe refund object.
     * @return array<string, string>
     */
    private function refundMetadata(object $refund): array
    {
        $raw = $refund->metadata ?? [];
        if (is_object($raw)) {
            $raw = (array)$raw;
        }
        if (!is_array($raw)) {
            return [];
        }
        $metadata = [];
        foreach ($raw as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $metadata[(string)$key] = (string)$value;
            }
        }

        return $metadata;
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        $connection = $this->fetchTable('SalesOrders')->getConnection();
        if (!$connection instanceof Connection) {
            throw new RuntimeException('Payments require a SQL connection.');
        }

        return $connection;
    }
}
