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
            $this->Flash->error('Your cart is empty. Add items before checkout.');

            return $this->redirect([
                'controller' => 'Products',
                'action' => 'index',
            ]);
        }

        $cart = $this->fetchTable('ShoppingCarts')->get(
            $cartId,
            contain: ['CartItems.Products']
        );

        $items = $cart->cart_items ?? [];

        if (empty($items)) {
            $this->Flash->error('Your cart is empty. Add items before checkout.');

            return $this->redirect([
                'controller' => 'Products',
                'action' => 'index',
            ]);
        }

        $productsTotal = 0.00;

        foreach ($items as $item) {
            $productsTotal += (
                (float)$item->product->Price * (int)$item->Quantity
            );
        }

        $installFee = ((int)$cart->InstallOptIn === 1)
            ? (float)$cart->InstallFee
            : 0.00;

        $grandTotal = $productsTotal + $installFee;

        if ($this->request->is('post')) {
            $cardNumber = preg_replace(
                '/\s+/',
                '',
                (string)$this->request->getData('card_number')
            );

            if ($cardNumber !== '4242424242424242') {
                $this->Flash->error('Use test card 4242424242424242.');

                $this->set(compact(
                    'cart',
                    'items',
                    'productsTotal',
                    'installFee',
                    'grandTotal'
                ));

                return;
            }

            $guest = $this->fetchTable('Guests')->newEntity([
                'GuestName' => $this->request->getData('GuestName'),
                'Email' => $this->request->getData('Email'),
                'Address' => $this->request->getData('Address'),
                'Phone' => $this->request->getData('Phone'),
            ]);

            $this->fetchTable('Guests')->saveOrFail($guest);

            $cart->GuestId = $guest->GuestId;
            $this->fetchTable('ShoppingCarts')->saveOrFail($cart);

            $order = $this->fetchTable('Orders')->newEntity([
                'CartId' => $cart->CartId,
                'GuestId' => $guest->GuestId,
                'Status' => 'paid',
            ]);

            $this->fetchTable('Orders')->saveOrFail($order);

            foreach ($items as $item) {
                $this->fetchTable('OrderItems')->saveOrFail(
                    $this->fetchTable('OrderItems')->newEntity([
                        'OrderId' => $order->OrderId,
                        'ProductId' => $item->ProductId,
                        'Quantity' => $item->Quantity,
                        'UnitPrice' => $item->product->Price,
                    ])
                );

                $inventory = $this->fetchTable('Inventory')
                    ->find()
                    ->where(['ProductId' => $item->ProductId])
                    ->first();

                if ($inventory) {
                    $inventory->Quantity = max(
                        0,
                        (int)$inventory->Quantity - (int)$item->Quantity
                    );

                    $this->fetchTable('Inventory')->save($inventory);
                }
            }

            if ($installFee > 0) {
                $serviceRequest = $this->fetchTable('ServiceRequests')->newEntity([
                    'RequestType' => 'installation',
                    'Description' => sprintf(
                        'Installation for Order %s Guest %s Address %s',
                        $order->OrderId,
                        $guest->GuestName,
                        $guest->Address
                    ),
                    'RequestedDate' => date('Y-m-d H:i:s', strtotime('+5 days')),
                ]);

                $this->fetchTable('ServiceRequests')->save($serviceRequest);
            }

            $delivery = $this->fetchTable('Delivery')->newEntity([
                'OrderId' => $order->OrderId,
                'DeliveryAddress' => $guest->Address,
                'DeliveryStatus' => 'scheduled',
                'DeliveryDate' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ]);

            $this->fetchTable('Delivery')->saveOrFail($delivery);

            $payment = $this->fetchTable('Payments')->newEntity([
                'OrderId' => $order->OrderId,
                'Amount' => $grandTotal,
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

        $this->set(compact(
            'cart',
            'items',
            'productsTotal',
            'installFee',
            'grandTotal'
        ));
    }
}
