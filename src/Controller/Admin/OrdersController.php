<?php
// src/Controller/Admin/OrdersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class OrdersController extends AdminController
{
    protected function requireOrdersViewPermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'orders.view'
            )
        ) {
            throw new ForbiddenException('Forbidden');
        }
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requireOrdersViewPermission();

        $q = trim((string)$this->request->getQuery('q'));

        $query = $this->fetchTable('Orders')
            ->find()
            ->contain(['Guests'])
            ->orderBy([
                'OrderDate' => 'DESC',
                'OrderId' => 'DESC',
            ]);

        if ($q !== '') {
            $orders = $query->all()->filter(function ($order) use ($q) {
                $guestName = $order->guest->GuestName ?? '';
                $guestEmail = $order->guest->Email ?? '';

                return str_contains((string)$order->OrderId, $q)
                    || stripos($guestName, $q) !== false
                    || stripos($guestEmail, $q) !== false;
            });
        } else {
            $orders = $query->all();
        }

        $this->set(compact('orders', 'q'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $this->requireOrdersViewPermission();

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
            ->where([
                'OrderId' => $order->OrderId,
            ])
            ->first();

        $receipt = null;

        if ($payment) {
            $receipt = $this->fetchTable('Receipts')
                ->find()
                ->where([
                    'PaymentId' => $payment->PaymentId,
                ])
                ->first();
        }

        $delivery = $this->fetchTable('Delivery')
            ->find()
            ->where([
                'OrderId' => $order->OrderId,
            ])
            ->first();

        $this->set(compact(
            'order',
            'payment',
            'receipt',
            'delivery'
        ));
    }
}
