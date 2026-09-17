<?php
// src/Controller/CheckoutController.php

declare(strict_types=1);

namespace App\Controller;

use App\Mailer\OrderMailer;
use App\Service\CheckoutService;
use DomainException;

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
        $quantityTotal = 0;

        foreach ($items as $item) {
            $quantityTotal += (int)$item->Quantity;
            $productsTotal += (
                (float)$item->product->Price * (int)$item->Quantity
            );
        }

        $installFee = ((int)$cart->InstallOptIn === 1)
            ? $quantityTotal * 80
            : 0.00;
        $grandTotal = $productsTotal + $installFee;

        $errors = [];
        $customer = null;
        $guest = null;

        $identity = $this->request->getAttribute('identity');
        if ($identity) {
            $userId = $identity->getIdentifier();
            $customer = $this->fetchTable('Customers')->find()
                ->where(['UserId' => $userId])
                ->first();
        }

        if ($this->request->is('post')) {
            $checkoutAddress = trim((string)$this->request->getData('Address'));

            if ($checkoutAddress === '') {
                $errors['Address'] = ['required' => 'Enter a delivery address.'];
            }

            if (!$customer) {
                $guest = $this->fetchTable('Guests')->newEntity([
                    'GuestName' => $this->request->getData('GuestName'),
                    'Email' => $this->request->getData('Email'),
                    'Address' => $checkoutAddress,
                    'Phone' => $this->request->getData('Phone'),
                ]);

                $errors = array_merge($errors, $guest->getErrors());

                if (!empty($errors)) {
                    $this->Flash->error(
                        isset($errors['Address'])
                            ? 'Enter a delivery address.'
                            : 'Check your contact details and try again.'
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
                    isset($errors['Address'])
                        ? 'Enter a delivery address.'
                        : 'Payment Failed. Please check your payment details and try again.'
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

            try {
                $order = (new CheckoutService())->placeOrder(
                    (int)$cartId,
                    $customer,
                    $guest,
                    $checkoutAddress
                );
            } catch (\Throwable $exception) {
                $this->Flash->error(
                    $exception instanceof DomainException
                        ? $exception->getMessage()
                        : 'Your order could not be completed. No payment was taken. Please try again.'
                );
                $this->log($exception->getMessage());
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

            if (!$order->CustomerId && $order->GuestId) {
                $session->write('GuestReceiptOrders.' . (int)$order->OrderId, true);
            }

            $this->Flash->success('Payment successful.');

            $confirmEmail = (string)($order->customer->Email
                ?? $order->guest->Email
                ?? '');

            if ($confirmEmail !== '') {
                try {
                    (new OrderMailer())->send('orderConfirmation', [$order]);
                } catch (\Throwable $exception) {
                    $this->log($exception->getMessage());
                }
            }

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
