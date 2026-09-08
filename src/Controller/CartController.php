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
        $grandTotal = 0.00;

        if ($cartId) {
            $cart = $this->fetchTable('ShoppingCarts')->get(
                $cartId,
                contain: ['CartItems.Products.Inventory']
            );

            if ($this->request->is('post')) {
                $removedId = (int)$this->request->getData('remove_item');

                if ($removedId) {
                    $cartItem = $this->fetchTable('CartItems')->find()
                        ->where([
                            'CartItemId' => $removedId,
                            'CartId' => (int)$cartId,
                        ])
                        ->first();

                    if ($cartItem) {
                        $this->fetchTable('CartItems')->delete($cartItem);
                    }
                }

                foreach ((array)$this->request->getData('qty') as $id => $qty) {
                    if ($removedId && (int)$id === $removedId) {
                        continue;
                    }

                    $cartItem = $this->fetchTable('CartItems')->get((int)$id);

                    $productRow = $this->fetchTable('Products')->get(
                        $cartItem->ProductId,
                        contain: ['Inventory']
                    );
                    $stockQty = (int)($productRow->inventory?->Quantity ?? 0);

                    $cartItem->Quantity = max(
                        1,
                        min((int)$qty, (int)$stockQty)
                    );

                    $this->fetchTable('CartItems')->save($cartItem);
                }

                $cart = $this->fetchTable('ShoppingCarts')->get(
                    $cartId,
                    contain: ['CartItems']
                );

                $cart->InstallOptIn = $this->request->getData(
                    'install_opt_in'
                ) ? 1 : 0;

                $qtyTotal = 0;

                foreach ($cart->cart_items as $item) {
                    $qtyTotal += (int)$item->Quantity;
                }

                $cart->InstallFee = $cart->InstallOptIn
                    ? ($qtyTotal * 80)
                    : 0;

                $this->fetchTable('ShoppingCarts')->save($cart);

                return $this->redirect(['action' => 'index']);
            }

            $items = $cart->cart_items;

            foreach ($items as $item) {
                $grandTotal += (float)$item->product->Price * (int)$item->Quantity;
            }

            if ((int)$cart->InstallOptIn === 1) {
                $grandTotal += (float)$cart->InstallFee;
            }
        }

        $this->set(compact(
            'cart',
            'items',
            'grandTotal'
        ));
    }
}
