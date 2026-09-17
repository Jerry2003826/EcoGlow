<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use DomainException;

class CheckoutService
{
    public function placeOrder(
        int $cartId,
        ?EntityInterface $customer,
        ?EntityInterface $guest,
        string $address
    ): EntityInterface {
        $address = trim($address);

        if ($address === '') {
            throw new DomainException('Enter a delivery address.');
        }

        if (!$customer && !$guest) {
            throw new DomainException('Enter your contact details.');
        }

        $tables = TableRegistry::getTableLocator();
        $orders = $tables->get('Orders');
        $connection = $orders->getConnection();

        return $connection->transactional(function () use (
            $tables,
            $orders,
            $connection,
            $cartId,
            $customer,
            $guest,
            $address
        ): EntityInterface {
            $connection->execute(
                'UPDATE Shopping_Cart SET InstallOptIn = InstallOptIn WHERE CartId = ?',
                [$cartId]
            );

            if ($orders->exists(['CartId' => $cartId])) {
                throw new DomainException('This cart has already been checked out.');
            }

            $cart = $tables->get('ShoppingCarts')->get(
                $cartId,
                contain: ['CartItems.Products']
            );
            $items = $cart->cart_items ?? [];

            if ($items === []) {
                throw new DomainException('Your cart is empty. Add items before checkout.');
            }

            $productsTotal = 0.0;
            $quantityTotal = 0;

            foreach ($items as $item) {
                $quantity = (int)$item->Quantity;

                if ($quantity < 1) {
                    throw new DomainException('Review the item quantities in your cart.');
                }

                $updated = $connection->execute(
                    'UPDATE Inventory SET Quantity = Quantity - ? '
                    . 'WHERE ProductId = ? AND Quantity >= ?',
                    [$quantity, (int)$item->ProductId, $quantity]
                )->rowCount();

                if ($updated !== 1) {
                    throw new DomainException(
                        'An item no longer has enough stock. Review your cart and try again.'
                    );
                }

                $productsTotal += (float)$item->product->Price * $quantity;
                $quantityTotal += $quantity;
            }

            $installFee = (int)$cart->InstallOptIn === 1 ? $quantityTotal * 80 : 0;
            $cart->InstallFee = $installFee;

            if ($customer) {
                $cart->CustomerId = $customer->CustomerId;
                $cart->GuestId = null;
                $buyerName = (string)$customer->CustomerName;
            } else {
                $tables->get('Guests')->saveOrFail($guest);
                $cart->GuestId = $guest->GuestId;
                $cart->CustomerId = null;
                $buyerName = (string)$guest->GuestName;
            }

            $tables->get('ShoppingCarts')->saveOrFail($cart);
            $order = $orders->newEntity([
                'CustomerId' => $cart->CustomerId,
                'GuestId' => $cart->GuestId,
                'CartId' => $cart->CartId,
                'Status' => 'paid',
            ]);
            $orders->saveOrFail($order);

            foreach ($items as $item) {
                $orderItem = $tables->get('OrderItems')->newEntity([
                    'OrderId' => $order->OrderId,
                    'ProductId' => $item->ProductId,
                    'Quantity' => $item->Quantity,
                    'UnitPrice' => $item->product->Price,
                ]);
                $tables->get('OrderItems')->saveOrFail($orderItem);
            }

            if ($installFee > 0) {
                $serviceRequest = $tables->get('ServiceRequests')->newEntity([
                    'CustomerId' => $cart->CustomerId,
                    'RequestType' => 'installation',
                    'Description' => sprintf(
                        'Installation for Order #%s Buyer %s Address %s',
                        $order->OrderId,
                        $buyerName,
                        $address
                    ),
                    'RequestedDate' => date('Y-m-d H:i:s', strtotime('+5 days')),
                ]);
                $tables->get('ServiceRequests')->saveOrFail($serviceRequest);
            }

            $delivery = $tables->get('Delivery')->newEntity([
                'OrderId' => $order->OrderId,
                'DeliveryAddress' => $address,
                'DeliveryStatus' => 'scheduled',
                'DeliveryDate' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ]);
            $tables->get('Delivery')->saveOrFail($delivery);

            $payment = $tables->get('Payments')->newEntity([
                'OrderId' => $order->OrderId,
                'Amount' => round($productsTotal + $installFee, 2),
                'PaymentMethod' => 'test-card',
            ]);
            $tables->get('Payments')->saveOrFail($payment);

            $receipt = $tables->get('Receipts')->newEntity([
                'PaymentId' => $payment->PaymentId,
                'ReceiptNumber' => 'RCT-' . $order->OrderId,
            ]);
            $tables->get('Receipts')->saveOrFail($receipt);

            return $orders->get(
                $order->OrderId,
                contain: [
                    'OrderItems.Products',
                    'Customers',
                    'Guests',
                    'Payments',
                    'ShoppingCarts',
                ]
            );
        });
    }
}
