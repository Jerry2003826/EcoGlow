<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentGatewayFactory;
use App\Service\Payments\RefundService;
use Cake\Http\Response;
use Cake\I18n\Date;
use InvalidArgumentException;

/**
 * Staff order recording and fulfilment.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class OrdersController extends AdminController
{
    /**
     * @var \App\Service\Orders\OrderService
     */
    private OrderService $orders;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->orders = new OrderService(new InventoryLedger());
    }

    /**
     * Filtered, paginated order list.
     *
     * @return void
     */
    public function index(): void
    {
        $today = Date::now('Australia/Melbourne');
        $status = (string)$this->request->getQuery('status', '');
        $channel = (string)$this->request->getQuery('channel', '');
        $q = trim((string)$this->request->getQuery('q', ''));
        $from = (string)$this->request->getQuery('from', '');
        $to = (string)$this->request->getQuery('to', '');

        $query = $this->fetchTable('SalesOrders')->find()
            ->contain(['Customers'])
            ->orderBy(['SalesOrders.placed_at' => 'DESC', 'SalesOrders.id' => 'DESC']);

        if ($status !== '' && isset(SalesOrder::statusLabels()[$status])) {
            $query->where(['SalesOrders.status' => $status]);
        }
        if ($channel !== '' && isset(SalesOrder::channelLabels()[$channel])) {
            $query->where(['SalesOrders.source_channel' => $channel]);
        }
        if ($from !== '') {
            $query->where(['DATE(COALESCE(SalesOrders.placed_at, SalesOrders.created)) >=' => $from]);
        }
        if ($to !== '') {
            $query->where(['DATE(COALESCE(SalesOrders.placed_at, SalesOrders.created)) <=' => $to]);
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'SalesOrders.order_number LIKE' => $like,
                    'SalesOrders.guest_name LIKE' => $like,
                    'Customers.first_name LIKE' => $like,
                    'Customers.last_name LIKE' => $like,
                    'Customers.email LIKE' => $like,
                ],
            ]);
        }

        $salesOrders = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('salesOrders', 'status', 'channel', 'q', 'from', 'to', 'today'));
    }

    /**
     * Order detail with lines, totals, history and notes.
     *
     * @param string|null $id Order id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $salesOrder = $this->fetchTable('SalesOrders')->get($this->recordId($id), finder: 'detail');
        $today = Date::now('Australia/Melbourne');
        $nextStatuses = OrderService::TRANSITIONS[$salesOrder->status] ?? [];
        $canSeeContact = $this->canViewCustomerContact();
        $existingInvoice = $this->fetchTable('Invoices')->find()
            ->where([
                'sales_order_id' => $salesOrder->id,
                'status !=' => 'void',
            ])
            ->first();
        $stripePayment = $this->fetchTable('Payments')->find()
            ->where([
                'sales_order_id' => $salesOrder->id,
                'provider' => 'stripe',
                'status' => 'captured',
            ])
            ->first();

        $this->set(compact(
            'salesOrder',
            'today',
            'nextStatuses',
            'canSeeContact',
            'existingInvoice',
            'stripePayment',
        ));
    }

    /**
     * Single-page staff order form.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $ledger = new InventoryLedger();
        $ledger->ensureDefaultLocation();

        if ($this->request->is('post')) {
            try {
                $order = $this->orders->create($this->postedOrder(), $this->actorId());
                $warnings = $order->metadata['stock_warnings'] ?? [];
                if ($warnings) {
                    $this->Flash->warning(__(
                        'Order {0} was saved. Some lines were short of stock: {1}',
                        $order->order_number,
                        implode('; ', $warnings),
                    ));
                } else {
                    $this->Flash->success(__('Order {0} was saved.', $order->order_number));
                }

                return $this->redirect(['action' => 'view', $order->id]);
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $variants = $this->fetchTable('ProductVariants')->find()
            ->contain(['Products'])
            ->where(['ProductVariants.is_active' => true])
            ->orderBy(['Products.name' => 'ASC', 'ProductVariants.sku' => 'ASC'])
            ->all();
        $customers = $this->fetchTable('Customers')->find()
            ->where(['deleted IS' => null])
            ->orderBy(['first_name' => 'ASC'])
            ->limit(200)
            ->all();
        $availability = $this->availabilityByVariant();

        $this->set(compact('variants', 'customers', 'availability'));

        return null;
    }

    /**
     * Advance status. Dispatch requires orders.dispatch; other moves use
     * orders.manage.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null
     */
    public function updateStatus(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $order = $this->fetchTable('SalesOrders')->get($this->recordId($id));
        $to = (string)$this->request->getData('status');
        if ($to === SalesOrder::STATUS_DISPATCHED) {
            $this->requirePermission('orders.dispatch');
        } else {
            $this->requirePermission('orders.manage');
        }

        try {
            $this->orders->changeStatus(
                $order,
                $to,
                $this->actorId(),
                $this->nullablePosted('note'),
            );
            $this->Flash->success(__('Order status updated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * In-place promised delivery date.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null
     */
    public function updatePromisedDate(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $order = $this->fetchTable('SalesOrders')->get($this->recordId($id));
        $this->orders->updatePromisedDate($order, $this->nullablePosted('promised_delivery_date'));
        $this->Flash->success(__('Promised delivery date updated.'));

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * Append an internal note.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null
     */
    public function addNote(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $order = $this->fetchTable('SalesOrders')->get($this->recordId($id));
        $body = trim((string)$this->request->getData('body'));
        if ($body === '') {
            $this->Flash->error(__('Write a note before saving.'));

            return $this->redirect(['action' => 'view', $order->id]);
        }
        $this->orders->addNote($order, $body, $this->actorId());
        $this->Flash->success(__('Note saved.'));

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * Refund a captured Stripe payment. Restocks only if the goods have not shipped.
     *
     * @param string|null $id Order id.
     * @return \Cake\Http\Response|null
     */
    public function refund(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->requirePermission('refunds.process');
        $order = $this->fetchTable('SalesOrders')->get($this->recordId($id));
        try {
            $refunds = new RefundService(
                $this->orders,
                PaymentGatewayFactory::create(),
            );
            $refunds->refundOrder($order, $this->actorId());
            $this->Flash->success(__('Refund recorded. Stock was returned if the order had not shipped.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $order->id]);
    }

    /**
     * JSON product search for the order form.
     *
     * @return \Cake\Http\Response
     */
    public function searchProducts(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q', ''));
        $query = $this->fetchTable('ProductVariants')->find()
            ->contain(['Products'])
            ->where(['ProductVariants.is_active' => true])
            ->orderBy(['Products.name' => 'ASC'])
            ->limit(20);
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'ProductVariants.sku LIKE' => $like,
                    'ProductVariants.name LIKE' => $like,
                    'Products.name LIKE' => $like,
                ],
            ]);
        }
        $availability = $this->availabilityByVariant();
        $payload = [];
        foreach ($query as $variant) {
            $payload[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->product->name ?? $variant->name,
                'variant' => $variant->name,
                'price_cents' => (int)$variant->price_cents,
                'quantity_available' => $availability[(int)$variant->id] ?? 0,
            ];
        }

        return $this->json($payload);
    }

    /**
     * JSON customer search for the order form.
     *
     * @return \Cake\Http\Response
     */
    public function searchCustomers(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q', ''));
        $query = $this->fetchTable('Customers')->find()
            ->where(['deleted IS' => null])
            ->orderBy(['first_name' => 'ASC'])
            ->limit(20);
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'first_name LIKE' => $like,
                    'last_name LIKE' => $like,
                    'email LIKE' => $like,
                    'phone LIKE' => $like,
                ],
            ]);
        }
        $payload = [];
        foreach ($query as $customer) {
            $payload[] = [
                'id' => $customer->id,
                'label' => $customer->label,
            ];
        }

        return $this->json($payload);
    }

    /**
     * Shape the order form into the service payload.
     *
     * @return array<string, mixed>
     */
    private function postedOrder(): array
    {
        $data = $this->request->getData();
        $lines = [];
        foreach ((array)($data['lines'] ?? []) as $line) {
            if (!is_array($line)) {
                continue;
            }
            $variantId = (int)($line['product_variant_id'] ?? 0);
            $quantity = (int)($line['quantity'] ?? 0);
            if ($variantId < 1 || $quantity < 1) {
                continue;
            }
            $lines[] = [
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        return [
            'customer_id' => $data['customer_id'] ?? null,
            'customer_first_name' => $data['customer_first_name'] ?? null,
            'customer_last_name' => $data['customer_last_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'guest_name' => trim(
                (string)($data['customer_first_name'] ?? '')
                . ' '
                . (string)($data['customer_last_name'] ?? ''),
            ),
            'guest_email' => $data['customer_email'] ?? null,
            'guest_phone' => $data['customer_phone'] ?? null,
            'source_channel' => $data['source_channel'] ?? '',
            'external_source_reference' => $data['external_source_reference'] ?? null,
            'promised_delivery_date' => $data['promised_delivery_date'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'lines' => $lines,
        ];
    }

    /**
     * Available units keyed by variant, summed across locations.
     *
     * @return array<int, int>
     */
    private function availabilityByVariant(): array
    {
        $rows = $this->fetchTable('InventoryBalances')->find()
            ->select([
                'product_variant_id',
                'available' => $this->fetchTable('InventoryBalances')->find()->func()->sum('quantity_available'),
            ])
            ->groupBy(['product_variant_id'])
            ->all();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->product_variant_id] = (int)$row->get('available');
        }

        return $map;
    }

    /**
     * @param mixed $payload JSON-serializable body.
     * @return \Cake\Http\Response
     */
    private function json(mixed $payload): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Empty posted strings become null.
     *
     * @param string $field Request field.
     * @return string|null
     */
    private function nullablePosted(string $field): ?string
    {
        $value = trim((string)$this->request->getData($field));

        return $value === '' ? null : $value;
    }
}
