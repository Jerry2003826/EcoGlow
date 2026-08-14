<?php
declare(strict_types=1);

namespace App\Service\Checkout;

use App\Model\Entity\Cart;
use App\Model\Entity\Customer;
use App\Model\Entity\SalesOrder;
use App\Service\AustralianStates;
use App\Service\Cart\CartService;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentGatewayInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
use Throwable;

/**
 * Places a web checkout order and starts a Stripe PaymentIntent.
 */
class CheckoutService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Service\Orders\OrderService $orders Order writer.
     * @param \App\Service\Cart\CartService $carts Cart totals.
     * @param \App\Service\Payments\PaymentGatewayInterface $gateway Stripe or test double.
     */
    public function __construct(
        private OrderService $orders,
        private CartService $carts,
        private PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * @param \App\Model\Entity\Customer $customer Signed-in customer.
     * @param int $userId Customer user id.
     * @param \App\Model\Entity\Cart $cart Current cart.
     * @param array<string, mixed> $address Shipping fields. Prices in this array are ignored.
     * @param bool $saveAddress Persist the address onto the customer record.
     * @return array{order: \App\Model\Entity\SalesOrder, client_secret: string}
     */
    public function place(
        Customer $customer,
        int $userId,
        Cart $cart,
        array $address,
        bool $saveAddress = false,
    ): array {
        $address = $this->sanitizeAddress($address);
        $order = $this->orders->createFromCheckout((int)$customer->id, $userId, $cart, $address);

        if ($saveAddress) {
            $this->storeCustomerAddress((int)$customer->id, $address);
        }

        try {
            $intent = $this->gateway->createPaymentIntent(
                (int)$order->grand_total_cents,
                'aud',
                [
                    'order_id' => (string)$order->id,
                    'order_number' => (string)$order->order_number,
                ],
            );
        } catch (Throwable $exception) {
            $this->orders->failUnpaid($order, $userId, 'PaymentIntent could not be created');
            throw $exception;
        }

        $this->recordPendingPayment($order, $intent->id, (int)$order->grand_total_cents);
        $meta = is_array($order->metadata) ? $order->metadata : [];
        $meta['stripe_payment_intent_id'] = $intent->id;
        $order->metadata = $meta;
        $this->fetchTable('SalesOrders')->saveOrFail($order);

        return [
            'order' => $order,
            'client_secret' => $intent->clientSecret,
        ];
    }

    /**
     * Re-open the latest unpaid web checkout so a refresh can finish the card form.
     *
     * @param \App\Model\Entity\Customer $customer Signed-in customer.
     * @return array{order: \App\Model\Entity\SalesOrder, client_secret: string}|null
     */
    public function resumePending(Customer $customer): ?array
    {
        $order = $this->fetchTable('SalesOrders')->find()
            ->contain(['SalesOrderItems'])
            ->where([
                'customer_id' => $customer->id,
                'source_channel' => SalesOrder::CHANNEL_WEB,
                'payment_status' => 'pending',
                'status' => SalesOrder::STATUS_DRAFT,
            ])
            ->orderBy(['id' => 'DESC'])
            ->first();
        if ($order === null) {
            return null;
        }

        $meta = is_array($order->metadata) ? $order->metadata : [];
        $intentId = (string)($meta['stripe_payment_intent_id'] ?? '');
        if ($intentId === '') {
            return null;
        }

        try {
            $secret = $this->gateway->retrieveClientSecret($intentId);
        } catch (Throwable) {
            return null;
        }
        if ($secret === null || $secret === '') {
            return null;
        }

        return [
            'order' => $order,
            'client_secret' => $secret,
        ];
    }

    /**
     * Live totals for the checkout page. Posted amounts are never read.
     *
     * @param \App\Model\Entity\Cart|null $cart Cart.
     * @return array<string, int|string>
     */
    public function totals(?Cart $cart): array
    {
        return $this->carts->totals($cart);
    }

    /**
     * @param array<string, mixed> $address Posted fields, including any forged totals.
     * @return array<string, string>
     */
    private function sanitizeAddress(array $address): array
    {
        $clean = [
            'recipient_name' => trim((string)($address['recipient_name'] ?? '')),
            'company' => trim((string)($address['company'] ?? '')),
            'line1' => trim((string)($address['line1'] ?? '')),
            'line2' => trim((string)($address['line2'] ?? '')),
            'suburb' => trim((string)($address['suburb'] ?? '')),
            'state' => strtoupper(trim((string)($address['state'] ?? ''))),
            'postcode' => trim((string)($address['postcode'] ?? '')),
            'phone' => trim((string)($address['phone'] ?? '')),
        ];
        if (!AustralianStates::isValid($clean['state'])) {
            throw new InvalidArgumentException('Please choose an Australian state or territory.');
        }

        return $clean;
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Order.
     * @param string $intentId Stripe PaymentIntent id.
     * @param int $amountCents Amount.
     * @return void
     */
    private function recordPendingPayment(SalesOrder $order, string $intentId, int $amountCents): void
    {
        $payments = $this->fetchTable('Payments');
        $existing = $payments->find()
            ->where(['provider' => 'stripe', 'provider_payment_id' => $intentId])
            ->first();
        if ($existing) {
            return;
        }
        $payment = $payments->newEmptyEntity();
        $payment->sales_order_id = $order->id;
        $payment->provider = 'stripe';
        $payment->provider_payment_id = $intentId;
        $payment->method = 'card';
        $payment->status = 'pending';
        $payment->amount_cents = $amountCents;
        $payment->currency = 'AUD';
        $payment->transaction_reference = $order->order_number;
        $payment->provider_metadata = ['payment_intent' => $intentId];
        $payments->saveOrFail($payment);
    }

    /**
     * @param int $customerId Customer.
     * @param array<string, string> $address Address fields.
     * @return void
     */
    private function storeCustomerAddress(int $customerId, array $address): void
    {
        $addresses = $this->fetchTable('Addresses');
        $row = $addresses->newEmptyEntity();
        $row->set('customer_id', $customerId);
        $row->set('label', 'Checkout');
        $row->set('recipient_name', $address['recipient_name']);
        $row->set('company', $address['company'] !== '' ? $address['company'] : null);
        $row->set('line1', $address['line1']);
        $row->set('line2', $address['line2'] !== '' ? $address['line2'] : null);
        $row->set('suburb', $address['suburb']);
        $row->set('state', $address['state']);
        $row->set('postcode', $address['postcode']);
        $row->set('country_code', 'AU');
        $row->set('phone', $address['phone'] !== '' ? $address['phone'] : null);
        $addresses->save($row);
    }
}
