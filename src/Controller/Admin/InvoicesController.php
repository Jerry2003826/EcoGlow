<?php
// src/Controller/Admin/InvoicesController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class InvoicesController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('invoices.issue');

        $invoices = $this->fetchTable('Invoices')
            ->find()
            ->contain(['Orders.Guests'])
            ->orderBy(['InvoiceId' => 'DESC'])
            ->all();

        $orders = $this->fetchTable('Orders')
            ->find()
            ->contain(['Guests', 'Payments'])
            ->orderBy(['Orders.OrderId' => 'DESC'])
            ->all();

        $this->set(compact('invoices', 'orders'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('invoices.issue');

        $invoice = $this->fetchTable('Invoices')->get(
            (int)$id,
            contain: ['Orders.Guests', 'Orders.OrderItems.Products']
        );

        $total = (float)$invoice->TotalAmount;
        $gst = round($total * (10 / 110), 2);
        $exGst = round($total - $gst, 2);

        $this->set(compact('invoice', 'gst', 'exGst'));
    }

    public function generate(int|string $orderId)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('invoices.issue');

        $order = $this->fetchTable('Orders')->get(
            (int)$orderId,
            contain: ['Payments']
        );

        $existing = $this->fetchTable('Invoices')
            ->find()
            ->where(['OrderId' => $order->OrderId])
            ->first();

        if ($existing) {
            return $this->redirect([
                'action' => 'view',
                $existing->InvoiceId,
            ]);
        }

        $amount = 0.0;

        if (!empty($order->payment)) {
            $amount = (float)$order->payment->Amount;
        } elseif (!empty($order->payments)) {
            foreach ($order->payments as $payment) {
                $amount += (float)$payment->Amount;
            }
        }

        $invoice = $this->fetchTable('Invoices')->newEntity([
            'OrderId' => $order->OrderId,
            'TotalAmount' => $amount,
        ]);

        $saved = $this->fetchTable('Invoices')->saveOrFail($invoice);

        $this->Flash->success('Tax invoice created for Order #' . $order->OrderId);

        return $this->redirect([
            'action' => 'view',
            $saved->InvoiceId,
        ]);
    }
}
