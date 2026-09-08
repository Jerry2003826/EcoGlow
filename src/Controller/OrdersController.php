<?php
// src/Controller/OrdersController.php

declare(strict_types=1);

namespace App\Controller;

class OrdersController extends AppController
{
    public function receipt(int $id)
    {
        $order = $this->fetchTable('Orders')->get($id);

        $guest = null;

        if ($order->GuestId) {
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
            'items',
            'productsTotal',
            'installFee',
            'grandTotal'
        ));
    }
}
