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

        $errors = [];
        $customer = null;
        $guest = null;

        //Check login status
        $identity = $this->request->getAttribute('identity');
        if ($identity) {
            $userId = $identity->getIdentifier();
            $customer = $this->fetchTable('Customers')->find()
                ->where(['UserId' => $userId])
                ->first();

        }


        if ($this->request->is('post')) {

            if(!$customer) {
                $guest = $this->fetchTable('Guests')->newEntity([
                    'GuestName' => $this->request->getData('GuestName'),
                    'Email' => $this->request->getData('Email'),
                    'Address' => $this->request->getData('Address'),
                    'Phone' => $this->request->getData('Phone'),
                ]);

                $errors = $guest->getErrors();

                if (!empty($errors)) {
                    $this->set(compact(
                        'cart',
                        'items',
                        'productsTotal',
                        'installFee',
                        'grandTotal',
                        'errors',
                        'customer'
                    ));

                    return;
                }
            }
            

            $cardNumber = preg_replace(
                '/\s+/',
                '',
                (string)$this->request->getData('card_number')
            );
            $cvv = preg_replace(
                '/\s+/',
                '',
                (string)$this->request->getData('cvv')
            );

            $expiryDate = preg_replace(
                '/\s+/',
                '',
                (string)$this->request->getData('expiry_date')
            );

            if ($cardNumber !== '4242424242424242') {
                $errors['card_number'] = [
                    'mismatch' => 'Enter the 16-digit test card number.',
                ];
            }

            if ($expiryDate !== '12/31') {
                $errors['expiry_date'] = [
                    'mismatch' => 'Enter the test expiry date 12/31.',
                ];
            }

            if ($cvv !== '123') {
                $errors['cvv'] = [
                    'mismatch' => 'Enter the 3-digit test CVV.',
                ];
            }

            if (!empty($errors)) {
                $this->Flash->error(
                    'Payment Failed. Please check your payment details and try again.'
                );

                $this->set(compact(
                    'cart',
                    'items',
                    'productsTotal',
                    'installFee',
                    'grandTotal',
                    'errors',
                    'customer'
                ));

                return;
            }

            if($customer) {
                $cart->CustomerId = $customer->CustomerId;
                $cart->GuestId = null;
                $checkoutName = $customer->CustomerName;
                $checkoutAddress = (string)$this->request->getData('Address');
            } else {
                $this->fetchTable('Guests')->saveOrFail($guest);
                $cart->GuestId = $guest->GuestId;
                $cart->CustomerId = null;
                $checkoutName = $guest->GuestName;
                $checkoutAddress = $guest->Address;
            }

            $this->fetchTable('ShoppingCarts')->saveOrFail($cart);

            $order = $this->fetchTable('Orders')->newEntity([
                'CustomerId' => $customer ? $customer->CustomerId : null,
                'GuestId' => $guest ? $guest->GuestId : null,
                'CartId' => $cart->CartId,
                'Status' => 'paid',
            ]);

            $this->fetchTable('Orders')->saveOrFail($order);

            foreach ($items as $item) {
                $orderItem = $this->fetchTable('OrderItems')->newEntity([
                    'OrderId' => $order->OrderId,
                    'ProductId' => $item->ProductId,
                    'Quantity' => $item->Quantity,
                    'UnitPrice' => $item->product->Price,
                ]);

                $this->fetchTable('OrderItems')->saveOrFail($orderItem);

                $inventory = $this->fetchTable('Inventory')
                    ->find()
                    ->where([
                        'ProductId' => $item->ProductId,
                    ])
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
                        $checkoutName,
                        $checkoutAddress
                    ),
                    'RequestedDate' => date(
                        'Y-m-d H:i:s',
                        strtotime('+5 days')
                    ),
                ]);

                $this->fetchTable('ServiceRequests')->save($serviceRequest);
            }

            $delivery = $this->fetchTable('Delivery')->newEntity([
                'OrderId' => $order->OrderId,
                'DeliveryAddress' => $checkoutAddress,
                'DeliveryStatus' => 'scheduled',
                'DeliveryDate' => date(
                    'Y-m-d H:i:s',
                    strtotime('+7 days')
                ),
            ]);

            $this->fetchTable('Delivery')->saveOrFail($delivery);

            $payment = $this->fetchTable('Payments')->newEntity([
                'OrderId' => $order->OrderId,
                'Amount' => $grandTotal,
                'PaymentMethod' => 'test-card',
            ]);

            $this->fetchTable('Payments')->saveOrFail($payment);
            $this->Flash->success('Payment successful.');

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
            'grandTotal',
            'errors',
            'customer',
        ));
    }
}
