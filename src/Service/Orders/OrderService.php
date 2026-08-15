<?php
declare(strict_types=1);

namespace App\Service\Orders;

use App\Model\Entity\Cart;
use App\Model\Entity\OrderNote;
use App\Model\Entity\SalesOrder;
use App\Service\AustralianStates;
use App\Service\Cart\CartService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Money;
use App\Service\Payments\PaymentGatewayFactory;
use App\Service\Payments\PaymentGatewayInterface;
use App\Service\Payments\PaymentUncertainException;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use Throwable;

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
     * @var int
     */
    private const MAX_HOLD_DEFERRALS = 8;

    /**
     * Fulfilment moves that a refunded order must never take.
     *
     * @var array<int, string>
     */
    private const BLOCKED_WHEN_REFUNDED = [
        SalesOrder::STATUS_CONFIRMED,
        SalesOrder::STATUS_PROCESSING,
        SalesOrder::STATUS_DISPATCHED,
        SalesOrder::STATUS_COMPLETED,
        SalesOrder::STATUS_ON_HOLD,
    ];

    /**
     * @param \App\Service\Inventory\InventoryLedger $ledger Inventory stored-procedure wrapper.
     * @param \App\Service\Payments\PaymentGatewayInterface|null $payments Stripe or test double.
     */
    public function __construct(
        private InventoryLedger $ledger,
        private ?PaymentGatewayInterface $payments = null,
    ) {
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
                    $reservation->reservation_movement_id = $this->ledger->lastInsertId();
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
        return $this->connection()->transactional(function () use ($order, $toStatus, $actorUserId, $note) {
            $this->lockOrder((int)$order->id);
            /** @var \App\Model\Entity\SalesOrder $current */
            $current = $this->fetchTable('SalesOrders')->get($order->id);
            $allowed = self::TRANSITIONS[$current->status] ?? [];
            if (!in_array($toStatus, $allowed, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot move an order from %s to %s.',
                    $current->status,
                    $toStatus,
                ));
            }
            if (
                (string)$current->get('payment_status') === 'refunded'
                && in_array($toStatus, self::BLOCKED_WHEN_REFUNDED, true)
            ) {
                throw new InvalidArgumentException('A refunded order cannot be fulfilled.');
            }
            if ($current->isOpenWebCheckout() && $toStatus !== SalesOrder::STATUS_CANCELLED) {
                throw new InvalidArgumentException(
                    'A website checkout that is still waiting for Stripe cannot be confirmed from admin.',
                );
            }
            if ($toStatus === SalesOrder::STATUS_CANCELLED && $current->isOpenWebCheckout()) {
                $intentState = $this->cancelOpenStripeIntents($current);
                if ($intentState === 'already_succeeded') {
                    throw new InvalidArgumentException(
                        'Stripe has already captured this payment. Wait for the webhook or refund it.',
                    );
                }
            }

            $from = $current->status;
            $current->status = $toStatus;
            $current->version_number = (int)$current->version_number + 1;
            if ($toStatus === SalesOrder::STATUS_COMPLETED) {
                $current->completed_at = DateTime::now('UTC');
            }
            if ($toStatus === SalesOrder::STATUS_CANCELLED) {
                $current->cancelled_at = DateTime::now('UTC');
            }
            $this->fetchTable('SalesOrders')->saveOrFail($current);
            $this->annotateLatestHistory($current, $from, $toStatus, $actorUserId, $note);

            if ($toStatus === SalesOrder::STATUS_CANCELLED) {
                $this->releaseReservations((int)$current->id, $actorUserId, $current->order_number);
            } elseif (
                $toStatus === SalesOrder::STATUS_DISPATCHED
                || $toStatus === SalesOrder::STATUS_COMPLETED
            ) {
                $this->consumeReservations((int)$current->id, $actorUserId, $current->order_number);
            }

            return $current;
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
     * Web checkout: draft unpaid order, live prices, full-quantity reservation.
     *
     * Posted prices are ignored. Short stock refuses the order rather than
     * recording a partial reservation.
     *
     * @param int $customerId Customer placing the order.
     * @param int $userId Acting customer user.
     * @param \App\Model\Entity\Cart $cart Cart with items contained.
     * @param array<string, mixed> $address Shipping fields.
     * @return \App\Model\Entity\SalesOrder
     */
    public function createFromCheckout(
        int $customerId,
        int $userId,
        Cart $cart,
        array $address,
        string $checkoutAttemptId = '',
    ): SalesOrder {
        $this->assertCheckoutAddress($address);
        $items = $cart->cart_items ?? [];
        if ($items === []) {
            throw new InvalidArgumentException('Your basket is empty.');
        }

        $this->fetchTable('Customers')->get($customerId);

        return $this->connection()->transactional(function () use (
            $customerId,
            $userId,
            $cart,
            $address,
            $items,
            $checkoutAttemptId,
        ) {
            $this->connection()->execute('SELECT id FROM carts WHERE id = ? FOR UPDATE', [$cart->id]);
            $cart = $this->fetchTable('Carts')->get($cart->id, contain: ['CartItems']);
            if ($checkoutAttemptId !== '') {
                $existing = $this->fetchTable('SalesOrders')->find()
                    ->contain(['Customers', 'SalesOrderItems', 'OrderAddresses'])
                    ->where(['checkout_attempt_id' => $checkoutAttemptId])
                    ->first();
                if ($existing) {
                    if ($this->attemptMatchesOpenCheckout($existing, $customerId, $userId, $cart)) {
                        return $existing;
                    }
                    throw new InvalidArgumentException(
                        'This checkout session is no longer valid. Refresh the page and try again.',
                    );
                }
            }
            if ((string)$cart->get('status') === 'converted') {
                throw new InvalidArgumentException('This basket has already been checked out.');
            }

            $cartService = new CartService();
            $totals = $cartService->totals($cart);
            $orderNumber = $this->ledger->nextDocumentNumber('sales_order', 'ORD');
            $promised = Date::now('Australia/Melbourne')->addDays(10);
            $holdUntil = DateTime::now('UTC')->addMinutes(15);

            $prepared = [];
            foreach ($cart->cart_items ?? $items as $index => $item) {
                $prepared[] = $this->prepareLine([
                    'product_variant_id' => (int)$item->product_variant_id,
                    'quantity' => (int)$item->quantity,
                ], $index);
            }

            $orders = $this->fetchTable('SalesOrders');
            $order = $orders->newEmptyEntity();
            $order->order_number = $orderNumber;
            $order->customer_id = $customerId;
            $order->status = SalesOrder::STATUS_DRAFT;
            $order->payment_status = 'pending';
            $order->fulfilment_method = 'shipping';
            $order->currency = 'AUD';
            $order->subtotal_cents = (int)$totals['subtotal_cents'];
            $order->discount_cents = 0;
            $order->shipping_cents = (int)$totals['shipping_cents'];
            $order->tax_cents = (int)$totals['gst_cents'];
            $order->grand_total_cents = (int)$totals['total_cents'];
            $order->placed_at = DateTime::now('UTC');
            $order->created_by_user_id = $userId;
            $order->source_channel = SalesOrder::CHANNEL_WEB;
            $order->order_type = 'retail';
            $order->promised_delivery_date = $promised;
            $order->version_number = 1;
            $order->set('checkout_attempt_id', $checkoutAttemptId !== '' ? $checkoutAttemptId : null);
            $order->set('hold_expires_at', $holdUntil);
            $order->set('cart_id', $cart->id);
            $order->metadata = [
                'created_via' => 'web_checkout',
                'checkout_attempt_id' => $checkoutAttemptId,
            ];
            $orders->saveOrFail($order);

            $this->recordStatus($order, null, SalesOrder::STATUS_DRAFT, $userId, 'Checkout started');
            $this->snapshotAddress($order, $address);

            $itemsTable = $this->fetchTable('SalesOrderItems');
            $reservations = $this->fetchTable('StockReservations');
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
                $qty = (int)$row['quantity'];
                if ($qty > max($available, 0)) {
                    throw new InvalidArgumentException(
                        'One or more items are no longer available in the quantity you asked for.',
                    );
                }
                $this->ledger->applyInTransaction(
                    (int)$row['product_variant_id'],
                    $location['id'],
                    'reservation',
                    0,
                    $qty,
                    'sales_order',
                    (int)$order->id,
                    'Reserve stock for ' . $order->order_number,
                    $userId,
                );
                $reservation = $reservations->newEmptyEntity();
                $reservation->sales_order_id = $order->id;
                $reservation->sales_order_item_id = $item->id;
                $reservation->product_variant_id = $row['product_variant_id'];
                $reservation->inventory_location_id = $location['id'];
                $reservation->quantity = $qty;
                $reservation->status = 'active';
                $reservation->reservation_movement_id = $this->ledger->lastInsertId();
                $reservation->created_by_user_id = $userId;
                $reservation->set('expires_at', $holdUntil);
                $reservations->saveOrFail($reservation);
            }

            $cart->set('status', 'converted');
            $cart->set('checkout_attempt_id', $checkoutAttemptId !== '' ? $checkoutAttemptId : null);
            $this->fetchTable('Carts')->saveOrFail($cart);

            return $orders->get($order->id, contain: [
                'Customers',
                'SalesOrderItems',
                'OrderAddresses',
            ]);
        });
    }

    /**
     * Payment succeeded: confirm the order and consume reservations.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param int $actorUserId Acting user (customer or system).
     * @return \App\Model\Entity\SalesOrder
     */
    public function confirmPaid(SalesOrder $order, int $actorUserId): SalesOrder
    {
        return $this->connection()->transactional(function () use ($order, $actorUserId) {
            $this->lockOrder((int)$order->id);
            $order = $this->fetchTable('SalesOrders')->get($order->id);
            if ($order->payment_status === 'refunded') {
                return $order;
            }
            if ($order->status === SalesOrder::STATUS_CANCELLED) {
                throw new InvalidArgumentException(
                    'Payment succeeded for a cancelled order; queued for reconciliation.',
                );
            }
            if ($order->payment_status === 'paid') {
                return $order;
            }
            $cas = $this->connection()->execute(
                "UPDATE sales_orders
                    SET payment_status = 'paid', status = ?
                  WHERE id = ?
                    AND status = ?
                    AND payment_status IN ('pending', 'failed')",
                [SalesOrder::STATUS_CONFIRMED, $order->id, SalesOrder::STATUS_DRAFT],
            );
            if ($cas->rowCount() !== 1) {
                $order = $this->fetchTable('SalesOrders')->get($order->id);
                if ($order->payment_status === 'paid') {
                    return $order;
                }
                throw new InvalidArgumentException(
                    'Payment succeeded for an order that is not awaiting capture.',
                );
            }
            $order = $this->fetchTable('SalesOrders')->get($order->id);

            $from = SalesOrder::STATUS_DRAFT;
            $this->annotateLatestHistory(
                $order,
                $from,
                SalesOrder::STATUS_CONFIRMED,
                $actorUserId,
                'Payment captured',
            );
            $this->consumeReservations((int)$order->id, $actorUserId, $order->order_number);

            return $order;
        });
    }

    /**
     * Payment failed or abandoned: cancel and release reservations.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param int $actorUserId Acting user.
     * @param string $note History note.
     * @return \App\Model\Entity\SalesOrder
     */
    public function failUnpaid(SalesOrder $order, int $actorUserId, string $note = 'Payment failed'): SalesOrder
    {
        return $this->connection()->transactional(function () use ($order, $actorUserId, $note) {
            $this->lockOrder((int)$order->id);
            $order = $this->fetchTable('SalesOrders')->get($order->id);
            if (
                in_array((string)$order->payment_status, ['paid', 'partially_refunded', 'refunded'], true)
                || $order->status === SalesOrder::STATUS_CANCELLED
            ) {
                return $order;
            }
            if ($this->hasCapturedPayment((int)$order->id)) {
                return $order;
            }
            if ($this->cancelOpenStripeIntents($order) === 'already_succeeded') {
                $this->deferHoldForInFlightCapture($order);

                return $order;
            }
            $order->payment_status = 'failed';
            $this->fetchTable('SalesOrders')->saveOrFail($order);
            if ($order->status !== SalesOrder::STATUS_CANCELLED) {
                $this->changeStatus($order, SalesOrder::STATUS_CANCELLED, $actorUserId, $note);
            }

            return $this->fetchTable('SalesOrders')->get($order->id);
        });
    }

    /**
     * Lock the order row before any payment/hold state change.
     *
     * @param int $orderId Order id.
     * @return void
     */
    public function lockOrder(int $orderId): void
    {
        $this->connection()->execute(
            'SELECT id FROM sales_orders WHERE id = ? FOR UPDATE',
            [$orderId],
        );
    }

    /**
     * @param int $orderId Order id.
     * @return bool
     */
    public function hasCapturedPayment(int $orderId): bool
    {
        return $this->fetchTable('Payments')->exists([
            'sales_order_id' => $orderId,
            'status IN' => ['captured', 'partially_refunded', 'refunded'],
        ]);
    }

    /**
     * Claim expired unpaid holds one row at a time, skipping rows locked by capture.
     *
     * @return int Number of orders cancelled.
     */
    public function releaseExpiredHolds(): int
    {
        $released = 0;
        for ($guard = 0; $guard < 500; $guard++) {
            $result = $this->connection()->transactional(function () {
                $id = $this->claimOneExpiredHoldId();
                if ($id === null) {
                    return null;
                }
                $order = $this->fetchTable('SalesOrders')->get($id);
                $updated = $this->failUnpaid(
                    $order,
                    (int)($order->created_by_user_id ?: 0),
                    'Checkout hold expired',
                );

                return $updated->status === SalesOrder::STATUS_CANCELLED ? 1 : 0;
            });
            if ($result === null) {
                break;
            }
            $released += $result;
        }

        return $released;
    }

    /**
     * Keep an uncertain PaymentIntent setup from sitting on a full 15-minute hold.
     *
     * @param \App\Model\Entity\SalesOrder $order Held checkout.
     * @param int $minutes Remaining hold minutes.
     * @return \App\Model\Entity\SalesOrder
     */
    public function shortenUncertainHold(SalesOrder $order, int $minutes = 5): SalesOrder
    {
        return $this->connection()->transactional(function () use ($order, $minutes) {
            $this->lockOrder((int)$order->id);
            $order = $this->fetchTable('SalesOrders')->get($order->id);
            if (
                $order->status !== SalesOrder::STATUS_DRAFT
                || !in_array((string)$order->payment_status, ['pending', 'failed'], true)
            ) {
                return $order;
            }
            $limit = DateTime::now('UTC')->addMinutes(max(1, $minutes));
            $current = $order->get('hold_expires_at');
            if ($current instanceof DateTime && $current->lessThanOrEquals($limit)) {
                return $order;
            }
            $order->set('hold_expires_at', $limit);
            $this->fetchTable('SalesOrders')->saveOrFail($order);
            $this->fetchTable('StockReservations')->updateAll(
                ['expires_at' => $limit],
                ['sales_order_id' => $order->id, 'status' => 'active'],
            );

            return $order;
        });
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Candidate order.
     * @param int $customerId Current customer.
     * @param int $userId Current user.
     * @param \App\Model\Entity\Cart $cart Current cart.
     * @return bool
     */
    public function attemptMatchesOpenCheckout(
        SalesOrder $order,
        int $customerId,
        int $userId,
        Cart $cart,
    ): bool {
        if (
            (int)$order->customer_id !== $customerId
            || (int)$order->created_by_user_id !== $userId
            || $order->status !== SalesOrder::STATUS_DRAFT
            || !in_array((string)$order->payment_status, ['pending', 'failed'], true)
        ) {
            return false;
        }
        if (
            (int)$order->get('cart_id') !== (int)$cart->id
            && $this->cartHasItems($cart)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return int|null
     */
    private function claimOneExpiredHoldId(): ?int
    {
        $params = [
            SalesOrder::CHANNEL_WEB,
            SalesOrder::STATUS_DRAFT,
            DateTime::now('UTC')->format('Y-m-d H:i:s'),
        ];
        $sql = "SELECT id FROM sales_orders
                 WHERE source_channel = ?
                   AND status = ?
                   AND payment_status IN ('pending', 'failed')
                   AND hold_expires_at IS NOT NULL
                   AND hold_expires_at <= ?
                 LIMIT 1
                 FOR UPDATE SKIP LOCKED";
        try {
            $rows = $this->connection()->execute($sql, $params)->fetchAll('assoc');
            $row = $rows[0] ?? null;
        } catch (Throwable) {
            $rows = $this->connection()->execute(
                "SELECT id FROM sales_orders
                  WHERE source_channel = ?
                    AND status = ?
                    AND payment_status IN ('pending', 'failed')
                    AND hold_expires_at IS NOT NULL
                    AND hold_expires_at <= ?
                  LIMIT 1",
                $params,
            )->fetchAll('assoc');
            $row = $rows[0] ?? null;
        }

        return is_array($row) ? (int)$row['id'] : null;
    }

    /**
     * @param \App\Model\Entity\Cart $cart Cart.
     * @return bool
     */
    private function cartHasItems(Cart $cart): bool
    {
        foreach ($cart->cart_items ?? [] as $item) {
            if ((int)$item->quantity > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restock consumed reservations when the goods have not shipped.
     *
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function restockIfUnshipped(SalesOrder $order, int $actorUserId): void
    {
        if (
            $order->status === SalesOrder::STATUS_DISPATCHED
            || $order->status === SalesOrder::STATUS_COMPLETED
        ) {
            return;
        }

        $this->connection()->transactional(function () use ($order, $actorUserId): void {
            $reservations = $this->fetchTable('StockReservations');
            $consumed = $reservations->find()
                ->where(['sales_order_id' => $order->id, 'status' => 'consumed'])
                ->all();
            foreach ($consumed as $reservation) {
                $qty = (int)$reservation->quantity;
                if ($qty > 0) {
                    $this->ledger->applyInTransaction(
                        (int)$reservation->product_variant_id,
                        (int)$reservation->inventory_location_id,
                        'return',
                        $qty,
                        0,
                        'sales_order',
                        (int)$order->id,
                        'Restock refunded ' . $order->order_number,
                        $actorUserId,
                    );
                }
                $reservation->status = 'returned';
                $reservation->released_at = DateTime::now('UTC');
                $reservations->saveOrFail($reservation);
            }
            $this->releaseReservations((int)$order->id, $actorUserId, $order->order_number);
        });
    }

    /**
     * @param array<string, mixed> $address Posted shipping fields.
     * @return void
     */
    private function assertCheckoutAddress(array $address): void
    {
        foreach (['recipient_name', 'line1', 'suburb', 'state', 'postcode'] as $field) {
            if (trim((string)($address[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Please complete the delivery address.');
            }
        }
        $state = strtoupper(trim((string)$address['state']));
        if (!AustralianStates::isValid($state)) {
            throw new InvalidArgumentException('Please choose an Australian state or territory.');
        }
        $postcode = trim((string)$address['postcode']);
        if (!preg_match('/^\d{4}$/', $postcode)) {
            throw new InvalidArgumentException('Please enter a four-digit postcode.');
        }
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param array<string, mixed> $address Shipping fields.
     * @return void
     */
    private function snapshotAddress(SalesOrder $order, array $address): void
    {
        $addresses = $this->fetchTable('OrderAddresses');
        foreach (['shipping', 'billing'] as $type) {
            $row = $addresses->newEmptyEntity();
            $row->set('sales_order_id', $order->id);
            $row->set('address_type', $type);
            $row->set('recipient_name', trim((string)$address['recipient_name']));
            $row->set('company', $this->nullableString($address['company'] ?? null));
            $row->set('line1', trim((string)$address['line1']));
            $row->set('line2', $this->nullableString($address['line2'] ?? null));
            $row->set('suburb', trim((string)$address['suburb']));
            $row->set('state', strtoupper(trim((string)$address['state'])));
            $row->set('postcode', trim((string)$address['postcode']));
            $row->set('country_code', 'AU');
            $row->set('phone', $this->nullableString($address['phone'] ?? null));
            $addresses->saveOrFail($row);
        }
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

        /** @var \App\Model\Entity\Customer $customer */
        $customer = $customers->newEmptyEntity();
        $customer->first_name = $first !== '' ? $first : ($email !== '' ? strtok($email, '@') : 'Customer');
        $customer->last_name = $last !== '' ? $last : null;
        $customer->email = $email !== '' ? $email : null;
        $customer->phone = $phone !== '' ? $phone : null;
        $customer->status = 'active';
        $customer->source = $channel;
        $customer->set('customer_type', 'individual');
        $customer->set('display_name', trim($customer->first_name . ' ' . (string)$customer->last_name));
        $customer->set('tags', []);
        $customer->set('metadata', ['created_via' => 'admin_order']);
        $customers->saveOrFail($customer);

        return (int)$customer->id;
    }

    /**
     * Release every active reservation for a cancelled order.
     *
     * @param int $orderId Order id.
     * @param int $actorUserId Acting staff user.
     * @param string $orderNumber Order number for the ledger note.
     * @return void
     */
    private function releaseReservations(int $orderId, int $actorUserId, string $orderNumber): void
    {
        $reservations = $this->fetchTable('StockReservations');
        $this->connection()->execute(
            'SELECT id FROM stock_reservations WHERE sales_order_id = ? AND status = ? FOR UPDATE',
            [$orderId, 'active'],
        );
        $active = $reservations->find()
            ->where(['sales_order_id' => $orderId, 'status' => 'active'])
            ->all();
        foreach ($active as $reservation) {
            $qty = (int)$reservation->quantity;
            if ($qty > 0) {
                $this->ledger->applyInTransaction(
                    (int)$reservation->product_variant_id,
                    (int)$reservation->inventory_location_id,
                    'reservation_release',
                    0,
                    -$qty,
                    'sales_order',
                    $orderId,
                    'Release reservation for cancelled ' . $orderNumber,
                    $actorUserId,
                );
            }
            $reservation->status = 'released';
            $reservation->released_at = DateTime::now('UTC');
            $reservation->release_or_sale_movement_id = $this->ledger->lastInsertId();
            $reservations->saveOrFail($reservation);
        }
    }

    /**
     * Deduct on-hand and reserved together when an order is dispatched or
     * completed. Already-consumed rows are skipped so dispatch then complete
     * cannot double-count.
     *
     * @param int $orderId Order id.
     * @param int $actorUserId Acting staff user.
     * @param string $orderNumber Order number for the ledger note.
     * @return void
     */
    private function consumeReservations(int $orderId, int $actorUserId, string $orderNumber): void
    {
        $reservations = $this->fetchTable('StockReservations');
        $this->connection()->execute(
            'SELECT id FROM stock_reservations WHERE sales_order_id = ? AND status = ? FOR UPDATE',
            [$orderId, 'active'],
        );
        $active = $reservations->find()
            ->where(['sales_order_id' => $orderId, 'status' => 'active'])
            ->all();
        foreach ($active as $reservation) {
            $qty = (int)$reservation->quantity;
            if ($qty > 0) {
                $this->ledger->applyInTransaction(
                    (int)$reservation->product_variant_id,
                    (int)$reservation->inventory_location_id,
                    'sale',
                    -$qty,
                    -$qty,
                    'sales_order',
                    $orderId,
                    'Fulfil reservation for ' . $orderNumber,
                    $actorUserId,
                );
            }
            $reservation->status = 'consumed';
            $reservation->consumed_at = DateTime::now('UTC');
            $reservation->release_or_sale_movement_id = $this->ledger->lastInsertId();
            $reservations->saveOrFail($reservation);
        }
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
     * Status buttons that remain legal after payment-status guards.
     *
     * @param \Cake\Datasource\EntityInterface $order Order.
     * @return array<int, string>
     */
    public static function allowedNextStatuses(EntityInterface $order): array
    {
        $status = (string)$order->get('status');
        $paymentStatus = (string)$order->get('payment_status');
        $next = self::TRANSITIONS[$status] ?? [];
        if ($paymentStatus === 'refunded') {
            $next = array_values(array_intersect($next, [SalesOrder::STATUS_CANCELLED]));
        }
        $openWebCheckout = (string)$order->get('source_channel') === SalesOrder::CHANNEL_WEB
            && $status === SalesOrder::STATUS_DRAFT
            && in_array($paymentStatus, ['pending', 'failed'], true);
        if ($openWebCheckout) {
            $next = array_values(array_intersect($next, [SalesOrder::STATUS_CANCELLED]));
        }

        return $next;
    }

    /**
     * Cancel leftover Stripe PaymentIntents for an unpaid website checkout.
     *
     * @param \Cake\Datasource\EntityInterface $order Locked order.
     * @return string none|canceled|already_canceled|already_succeeded|skipped
     */
    private function cancelOpenStripeIntents(EntityInterface $order): string
    {
        $openWebCheckout = (string)$order->get('source_channel') === SalesOrder::CHANNEL_WEB
            && (string)$order->get('status') === SalesOrder::STATUS_DRAFT
            && in_array((string)$order->get('payment_status'), ['pending', 'failed'], true);
        if (!$openWebCheckout) {
            return 'none';
        }

        $intentIds = [];
        $payments = $this->fetchTable('Payments')->find()
            ->select(['provider_payment_id'])
            ->where([
                'sales_order_id' => (int)$order->get('id'),
                'provider' => 'stripe',
                'status IN' => ['pending', 'failed'],
            ])
            ->all();
        foreach ($payments as $payment) {
            $intentId = (string)$payment->get('provider_payment_id');
            if ($intentId !== '') {
                $intentIds[] = $intentId;
            }
        }
        $meta = $order->get('metadata');
        if (!is_array($meta)) {
            $meta = [];
        }
        $metaIntent = (string)($meta['stripe_payment_intent_id'] ?? '');
        if ($metaIntent !== '') {
            $intentIds[] = $metaIntent;
        }
        $intentIds = array_values(array_unique($intentIds));
        if ($intentIds === []) {
            return 'none';
        }

        $state = 'none';
        foreach ($intentIds as $intentId) {
            try {
                $result = $this->payments()->cancelPaymentIntent($intentId);
            } catch (PaymentUncertainException $exception) {
                throw $exception;
            } catch (InvalidArgumentException $exception) {
                if (str_contains($exception->getMessage(), 'not configured')) {
                    return 'skipped';
                }
                throw $exception;
            }
            if ($result === 'already_succeeded') {
                return 'already_succeeded';
            }
            $state = $result;
        }

        return $state;
    }

    /**
     * Keep an in-flight Stripe capture out of the next expired-hold scan.
     *
     * @param \Cake\Datasource\EntityInterface $order Locked unpaid checkout.
     * @return void
     */
    private function deferHoldForInFlightCapture(EntityInterface $order): void
    {
        $meta = $order->get('metadata');
        if (!is_array($meta)) {
            $meta = [];
        }
        $deferrals = (int)($meta['stripe_hold_deferrals'] ?? 0) + 1;
        $exhausted = $deferrals >= self::MAX_HOLD_DEFERRALS;
        $meta['stripe_hold_deferrals'] = $deferrals;
        $meta['stripe_reconciliation'] = $exhausted ? 'hold_exhausted' : 'awaiting_capture';
        $limit = DateTime::now('UTC')->addMinutes($exhausted ? 24 * 60 : 15);
        $order->set('metadata', $meta);
        $order->set('hold_expires_at', $limit);
        $this->fetchTable('SalesOrders')->saveOrFail($order);
        $this->fetchTable('StockReservations')->updateAll(
            ['expires_at' => $limit],
            ['sales_order_id' => (int)$order->get('id'), 'status' => 'active'],
        );
        if ($exhausted) {
            $this->alertHoldExhausted((int)$order->get('id'), (string)$order->get('order_number'));
        }
    }

    /**
     * @param int $orderId Order id.
     * @param string $orderNumber Order number.
     * @return void
     */
    private function alertHoldExhausted(int $orderId, string $orderNumber): void
    {
        $alerts = $this->fetchTable('PaymentReconciliationAlerts');
        if ($alerts->exists(['event_id' => 'hold-exhausted-' . $orderId])) {
            return;
        }
        $row = $alerts->newEmptyEntity();
        $row->set('event_id', 'hold-exhausted-' . $orderId);
        $row->set('sales_order_id', $orderId);
        $row->set('reason', 'Checkout hold exhausted while Stripe is still processing');
        $row->set('detail', 'Order ' . $orderNumber . ' kept a reservation after repeated processing holds.');
        $row->set('payload_digest', hash('sha256', 'hold-exhausted-' . $orderId));
        $alerts->saveOrFail($row);
    }

    /**
     * @return \App\Service\Payments\PaymentGatewayInterface
     */
    private function payments(): PaymentGatewayInterface
    {
        return $this->payments ?? PaymentGatewayFactory::create();
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('SalesOrders')->getConnection();
    }
}
