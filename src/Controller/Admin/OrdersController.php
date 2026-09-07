<?php
// src/Controller/Admin/OrdersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class OrdersController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('orders.view');

        $q = trim((string)$this->request->getQuery('q'));
        $type = trim((string)$this->request->getQuery('type'));

        $query = $this->fetchTable('Orders')
            ->find()
            ->contain(['Guests', 'ShoppingCarts'])
            ->orderBy([
                'OrderDate' => 'DESC',
                'OrderId' => 'DESC',
            ]);

        $orders = $query->all();

        if ($q !== '') {
            $orders = $orders->filter(function ($order) use ($q) {
                $guestName = $order->guest->GuestName ?? '';
                $guestEmail = $order->guest->Email ?? '';

                return str_contains((string)$order->OrderId, $q)
                    || stripos($guestName, $q) !== false
                    || stripos($guestEmail, $q) !== false;
            });
        }

        if ($type === 'installation') {
            $orders = $orders->filter(function ($order) {
                return !empty($order->shopping_cart)
                    && (int)$order->shopping_cart->InstallOptIn === 1;
            });
        } elseif ($type === 'product') {
            $orders = $orders->filter(function ($order) {
                return empty($order->shopping_cart)
                    || (int)$order->shopping_cart->InstallOptIn !== 1;
            });
        }

        $repairs = [];

        if ($type === '' || $type === 'repair') {
            $repairQuery = $this->fetchTable('ServiceRequests')
                ->find()
                ->where(['RequestType' => 'repair'])
                ->orderBy(['ServiceRequestId' => 'DESC']);

            if ($q !== '') {
                $repairQuery->where([
                    'OR' => [
                        'Description LIKE' => '%' . $q . '%',
                        'RequestType LIKE' => '%' . $q . '%',
                    ],
                ]);
            }

            $repairs = $repairQuery->all();
        }

        $this->set(compact('orders', 'repairs', 'q', 'type'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('orders.view');

        $order = $this->fetchTable('Orders')->get(
            (int)$id,
            contain: [
                'Guests',
                'OrderItems.Products',
                'ShoppingCarts',
            ]
        );

        $payment = $this->fetchTable('Payments')
            ->find()
            ->where(['OrderId' => $order->OrderId])
            ->first();

        $receipt = null;

        if ($payment) {
            $receipt = $this->fetchTable('Receipts')
                ->find()
                ->where(['PaymentId' => $payment->PaymentId])
                ->first();
        }

        $delivery = $this->fetchTable('Delivery')
            ->find()
            ->where(['OrderId' => $order->OrderId])
            ->first();

        $services = $this->fetchTable('ServiceRequests')
            ->find()
            ->where(['Description LIKE' => '%Order #' . $order->OrderId . '%'])
            ->all();

        $this->set(compact(
            'order',
            'payment',
            'receipt',
            'delivery',
            'services'
        ));
    }

    public function updateDelivery(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('orders.view');

        $this->request->allowMethod(['post', 'put', 'patch']);

        $delivery = $this->fetchTable('Delivery')
            ->find()
            ->where(['OrderId' => (int)$id])
            ->first();

        if (!$delivery) {
            $this->Flash->error('No delivery record for this order.');

            return $this->redirect(['action' => 'view', $id]);
        }

        $delivery->DeliveryAddress = (string)$this->request->getData(
            'DeliveryAddress',
            $delivery->DeliveryAddress
        );
        $delivery->DeliveryStatus = (string)$this->request->getData(
            'DeliveryStatus',
            $delivery->DeliveryStatus
        );

        $date = $this->request->getData('DeliveryDate');

        if ($date) {
            $delivery->DeliveryDate = $date;
        }

        $this->fetchTable('Delivery')->saveOrFail($delivery);

        if ($delivery->DeliveryStatus === 'delivered') {
            $order = $this->fetchTable('Orders')->get((int)$id);
            $order->Status = 'delivered';
            $this->fetchTable('Orders')->save($order);
        }

        $this->Flash->success('Delivery details updated.');

        return $this->redirect(['action' => 'view', $id]);
    }

    public function addRepair()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('orders.view');

        if ($this->request->is('post')) {
            $request = $this->fetchTable('ServiceRequests')->newEntity([
                'RequestType' => 'repair',
                'Description' => $this->request->getData('Description'),
                'RequestedDate' => $this->request->getData('RequestedDate') ?: date('Y-m-d H:i:s'),
            ]);

            if ($this->fetchTable('ServiceRequests')->save($request)) {
                $this->Flash->success('Repair booking saved.');

                return $this->redirect([
                    'action' => 'index',
                    '?' => ['type' => 'repair'],
                ]);
            }

            $this->Flash->error('Repair booking could not be saved.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
