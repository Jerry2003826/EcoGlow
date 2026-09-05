<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class InvoicesController extends AdminController
{
    protected function requirePermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'invoices.issue'
            )
        ) {
            throw new ForbiddenException();
        }
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requirePermission();

        $invoices = $this->fetchTable('Invoices')
            ->find()
            ->contain(['Customers', 'SalesOrders'])
            ->orderBy(['Invoices.invoice_number' => 'DESC'])
            ->all();

        $this->set(compact('invoices'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requirePermission();

        $invoice = $this->fetchTable('Invoices')->get(
            $id,
            contain: ['Customers', 'SalesOrders']
        );

        $this->set(compact('invoice'));
    }

    public function add()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requirePermission();

        $invoice = $this->fetchTable('Invoices')->newEmptyEntity();

        $orders = $this->fetchTable('SalesOrders')
            ->find()
            ->orderBy(['SalesOrders.order_number' => 'DESC'])
            ->all();

        if ($this->request->is('post')) {
            $order = $this->fetchTable('SalesOrders')->get(
                (int)$this->request->getData('sales_order_id')
            );

            $last = $this->fetchTable('Invoices')
                ->find()
                ->orderBy(['Invoices.id' => 'DESC'])
                ->first();

            $next = 4001;

            if ($last && preg_match('/INV-(\d+)/', (string)$last->invoice_number, $m)) {
                $next = ((int)$m[1]) + 1;
            }

            $invoice = $this->fetchTable('Invoices')->patchEntity($invoice, [
                'invoice_number' => 'INV-' . $next,
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => 'issued',
                'source_channel' => $order->source_channel,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'tax_cents' => $order->tax_cents,
                'grand_total_cents' => $order->grand_total_cents,
                'issued_by_user_id' => (int)$identity->getIdentifier(),
            ]);

            if ($this->fetchTable('Invoices')->save($invoice)) {
                return $this->redirect(['action' => 'view', $invoice->id]);
            }
        }

        $this->set(compact('invoice', 'orders'));
    }
}
