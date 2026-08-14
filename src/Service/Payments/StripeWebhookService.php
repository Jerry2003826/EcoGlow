<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\Payment;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\OutboundQueue;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
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
        if (!$this->idempotency->claim(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId)) {
            return ['status' => 200, 'body' => ['received' => true, 'duplicate' => true]];
        }

        $type = (string)$event->type;
        try {
            if ($type === 'payment_intent.succeeded') {
                return $this->finish($eventId, $this->onSucceeded($event));
            }
            if ($type === 'payment_intent.payment_failed') {
                $this->onPaymentFailed($event);

                return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]]);
            }
            if ($type === 'payment_intent.canceled') {
                $this->onCanceled($event);

                return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]]);
            }
            Log::info('Ignoring Stripe event type: ' . $type);

            return $this->finish($eventId, ['status' => 200, 'body' => ['received' => true]]);
        } catch (RuntimeException $exception) {
            $this->idempotency->release(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId);
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
        if ((string)$payment->status === 'captured') {
            return ['status' => 200, 'body' => ['received' => true, 'duplicate' => true]];
        }
        $order = $this->fetchTable('SalesOrders')->get((int)$payment->sales_order_id, contain: [
            'Customers',
            'OrderAddresses',
        ]);
        $this->assertAmountAndCurrency($intent, $payment, $order);

        if ($order->status === SalesOrder::STATUS_CANCELLED) {
            throw new InvalidArgumentException(
                'Payment succeeded for a cancelled order; queued for reconciliation.',
            );
        }

        $actorId = (int)($order->created_by_user_id ?: 0);
        $amount = $this->capturedAmount($intent);
        $this->connection()->transactional(function () use ($order, $payment, $amount, $actorId, $intent): void {
            $this->lockPayment((int)$payment->id);
            $payment = $this->fetchTable('Payments')->get((int)$payment->id);
            $this->markCaptured($payment, $amount, $intent);
            $this->orders->confirmPaid($order, $actorId > 0 ? $actorId : (int)$order->customer_id);
            $this->creditInvoiceIfPresent($order, $amount);
        });

        try {
            $this->queueConfirmation($order);
        } catch (Throwable $exception) {
            Log::error('Order confirmation email could not be queued: ' . $exception->getMessage());
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
            $payment = $this->fetchTable('Payments')->get((int)$payment->id);
            if ($payment->status === 'captured') {
                return;
            }
            $payment->status = 'failed';
            $payment->failed_at = DateTime::now('UTC');
            $payment->failure_reason = $this->failureMessage($intent);
            $this->fetchTable('Payments')->saveOrFail($payment);
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
            $this->lockPayment((int)$payment->id);
            $payment = $this->fetchTable('Payments')->get((int)$payment->id);
            if ($payment->status !== 'captured') {
                $payment->status = 'failed';
                $payment->failed_at = DateTime::now('UTC');
                $payment->failure_reason = $this->failureMessage($intent);
                $this->fetchTable('Payments')->saveOrFail($payment);
            }
            $this->orders->failUnpaid(
                $order,
                $actorId > 0 ? $actorId : (int)$order->customer_id,
                'Stripe payment_intent.canceled',
            );
        });
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
            if ((string)$payment->status === 'captured') {
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
     * @return void
     */
    private function markCaptured(Payment $payment, int $amountCents, object $intent): void
    {
        if ((string)$payment->status === 'captured') {
            return;
        }
        $payment->status = 'captured';
        $payment->amount_cents = $amountCents;
        $payment->captured_at = DateTime::now('UTC');
        $payment->authorised_at = DateTime::now('UTC');
        $payment->provider_metadata = ['payment_intent' => (string)$intent->id];
        $this->fetchTable('Payments')->saveOrFail($payment);
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
     * @param \App\Model\Entity\SalesOrder $order Paid order.
     * @param int $amountCents Captured amount.
     * @return void
     */
    private function creditInvoiceIfPresent(SalesOrder $order, int $amountCents): void
    {
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $order->id, 'status !=' => 'void'])
            ->first();
        if ($invoice === null) {
            return;
        }
        $this->connection()->execute('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoice->id]);
        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $invoice->amount_paid_cents = (int)$invoice->amount_paid_cents + $amountCents;
        if ((int)$invoice->amount_paid_cents >= (int)$invoice->grand_total_cents) {
            $invoice->status = 'paid';
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
        $row = $this->fetchTable('PaymentReconciliationAlerts')->newEmptyEntity();
        $row->set('event_id', (string)$event->id);
        $row->set('provider_payment_id', is_object($intent) ? (string)($intent->id ?? '') : null);
        $row->set('sales_order_id', is_object($intent) ? $this->metadataOrderId($intent) : null);
        $row->set('reason', substr($reason, 0, 64));
        $row->set('detail', $reason);
        $row->set('payload_digest', hash('sha256', (string)$event->id . $reason));
        $saved = $this->fetchTable('PaymentReconciliationAlerts')->saveOrFail($row);

        return (int)$saved->id;
    }

    /**
     * @param string $eventId Event id.
     * @param array{status: int, body: array<string, mixed>} $result Result.
     * @return array{status: int, body: array<string, mixed>}
     */
    private function finish(string $eventId, array $result): array
    {
        $this->idempotency->complete(
            IdempotencyService::SCOPE_STRIPE_WEBHOOK,
            $eventId,
            $result['status'],
            $result['body'],
        );

        return $result;
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
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('SalesOrders')->getConnection();
    }
}
