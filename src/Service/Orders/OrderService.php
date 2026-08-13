<?php
declare(strict_types=1);

namespace App\Service\Orders;

use App\Model\Entity\OrderNote;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Money;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * Staff order recording: header, line snapshots, status history and stock
 * reservation in a single application-owned transaction.
 */
class OrderService
{
    use LocatorAwareTrait;

    /**
     * Allowed source_channel values.
     *
     * @var array<int, string>
     */
    public const CHANNELS = [
        SalesOrder::CHANNEL_PHONE,
        SalesOrder::CHANNEL_EMAIL,
        SalesOrder::CHANNEL_SMS,
        SalesOrder::CHANNEL_IN_STORE,
        SalesOrder::CHANNEL_WEB,
    ];

    /**
     * Allowed forward status transitions.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        SalesOrder::STATUS_DRAFT => [
            SalesOrder::STATUS_CONFIRMED,
            SalesOrder::STATUS_CANCELLED,
        ],
        SalesOrder::STATUS_CONFIRMED => [
            SalesOrder::STATUS_PROCESSING,
            SalesOrder::STATUS_ON_HOLD,
            SalesOrder::STATUS_CANCELLED,
        ],
        SalesOrder::STATUS_PROCESSING => [
            SalesOrder::STATUS_DISPATCHED,
            SalesOrder::STATUS_ON_HOLD,
            SalesOrder::STATUS_CANCELLED,
        ],
        SalesOrder::STATUS_DISPATCHED => [
            SalesOrder::STATUS_COMPLETED,
            SalesOrder::STATUS_CANCELLED,
        ],
        SalesOrder::STATUS_ON_HOLD => [
            SalesOrder::STATUS_PROCESSING,
            SalesOrder::STATUS_CANCELLED,
        ],
        SalesOrder::STATUS_COMPLETED => [],
        SalesOrder::STATUS_CANCELLED => [],
    ];

    /**
     * @param \App\Service\Inventory\InventoryLedger $ledger Inventory stored-procedure wrapper.
     */
    public function __construct(private InventoryLedger $ledger)
    {
    }

    /**
     * Record a staff-entered order and reserve whatever stock is on hand.
     *
     * Short stock is recorded on the line metadata and never blocks the save.
     *
     * @param array<string, mixed> $data Validated payload from OrdersController.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\SalesOrder
     */
    public function create(array $data, int $actorUserId): SalesOrder
    {
        $channel = (string)($data['source_channel'] ?? '');
        if (!in_array($channel, self::CHANNELS, true)) {
            throw new InvalidArgumentException('A source channel is required.');
        }

        $lines = $data['lines'] ?? [];
        if (!is_array($lines) || $lines === []) {
            throw new InvalidArgumentException('Add at least one product line.');
        }

        return $this->connection()->transactional(function () use ($data, $actorUserId, $channel, $lines) {
            $customerId = $this->resolveCustomer($data, $channel);
            $orderNumber = $this->ledger->nextDocumentNumber('sales_order', 'ORD');

            $prepared = [];
            $subtotal = 0;
            $taxTotal = 0;
            foreach ($lines as $index => $line) {
                if (!is_array($line)) {
                    continue;
                }
                $prepared[] = $this->prepareLine($line, $index);
            }
            if ($prepared === []) {
                throw new InvalidArgumentException('Add at least one product line.');
            }
            foreach ($prepared as $item) {
                $subtotal += $item['line_total_cents'];
                $taxTotal += $item['tax_cents'];
            }

            $shipping = (int)($data['shipping_cents'] ?? 0);
            $discount = (int)($data['discount_cents'] ?? 0);
            $grandTotal = $subtotal - $discount + $shipping;

            $orders = $this->fetchTable('SalesOrders');
            $order = $orders->newEmptyEntity();
            $order->order_number = $orderNumber;
            $order->customer_id = $customerId;
            $order->guest_name = $this->nullableString($data['guest_name'] ?? null);
            $order->guest_email = $this->nullableString($data['guest_email'] ?? null);
            $order->guest_phone = $this->nullableString($data['guest_phone'] ?? null);
            $order->status = SalesOrder::STATUS_CONFIRMED;
            $order->payment_status = 'pending';
            $order->fulfilment_method = 'shipping';
            $order->currency = 'AUD';
            $order->subtotal_cents = $subtotal;
            $order->discount_cents = $discount;
            $order->shipping_cents = $shipping;
            $order->tax_cents = $taxTotal;
            $order->grand_total_cents = $grandTotal;
            $order->customer_notes = $this->nullableString($data['customer_notes'] ?? null);
            $order->internal_notes = $this->nullableString($data['internal_notes'] ?? null);
            $order->placed_at = DateTime::now('UTC');
            $order->created_by_user_id = $actorUserId;
            $order->source_channel = $channel;
            $order->external_source_reference = $this->nullableString(
                $data['external_source_reference'] ?? null,
            );
            $order->order_type = 'retail';
            $order->promised_delivery_date = $this->parseDate($data['promised_delivery_date'] ?? null);
            $order->version_number = 1;
            $order->metadata = ['created_via' => 'admin_manual'];
            $orders->saveOrFail($order);

            $this->recordStatus($order, null, SalesOrder::STATUS_CONFIRMED, $actorUserId, 'Order recorded');

            $itemsTable = $this->fetchTable('SalesOrderItems');
            $reservations = $this->fetchTable('StockReservations');
            $shortages = [];

            foreach ($prepared as $row) {
                $item = $itemsTable->newEmptyEntity();
                foreach ($row as $field => $value) {
                    if ($field === 'quantity_requested') {
                        continue;
                    }
                    $item->set($field, $value);
                }
                $item->sales_order_id = $order->id;
                $itemsTable->saveOrFail($item);

                $location = $this->ledger->bestLocationFor((int)$row['product_variant_id']);
                $available = $location['available'];
                $reserveQty = min((int)$row['quantity'], max($available, 0));
                if ($reserveQty > 0) {
                    $this->ledger->applyInTransaction(
                        (int)$row['product_variant_id'],
                        $location['id'],
                        'reservation',
                        0,
                        $reserveQty,
                        'sales_order',
                        (int)$order->id,
                        'Reserve stock for ' . $order->order_number,
                        $actorUserId,
                    );
                    $reservation = $reservations->newEmptyEntity();
                    $reservation->sales_order_id = $order->id;
                    $reservation->sales_order_item_id = $item->id;
                    $reservation->product_variant_id = $row['product_variant_id'];
                    $reservation->inventory_location_id = $location['id'];
                    $reservation->quantity = $reserveQty;
                    $reservation->status = 'active';
                    $reservation->created_by_user_id = $actorUserId;
                    $reservations->saveOrFail($reservation);
                }
                if ($reserveQty < (int)$row['quantity']) {
                    $short = (int)$row['quantity'] - $reserveQty;
                    $shortages[] = ($row['sku_snapshot'] ?? 'item') . ' short ' . $short;
                    $meta = is_array($item->metadata) ? $item->metadata : [];
                    $meta['stock_shortfall'] = $short;
                    $item->metadata = $meta;
                    $itemsTable->saveOrFail($item);
                }
            }

            if ($shortages !== []) {
                $order->metadata = array_merge(
                    is_array($order->metadata) ? $order->metadata : [],
                    ['stock_warnings' => $shortages],
                );
                $orders->saveOrFail($order);
            }

            return $orders->get($order->id, contain: [
                'Customers',
                'SalesOrderItems',
                'OrderStatusHistory',
            ]);
        });
    }

