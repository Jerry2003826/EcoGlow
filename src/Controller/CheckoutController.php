<?php
// src/Controller/CheckoutController.php

declare(strict_types=1);

namespace App\Controller;

class CheckoutController extends AppController
{
    public function index()
    {
        $session = $this->request->getSession();

        $cartId = $session->read('CartId');

        if (!$cartId) {
            return $this->redirect([
                'controller' => 'Products',
                'action' => 'index',
            ]);
        }

        $cart = $this->fetchTable('ShoppingCarts')->get(
            $cartId,
            contain: ['CartItems.Products']
        );

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $cardNumber = preg_replace('/\s+/', '', (string)$data['card_number']);

            if ($cardNumber !== '4242424242424242') {
                $this->Flash->error('Use test card 4242424242424242.');

                $this->set(compact('cart'));

                return;
            }

            $guest = $this->fetchTable('Guests')->newEntity([
                'GuestName' => $data['GuestName'],
                'Email' => $data['Email'],
                'Address' => $data['Address'],
                'Phone' => $data['Phone'],
            ]);

            $this->fetchTable('Guests')->saveOrFail($guest);

            $cart->GuestId = $guest->GuestId;

            $this->fetchTable('ShoppingCarts')->saveOrFail($cart);

            $ordersTable = $this->fetchTable('Orders');

            $order = $ordersTable->newEntity([
                'CartId' => $cart->CartId,
                'GuestId' => $guest->GuestId,
                'Status' => 'paid',
            ]);

            $ordersTable->saveOrFail($order);

            $orderItemsTable = $this->fetchTable('OrderItems');

            $total = 0;

            foreach ($cart->cart_items as $cartItem) {
                $line = ((float)$cartItem->product->Price) * (int)$cartItem->Quantity;

                $total += $line;

                $orderItem = $orderItemsTable->newEntity([
                    'OrderId' => $order->OrderId,
                    'ProductId' => $cartItem->ProductId,
                    'Quantity' => $cartItem->Quantity,
                    'UnitPrice' => $cartItem->product->Price,
                ]);

                $orderItemsTable->saveOrFail($orderItem);
            }

            if ((int)$cart->InstallOptIn === 1) {
                $total += (float)$cart->InstallFee;
            }

            $delivery = $this->fetchTable('Delivery')->newEntity([
                'OrderId' => $order->OrderId,
                'DeliveryAddress' => $guest->Address,
                'DeliveryStatus' => 'scheduled',
            ]);

            $this->fetchTable('Delivery')->saveOrFail($delivery);

            $payment = $this->fetchTable('Payments')->newEntity([
                'OrderId' => $order->OrderId,
                'Amount' => $total,
                'PaymentMethod' => 'test-card',
            ]);

            $this->fetchTable('Payments')->saveOrFail($payment);

            $receipt = $this->fetchTable('Receipts')->newEntity([
                'PaymentId' => $payment->PaymentId,
                'ReceiptNumber' => 'RCT-' . time(),
            ]);

            $this->fetchTable('Receipts')->saveOrFail($receipt);

            $session->delete('CartId');

            return $this->redirect([
                'controller' => 'Orders',
                'action' => 'receipt',
                $order->OrderId,
            ]);
        }

        $this->set(compact('cart'));
    }
}
