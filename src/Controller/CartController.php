<?php
// src/Controller/CartController.php

declare(strict_types=1);

namespace App\Controller;

class CartController extends AppController
{
    public function index()
    {
        $cartId = $this->request->getSession()->read('CartId');

        $cart = null;
        $items = [];
        $subtotal = 0.0;

        if ($cartId) {
            $cart = $this->fetchTable('ShoppingCarts')->get(
                $cartId,
                contain: ['CartItems.Products']
            );

            if ($this->request->is(['post', 'put', 'patch'])) {
                $cart->InstallOptIn = (int)$this->request->getData('install_opt_in', 0);

                $this->fetchTable('ShoppingCarts')->save($cart);

                foreach (($this->request->getData('qty') ?? []) as $cartItemId => $qty) {
                    $item = $this->fetchTable('CartItems')->get((int)$cartItemId);
                    $item->Quantity = max(1, (int)$qty);
                    $this->fetchTable('CartItems')->save($item);
                }

                return $this->redirect(['action' => 'index']);
            }

            $items = $cart->cart_items;

            foreach ($items as $item) {
                $subtotal += ((float)$item->product->Price * (int)$item->Quantity);
            }

            if ((int)$cart->InstallOptIn === 1) {
                $subtotal += (float)$cart->InstallFee;
            }
        }

        $this->set(compact(
            'cart',
            'items',
            'subtotal'
        ));
    }
}
