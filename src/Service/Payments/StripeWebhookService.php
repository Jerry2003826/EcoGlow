<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\OutboundQueue;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
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
                $this->onSucceeded($event);
            } elseif ($type === 'payment_intent.payment_failed' || $type === 'payment_intent.canceled') {
                $this->onFailed($event);
            } else {
                Log::info('Ignoring Stripe event type: ' . $type);
            }
            $result = ['status' => 200, 'body' => ['received' => true]];
            $this->idempotency->complete(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId, 200, $result['body']);

            return $result;
        } catch (InvalidArgumentException $exception) {
            Log::error('Stripe webhook skipped: ' . $exception->getMessage());
            $result = ['status' => 200, 'body' => ['received' => true, 'skipped' => true]];
            $this->idempotency->complete(IdempotencyService::SCOPE_STRIPE_WEBHOOK, $eventId, 200, $result['body']);

            return $result;
        }
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return void
     */
    private function onSucceeded(Event $event): void
    {
        $intent = $event->data->object;
        $intentId = (string)$intent->id;
        $amount = (int)$intent->amount;
        $order = $this->orderFromIntent($intent);
        $actorId = (int)($order->created_by_user_id ?: 0);

        $this->connection()->transactional(function () use ($order, $intentId, $amount, $actorId, $intent): void {
            $this->upsertCapturedPayment($order, $intentId, $amount, $intent);
            $this->orders->confirmPaid($order, $actorId > 0 ? $actorId : (int)$order->customer_id);
            $this->creditInvoiceIfPresent($order, $amount);
        });

        try {
            $this->queueConfirmation($order);
        } catch (Throwable $exception) {
            Log::error('Order confirmation email could not be queued: ' . $exception->getMessage());
        }
    }

    /**
     * @param \Stripe\Event $event Event.
     * @return void
     */
    private function onFailed(Event $event): void
    {
        $intent = $event->data->object;
        $intentId = (string)$intent->id;
        $order = $this->orderFromIntent($intent);
        $actorId = (int)($order->created_by_user_id ?: 0);

        $this->connection()->transactional(function () use ($order, $intentId, $actorId, $intent): void {
            $this->markPaymentFailed($order, $intentId, $intent);
            $this->orders->failUnpaid(
                $order,
                $actorId > 0 ? $actorId : (int)$order->customer_id,
                'Stripe payment_intent.payment_failed',
            );
        });
    }

    /**
     * @param object $intent Stripe PaymentIntent.
     * @return \App\Model\Entity\SalesOrder
     */
    private function orderFromIntent(object $intent): SalesOrder
    {
        $metadata = $intent->metadata ?? null;
        $orderId = 0;
        $orderNumber = '';
        if (is_array($metadata) || is_object($metadata)) {
            $orderId = (int)($metadata['order_id'] ?? 0);
            $orderNumber = (string)($metadata['order_number'] ?? '');
        }
        $orders = $this->fetchTable('SalesOrders');
        if ($orderId > 0) {
            return $orders->get($orderId, contain: ['Customers', 'OrderAddresses']);
        }
        if ($orderNumber !== '') {
            $order = $orders->find()
                ->contain(['Customers', 'OrderAddresses'])
                ->where(['order_number' => $orderNumber])
                ->first();
            if ($order) {
                return $order;
            }
        }

        throw new InvalidArgumentException('PaymentIntent has no matching order.');
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $intentId PaymentIntent id.
     * @param int $amountCents Captured amount.
     * @param object $intent Stripe object.
     * @return void
     */
    private function upsertCapturedPayment(
        SalesOrder $order,
        string $intentId,
        int $amountCents,
        object $intent,
    ): void {
        $payments = $this->fetchTable('Payments');
        $payment = $payments->find()
            ->where(['provider' => 'stripe', 'provider_payment_id' => $intentId])
            ->first();
        if ($payment === null) {
            $payment = $payments->newEmptyEntity();
            $payment->sales_order_id = $order->id;
            $payment->provider = 'stripe';
            $payment->provider_payment_id = $intentId;
            $payment->method = 'card';
            $payment->currency = 'AUD';
            $payment->transaction_reference = $order->order_number;
        }
        $payment->status = 'captured';
        $payment->amount_cents = $amountCents;
        $payment->captured_at = DateTime::now('UTC');
        $payment->authorised_at = DateTime::now('UTC');
        $payment->provider_metadata = ['payment_intent' => $intentId];
        $payments->saveOrFail($payment);
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $intentId PaymentIntent id.
     * @param object $intent Stripe object.
     * @return void
     */
    private function markPaymentFailed(SalesOrder $order, string $intentId, object $intent): void
    {
        $payments = $this->fetchTable('Payments');
        $payment = $payments->find()
            ->where(['provider' => 'stripe', 'provider_payment_id' => $intentId])
            ->first();
        if ($payment === null) {
            $payment = $payments->newEmptyEntity();
            $payment->sales_order_id = $order->id;
            $payment->provider = 'stripe';
            $payment->provider_payment_id = $intentId;
            $payment->method = 'card';
            $payment->amount_cents = (int)($intent->amount ?? $order->grand_total_cents);
            $payment->currency = 'AUD';
            $payment->transaction_reference = $order->order_number;
        }
        $payment->status = 'failed';
        $payment->failed_at = DateTime::now('UTC');
        $payment->failure_reason = $this->failureMessage($intent);
        $payments->saveOrFail($payment);
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
        $invoice->amount_paid_cents = (int)$invoice->amount_paid_cents + $amountCents;
        if ((int)$invoice->amount_paid_cents >= (int)$invoice->grand_total_cents) {
            $invoice->status = 'paid';
            $invoice->paid_at = DateTime::now('UTC');
        }
        $this->fetchTable('Invoices')->saveOrFail($invoice);
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
