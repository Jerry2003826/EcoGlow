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
                'OrderId' => $id,
            ])
            ->all();

        $cart = $this->fetchTable('ShoppingCarts')->get($order->CartId);

        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += ((float)$item->UnitPrice * (int)$item->Quantity);
        }

        $installFee = ((int)$cart->InstallOptIn === 1)
            ? (float)$cart->InstallFee
            : 0.00;

        $total = $subtotal + $installFee;

        $this->set(compact(
            'order',
            'guest',
            'items',
            'subtotal',
            'installFee',
            'total'
        ));
    }
}
