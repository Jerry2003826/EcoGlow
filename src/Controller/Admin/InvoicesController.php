<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Invoice;
use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Invoices\InvoiceService;
use App\Service\Orders\OrderService;
use App\Service\OutboundQueue;
use App\Service\Payments\PaymentGatewayFactory;
use App\Service\Payments\RefundService;
use Cake\Http\Response;
use Cake\I18n\Date;
use InvalidArgumentException;

/**
 * GST invoices issued from recorded sales.
 */
class InvoicesController extends AdminController
{
    /**
     * @var \App\Service\Invoices\InvoiceService
     */
    private InvoiceService $invoices;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->invoices = new InvoiceService(new InventoryLedger(), new OutboundQueue());
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $today = Date::now('Australia/Melbourne');
        $status = (string)$this->request->getQuery('status', '');
        $q = trim((string)$this->request->getQuery('q', ''));

        $query = $this->fetchTable('Invoices')->find()
            ->contain(['Customers'])
            ->leftJoinWith('Customers')
            ->orderBy(['Invoices.issue_date' => 'DESC', 'Invoices.id' => 'DESC']);
        if ($status === Invoice::STATUS_OVERDUE) {
            $query->where([
                'Invoices.status NOT IN' => [
                    Invoice::STATUS_PAID,
                    Invoice::STATUS_CREDITED,
                    Invoice::STATUS_VOID,
                ],
                'Invoices.due_date <' => $today->format('Y-m-d'),
                'Invoices.balance_due_cents >' => 0,
            ]);
        } elseif ($status !== '' && isset(Invoice::statusLabels()[$status])) {
            $query->where(['Invoices.status' => $status]);
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'Invoices.invoice_number LIKE' => $like,
                    'Customers.first_name LIKE' => $like,
                    'Customers.last_name LIKE' => $like,
                    'Customers.email LIKE' => $like,
                ],
            ]);
        }

        $invoices = $this->paginate($query, ['limit' => 20]);
        $statusCounts = $this->countByField('Invoices', 'status');
        $overdueCount = $this->fetchTable('Invoices')->find()
            ->where([
                'status NOT IN' => [
                    Invoice::STATUS_PAID,
                    Invoice::STATUS_CREDITED,
                    Invoice::STATUS_VOID,
                ],
                'due_date <' => $today->format('Y-m-d'),
                'balance_due_cents >' => 0,
            ])
            ->count();

        $this->set(compact('invoices', 'status', 'q', 'today', 'statusCounts', 'overdueCount'));
    }

    /**
     * @param string|null $id Invoice id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $invoice = $this->fetchTable('Invoices')->get($this->recordId($id), finder: 'detail');
        $today = Date::now('Australia/Melbourne');
        $payments = [];
        $stripePayment = null;
        if ($invoice->sales_order_id) {
            $payments = $this->fetchTable('Payments')->find()
                ->where(['sales_order_id' => $invoice->sales_order_id])
                ->orderBy(['Payments.created' => 'ASC'])
                ->all();
            foreach ($payments as $payment) {
                if ($payment->provider === 'stripe' && $payment->status === 'captured') {
                    $stripePayment = $payment;
                    break;
                }
            }
        }
        $blocksManualPayment = false;
        if ($invoice->sales_order_id) {
            $order = $this->fetchTable('SalesOrders')->get((int)$invoice->sales_order_id);
            $blocksManualPayment = (string)$order->get('source_channel') === SalesOrder::CHANNEL_WEB
                && (string)$order->get('status') === SalesOrder::STATUS_DRAFT
                && in_array((string)$order->get('payment_status'), ['pending', 'failed'], true);
        }
        $this->set(compact('invoice', 'today', 'payments', 'stripePayment', 'blocksManualPayment'));
    }

    /**
     * @param string|null $orderId Sales order id.
     * @return \Cake\Http\Response|null
     */
    public function createFromOrder(?string $orderId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $order = $this->fetchTable('SalesOrders')->get($this->recordId($orderId), finder: 'detail');
        try {
            $invoice = $this->invoices->createFromOrder($order, $this->actorId());
            $this->Flash->success(__('Invoice {0} was issued from the order snapshot.', $invoice->invoice_number));

            return $this->redirect(['action' => 'view', $invoice->id]);
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());

            return $this->redirect(['controller' => 'Orders', 'action' => 'view', $order->id]);
        }
    }

    /**
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     */
    public function send(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $invoice = $this->fetchTable('Invoices')->get($this->recordId($id), finder: 'detail');
        try {
            $this->invoices->send($invoice, $this->actorId());
            $this->Flash->success(__('Invoice queued for email. It will send when the mail worker runs.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $invoice->id]);
    }

    /**
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     */
    public function recordPayment(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $invoice = $this->fetchTable('Invoices')->get($this->recordId($id));
        try {
            $this->invoices->recordPayment($invoice, $this->postedCents('amount'), $this->actorId());
            $this->Flash->success(__('Payment recorded.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $invoice->id]);
    }

    /**
     * Refund the captured Stripe payment on the related order.
     *
     * @param string|null $id Invoice id.
     * @return \Cake\Http\Response|null
     */
    public function refund(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->requirePermission('refunds.process');
        $invoice = $this->fetchTable('Invoices')->get($this->recordId($id));
        if (!$invoice->sales_order_id) {
            $this->Flash->error(__('This invoice is not linked to an order.'));

            return $this->redirect(['action' => 'view', $invoice->id]);
        }
        $order = $this->fetchTable('SalesOrders')->get((int)$invoice->sales_order_id);
        try {
            $refunds = new RefundService(
                new OrderService(new InventoryLedger()),
                PaymentGatewayFactory::create(),
            );
            $refunds->refundOrder($order, $this->actorId());
            $this->Flash->success(__('Refund recorded.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'view', $invoice->id]);
    }

    /**
     * Parse a dollars-and-cents string into integer cents without floats.
     *
     * @param string $field Posted field name.
     * @return int
     */
    private function postedCents(string $field): int
    {
        $raw = trim((string)$this->request->getData($field));
        if ($raw === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $raw)) {
            return 0;
        }
        $parts = explode('.', $raw, 2);
        $dollars = (int)$parts[0];
        $fraction = isset($parts[1]) ? str_pad(substr($parts[1], 0, 2), 2, '0') : '00';

        return $dollars * 100 + (int)$fraction;
    }
}