    /**
     * Advance or hold an order, then annotate the trigger-written history row.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $toStatus Next status.
     * @param int $actorUserId Acting staff user.
     * @param string|null $note Optional history note.
     * @return \App\Model\Entity\SalesOrder
     */
    public function changeStatus(
        SalesOrder $order,
        string $toStatus,
        int $actorUserId,
        ?string $note = null,
    ): SalesOrder {
        $allowed = self::TRANSITIONS[$order->status] ?? [];
        if (!in_array($toStatus, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot move an order from %s to %s.',
                $order->status,
                $toStatus,
            ));
        }

        return $this->connection()->transactional(function () use ($order, $toStatus, $actorUserId, $note) {
            $from = $order->status;
            $order->status = $toStatus;
            if ($toStatus === SalesOrder::STATUS_COMPLETED) {
                $order->completed_at = DateTime::now('UTC');
            }
            if ($toStatus === SalesOrder::STATUS_CANCELLED) {
                $order->cancelled_at = DateTime::now('UTC');
            }
            $this->fetchTable('SalesOrders')->saveOrFail($order);
            $this->annotateLatestHistory($order, $from, $toStatus, $actorUserId, $note);

            return $order;
        });
    }

    /**
     * Update the promised delivery date in place.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string|null $date Y-m-d or empty.
     * @return \App\Model\Entity\SalesOrder
     */
    public function updatePromisedDate(SalesOrder $order, ?string $date): SalesOrder
    {
        $order->promised_delivery_date = $this->parseDate($date);
        $this->fetchTable('SalesOrders')->saveOrFail($order);

        return $order;
    }

    /**
     * Append an internal note.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $body Note text.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\OrderNote
     */
    public function addNote(SalesOrder $order, string $body, int $actorUserId): OrderNote
    {
        $notes = $this->fetchTable('OrderNotes');
        $note = $notes->newEmptyEntity();
        $note->sales_order_id = $order->id;
        $note->author_user_id = $actorUserId;
        $note->note_type = 'internal';
        $note->body = $body;
        $note->visible_to_customer = false;

        return $notes->saveOrFail($note);
    }

    /**
     * Snapshot a posted line against the live variant so later price edits
     * cannot rewrite history.
     *
     * @param array<string, mixed> $line Posted line.
     * @param int $index Line index for error messages.
     * @return array<string, mixed>
     */
    private function prepareLine(array $line, int $index): array
    {
        $variantId = (int)($line['product_variant_id'] ?? 0);
        $quantity = (int)($line['quantity'] ?? 0);
        if ($variantId < 1 || $quantity < 1) {
            throw new InvalidArgumentException('Each line needs a product and a quantity of at least 1.');
        }

        $variant = $this->fetchTable('ProductVariants')->get($variantId, contain: ['Products']);
        $unitPrice = (int)$variant->price_cents;
        $lineTotal = $unitPrice * $quantity;
        $taxRate = (string)$variant->tax_rate;
        $tax = Money::gstPortionInclusive($lineTotal, $taxRate);
        $productName = $variant->product->name ?? $variant->name;

        return [
            'item_type' => 'product',
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => $variant->sku,
            'item_name_snapshot' => $productName,
            'variant_name_snapshot' => $variant->name,
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'discount_cents' => 0,
            'tax_cents' => $tax,
            'line_total_cents' => $lineTotal,
            'cost_snapshot_cents' => $variant->cost_cents,
            'tax_rate_snapshot' => $taxRate,
            'fulfilled_quantity' => 0,
            'returned_quantity' => 0,
            'metadata' => ['line_index' => $index],
            'quantity_requested' => $quantity,
        ];
    }

    /**
     * Attach to an existing customer or create one from the posted identity.
     *
     * @param array<string, mixed> $data Payload.
     * @param string $channel Order source channel, reused as customer source.
     * @return int|null
     */
    private function resolveCustomer(array $data, string $channel): ?int
    {
        $existingId = (int)($data['customer_id'] ?? 0);
        if ($existingId > 0) {
            $this->fetchTable('Customers')->get($existingId);

            return $existingId;
        }

        $first = trim((string)($data['customer_first_name'] ?? ''));
        $last = trim((string)($data['customer_last_name'] ?? ''));
        $email = trim((string)($data['customer_email'] ?? ''));
        $phone = trim((string)($data['customer_phone'] ?? ''));

        if ($first === '' && $last === '' && $email === '' && $phone === '') {
            return null;
        }
        if ($email === '' && $phone === '' && $first === '') {
            throw new InvalidArgumentException(
                'A new customer needs a name, email or phone number as a contact method.',
            );
        }

        $customers = $this->fetchTable('Customers');
        if ($email !== '') {
            $match = $customers->find()->where(['email' => $email, 'deleted IS' => null])->first();
            if ($match) {
                return (int)$match->id;
            }
        }

        $customer = $customers->newEmptyEntity();
        $customer->first_name = $first !== '' ? $first : ($email !== '' ? strtok($email, '@') : 'Customer');
        $customer->last_name = $last !== '' ? $last : null;
        $customer->email = $email !== '' ? $email : null;
        $customer->phone = $phone !== '' ? $phone : null;
        $customer->status = 'active';
        $customer->source = $channel;
        $customer->customer_type = 'individual';
        $customer->display_name = trim($customer->first_name . ' ' . (string)$customer->last_name);
        $customer->tags = [];
        $customer->metadata = ['created_via' => 'admin_order'];
        $customers->saveOrFail($customer);

        return (int)$customer->id;
    }

    /**
     * Insert a history row for creates (the DB trigger only fires on UPDATE).
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string|null $from Previous status.
     * @param string $to New status.
     * @param int $actorUserId Acting staff user.
     * @param string|null $note Optional note.
     * @return void
     */
    private function recordStatus(
        SalesOrder $order,
        ?string $from,
        string $to,
        int $actorUserId,
        ?string $note,
    ): void {
        $history = $this->fetchTable('OrderStatusHistory')->newEmptyEntity();
        $history->sales_order_id = $order->id;
        $history->from_status = $from;
        $history->to_status = $to;
        $history->changed_by_user_id = $actorUserId;
        $history->note = $note;
        $this->fetchTable('OrderStatusHistory')->saveOrFail($history);
    }

    /**
     * Fill changed_by_user_id on the row the status trigger just inserted.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $from Previous status.
     * @param string $to New status.
     * @param int $actorUserId Acting staff user.
     * @param string|null $note Optional note.
     * @return void
     */
    private function annotateLatestHistory(
        SalesOrder $order,
        string $from,
        string $to,
        int $actorUserId,
        ?string $note,
    ): void {
        $historyTable = $this->fetchTable('OrderStatusHistory');
        $row = $historyTable->find()
            ->where([
                'sales_order_id' => $order->id,
                'to_status' => $to,
            ])
            ->orderBy(['id' => 'DESC'])
            ->first();
        if ($row === null) {
            $this->recordStatus($order, $from, $to, $actorUserId, $note);

            return;
        }
        $row->from_status = $from;
        $row->changed_by_user_id = $actorUserId;
        $row->note = $note;
        $historyTable->saveOrFail($row);
    }

    /**
     * @param mixed $value Posted date.
     * @return \Cake\I18n\Date|null
     */
    private function parseDate(mixed $value): ?Date
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Date) {
            return $value;
        }

        return Date::parse((string)$value);
    }

    /**
     * @param mixed $value Posted string.
     * @return string|null
     */
    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string)$value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('SalesOrders')->getConnection();
    }
}
