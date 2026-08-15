<?php
declare(strict_types=1);

namespace App\Service\Invoices;

use App\Model\Entity\Invoice;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\OutboundQueue;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * Issue invoices from order snapshots. Live catalogue prices never rewrite
 * a stored invoice line.
 */
class InvoiceService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Service\Inventory\InventoryLedger $ledger Document numbers.
     * @param \App\Service\OutboundQueue $queue Outbound mail queue.
     */
    public function __construct(
        private InventoryLedger $ledger,
        private OutboundQueue $queue,
    ) {
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order with items contained.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\Invoice
     */
    public function createFromOrder(SalesOrder $order, int $actorUserId): Invoice
    {
        if ($order->status === SalesOrder::STATUS_CANCELLED) {
            throw new InvalidArgumentException('A cancelled order cannot be invoiced.');
        }
        if (in_array((string)$order->payment_status, ['refunded', 'partially_refunded'], true)) {
            throw new InvalidArgumentException('A refunded order cannot be invoiced.');
        }
        $order = $this->fetchTable('SalesOrders')->get($order->id, finder: 'detail');

        return $this->connection()->transactional(function () use ($order, $actorUserId) {
            $locked = $this->connection()->execute(
                'SELECT status, payment_status FROM sales_orders WHERE id = ? FOR UPDATE',
                [$order->id],
            )->fetch('assoc');
            if (!is_array($locked)) {
                throw new InvalidArgumentException('The order could not be invoiced.');
            }
            if ((string)$locked['status'] === SalesOrder::STATUS_CANCELLED) {
                throw new InvalidArgumentException('A cancelled order cannot be invoiced.');
            }
            if (in_array((string)$locked['payment_status'], ['refunded', 'partially_refunded'], true)) {
                throw new InvalidArgumentException('A refunded order cannot be invoiced.');
            }
            $existing = $this->fetchTable('Invoices')->find()
                ->where([
                    'sales_order_id' => $order->id,
                    'status !=' => Invoice::STATUS_VOID,
                ])
                ->first();
            if ($existing) {
                throw new InvalidArgumentException(sprintf(
                    'Order %s already has invoice %s.',
                    $order->order_number,
                    $existing->invoice_number,
                ));
            }

            $today = Date::now('Australia/Melbourne');
            $invoices = $this->fetchTable('Invoices');
            $alreadyPaid = $this->capturedPaymentsTotal((int)$order->id);
            $invoice = $invoices->newEmptyEntity();
            $invoice->invoice_number = $this->ledger->nextDocumentNumber('invoice', 'INV');
            $invoice->invoice_type = 'invoice';
            $invoice->sales_order_id = $order->id;
            $invoice->customer_id = $order->customer_id;
            $invoice->status = $alreadyPaid >= (int)$order->grand_total_cents
                ? Invoice::STATUS_PAID
                : Invoice::STATUS_ISSUED;
            $invoice->currency = 'AUD';
            $invoice->issue_date = $today;
            $invoice->due_date = $today->addDays(14);
            $invoice->subtotal_cents = (int)$order->subtotal_cents;
            $invoice->discount_cents = (int)$order->discount_cents;
            $invoice->shipping_cents = (int)$order->shipping_cents;
            $invoice->tax_cents = (int)$order->tax_cents;
            $invoice->grand_total_cents = (int)$order->grand_total_cents;
            $invoice->amount_paid_cents = $alreadyPaid;
            $invoice->credit_applied_cents = 0;
            $invoice->business_snapshot = $this->businessSnapshot();
            $invoice->customer_snapshot = $this->customerSnapshot($order);
            $invoice->billing_address_snapshot = [];
            $invoice->issued_by_user_id = $actorUserId;
            $invoice->issued_at = DateTime::now('UTC');
            $invoice->metadata = [
                'source_order_number' => $order->order_number,
            ];
            $invoice->set('open_order_key', (int)$order->id);
            $invoices->saveOrFail($invoice);

            $itemsTable = $this->fetchTable('InvoiceItems');
            $lineNumber = 1;
            foreach ($order->sales_order_items as $item) {
                $line = $itemsTable->newEmptyEntity();
                $line->invoice_id = $invoice->id;
                $line->sales_order_item_id = $item->id;
                $line->line_number = $lineNumber++;
                $line->item_type = $item->item_type ?: 'product';
                $line->sku_snapshot = $item->sku_snapshot;
                $line->item_name_snapshot = $item->item_name_snapshot;
                $line->description_snapshot = $item->variant_name_snapshot;
                $line->quantity = (int)$item->quantity;
                $line->unit_price_cents = (int)$item->unit_price_cents;
                $line->discount_cents = (int)$item->discount_cents;
                $line->tax_rate_snapshot = $item->tax_rate_snapshot ?: '0';
                $line->tax_cents = (int)$item->tax_cents;
                $line->line_total_cents = (int)$item->line_total_cents;
                $line->metadata = ['source' => 'sales_order_item'];
                $itemsTable->saveOrFail($line);
            }

            $note = 'Issued from ' . $order->order_number;
            $this->recordStatus($invoice, null, Invoice::STATUS_ISSUED, $actorUserId, $note);
            if ($invoice->status === Invoice::STATUS_PAID) {
                $invoice->paid_at = DateTime::now('UTC');
                $invoices->saveOrFail($invoice);
                $this->recordStatus(
                    $invoice,
                    Invoice::STATUS_ISSUED,
                    Invoice::STATUS_PAID,
                    $actorUserId,
                    'Paid on the web checkout',
                );
            }

            return $invoices->get($invoice->id, finder: 'detail');
        });
    }

    /**
     * Queue the invoice for email. Does not send.
     *
     * @param \App\Model\Entity\Invoice $invoice Invoice.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function send(Invoice $invoice, int $actorUserId): void
    {
        $recipient = $invoice->customer_snapshot['email']
            ?? $invoice->customer->email
            ?? null;
        if (!$recipient) {
            throw new InvalidArgumentException('This invoice has no customer email to send to.');
        }
        $reference = $this->ledger->nextDocumentNumber('outbound_message', 'OUT');
        $this->queue->enqueue([
            'reference_number' => $reference,
            'customer_id' => $invoice->customer_id,
            'channel' => 'email',
            'recipient' => $recipient,
            'template_key' => 'invoice',
            'subject' => 'Invoice ' . $invoice->invoice_number,
            'body_text' => 'Please find invoice ' . $invoice->invoice_number
                . ' for ' . $invoice->grand_total_cents . ' cents (GST inclusive).',
            'related_entity_type' => 'invoice',
            'related_entity_id' => $invoice->id,
            'metadata' => ['invoice_number' => $invoice->invoice_number],
        ], $actorUserId);
        $meta = is_array($invoice->metadata) ? $invoice->metadata : [];
        $meta['last_queued_at'] = DateTime::now('UTC')->toIso8601String();
        $invoice->metadata = $meta;
        $this->fetchTable('Invoices')->saveOrFail($invoice);
    }

    /**
     * Record a payment against the invoice balance (integer cents).
     *
     * @param \App\Model\Entity\Invoice $invoice Invoice.
     * @param int $amountCents Amount in cents.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\Invoice
     */
    public function recordPayment(Invoice $invoice, int $amountCents, int $actorUserId): Invoice
    {
        if ($amountCents < 1) {
            throw new InvalidArgumentException('Enter a payment of at least 1 cent.');
        }

        return $this->connection()->transactional(function () use ($invoice, $amountCents, $actorUserId) {
            $this->connection()->execute('SELECT id FROM invoices WHERE id = ? FOR UPDATE', [$invoice->id]);
            $invoice = $this->fetchTable('Invoices')->get($invoice->id);
            if ($invoice->status === Invoice::STATUS_VOID) {
                throw new InvalidArgumentException('A void invoice cannot take a payment.');
            }
            if ($invoice->status === Invoice::STATUS_CREDITED) {
                throw new InvalidArgumentException('A credited invoice cannot take a payment.');
            }
            $balance = (int)$invoice->balance_due_cents;
            if ($amountCents > $balance) {
                throw new InvalidArgumentException('That payment is larger than the balance due.');
            }
            $invoice->amount_paid_cents = (int)$invoice->amount_paid_cents + $amountCents;
            $paid = (int)$invoice->amount_paid_cents + (int)$invoice->credit_applied_cents;
            if ($paid >= (int)$invoice->grand_total_cents) {
                $invoice->status = Invoice::STATUS_PAID;
                $invoice->paid_at = DateTime::now('UTC');
            }
            $this->fetchTable('Invoices')->saveOrFail($invoice);

            if ($invoice->sales_order_id) {
                $payments = $this->fetchTable('Payments');
                $payment = $payments->newEmptyEntity();
                $payment->sales_order_id = $invoice->sales_order_id;
                $payment->provider = 'manual';
                $payment->provider_payment_id = 'inv-' . $invoice->id . '-' . bin2hex(random_bytes(8));
                $payment->method = 'manual';
                $payment->status = 'captured';
                $payment->amount_cents = $amountCents;
                $payment->currency = 'AUD';
                $payment->transaction_reference = $invoice->invoice_number;
                $payment->provider_metadata = ['invoice_id' => $invoice->id];
                $payment->captured_at = DateTime::now('UTC');
                $payments->saveOrFail($payment);
                if ($invoice->status === Invoice::STATUS_PAID) {
                    $this->fetchTable('SalesOrders')->updateAll(
                        ['payment_status' => 'paid'],
                        [
                            'id' => $invoice->sales_order_id,
                            'payment_status IN' => ['pending', 'failed'],
                        ],
                    );
                }
            }

            return $invoice;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function businessSnapshot(): array
    {
        $row = $this->connection()->execute(
            'SELECT name, legal_name, trading_name, abn, email, phone, address
               FROM businesses WHERE deleted IS NULL ORDER BY id ASC LIMIT 1',
        )->fetch('assoc');
        if (!is_array($row)) {
            return [
                'name' => 'Eco Glow Lighting',
                'email' => 'hello@ecoglowlighting.example',
            ];
        }
        if (is_string($row['address'] ?? null)) {
            $decoded = json_decode($row['address'], true);
            $row['address'] = is_array($decoded) ? $decoded : [];
        }

        return $row;
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @return array<string, mixed>
     */
    private function customerSnapshot(SalesOrder $order): array
    {
        $customer = $order->customer;

        return [
            'name' => $order->customer_label,
            'email' => $customer?->email ?? $order->guest_email,
            'phone' => $customer?->phone ?? $order->guest_phone,
            'company' => $customer?->company,
        ];
    }

    /**
     * @param \App\Model\Entity\Invoice $invoice Invoice.
     * @param string|null $from Previous status.
     * @param string $to New status.
     * @param int $actorUserId Acting staff user.
     * @param string|null $note Note.
     * @return void
     */
    private function recordStatus(
        Invoice $invoice,
        ?string $from,
        string $to,
        int $actorUserId,
        ?string $note,
    ): void {
        $history = $this->fetchTable('InvoiceStatusHistory')->newEmptyEntity();
        $history->invoice_id = $invoice->id;
        $history->from_status = $from;
        $history->to_status = $to;
        $history->changed_by_user_id = $actorUserId;
        $history->note = $note;
        $this->fetchTable('InvoiceStatusHistory')->saveOrFail($history);
    }

    /**
     * Captured Stripe/manual payments already on the order.
     *
     * @param int $orderId Sales order id.
     * @return int
     */
    private function capturedPaymentsTotal(int $orderId): int
    {
        $total = 0;
        $rows = $this->fetchTable('Payments')->find()
            ->where([
                'sales_order_id' => $orderId,
                'status' => 'captured',
            ])
            ->all();
        foreach ($rows as $row) {
            $total += (int)$row->amount_cents;
        }

        return $total;
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Invoices')->getConnection();
    }
}
