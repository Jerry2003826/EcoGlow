<?php
// src/Controller/OrdersController.php

declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;

class OrdersController extends AppController
{
    public function receipt(int $id)
    {
        $order = $this->fetchTable('Orders')->get($id, contain: ['Delivery']);

        $identity = $this->request->getAttribute('identity');
        $role = $identity ? (string)$identity->get('UserRole') : '';
        $canView = in_array($role, ['admin', 'staff'], true);

        if (!$canView && !empty($order->CustomerId) && $identity) {
            $canView = $this->fetchTable('Customers')->exists([
                'CustomerId' => $order->CustomerId,
                'UserId' => (int)$identity->getIdentifier(),
            ]);
        }

        if (!$canView && empty($order->CustomerId) && !empty($order->GuestId)) {
            $canView = $this->request->getSession()
                ->read('GuestReceiptOrders.' . (int)$order->OrderId) === true;
        }

        if (!$canView) {
            throw new NotFoundException('That order could not be found.');
        }

        $guest = null;
        $customer = null;

        if (!empty($order->CustomerId)) {
            $customer = $this->fetchTable('Customers')->get($order->CustomerId);
        } elseif (!empty($order->GuestId)) {
            $guest = $this->fetchTable('Guests')->get($order->GuestId);
        }

        $items = $this->fetchTable('OrderItems')
            ->find()
            ->contain(['Products'])
            ->where([
                'OrderId' => $order->OrderId,
            ])
            ->all();

        $productsTotal = 0.00;

        foreach ($items as $item) {
            $productsTotal += (
                (float)$item->UnitPrice * (int)$item->Quantity
            );
        }

        $cart = $this->fetchTable('ShoppingCarts')
            ->find()
            ->where([
                'CartId' => $order->CartId,
            ])
            ->first();

        $installFee = 0.00;

        if ($cart && (int)$cart->InstallOptIn === 1) {
            $installFee = (float)$cart->InstallFee;
        }

        $grandTotal = $productsTotal + $installFee;

        $this->set(compact(
            'order',
            'guest',
            'customer',
            'items',
            'productsTotal',
            'installFee',
            'grandTotal'
        ));
    }
}
