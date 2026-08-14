<?php
declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AbuseThrottleMiddleware;
use App\Model\Entity\Cart;
use App\Model\Entity\Customer;
use App\Model\Entity\SalesOrder;
use App\Service\AustralianStates;
use App\Service\Cart\CartService;
use App\Service\Checkout\CheckoutService;
use App\Service\FeatureFlagService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentGatewayFactory;
use App\Service\Payments\PaymentUncertainException;
use App\Service\Security\RateLimitService;
use App\Service\Security\SensitiveSession;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Single-page checkout with an embedded Stripe Payment Element.
 */
class CheckoutController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->addHelper('Money');
        $this->viewBuilder()->setTemplatePath('Checkout');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $customer = $this->requireCustomer();
        $user = $this->fetchTable('Users')->get((int)$customer->user_id);
        if ($user->get('email_verified_at') === null) {
            $this->Flash->warning(__('Please confirm your email before completing checkout.'));
            if ($this->request->is('post')) {
                return $this->redirect('/account');
            }
        }
        $attemptId = $this->checkoutAttemptId();
        $carts = new CartService();
        $token = $carts->token($this->request);
        $cart = $carts->current((int)$customer->user_id, $token, false);
        $checkout = $this->checkoutService($carts);
        $totals = $checkout->totals($cart);
        $lines = $this->reviewLines($cart);
        $flags = new FeatureFlagService();
        $paymentsEnabled = $flags->enabled(FeatureFlagService::ONLINE_PAYMENTS, false);
        $publishableKey = (string)Configure::read('Stripe.publishableKey');
        $stripeConfigured = $publishableKey !== ''
            && (string)Configure::read('Stripe.secretKey') !== '';
        $addresses = $this->fetchTable('Addresses')->find()
            ->where(['customer_id' => $customer->id])
            ->orderBy(['is_default_shipping' => 'DESC', 'id' => 'ASC'])
            ->all();
        $states = AustralianStates::options();
        $clientSecret = null;
        $order = null;
        $errors = [];

        if ($this->request->is('post')) {
            if ((int)($totals['total_cents'] ?? 0) < 1 || $cart === null || ($cart->cart_items ?? []) === []) {
                $this->Flash->error(__('Your basket is empty.'));

                return $this->redirect('/cart');
            }
            if (!$paymentsEnabled) {
                $this->Flash->warning(__('Online payment is not open yet. Please contact us to complete your order.'));
            } elseif (!$stripeConfigured) {
                $this->Flash->warning(__(
                    'Card payment is not configured on this server yet. ' .
                    'Your basket is held; please contact us to complete the order.',
                ));
            } else {
                try {
                    if ($user->get('email_verified_at') === null) {
                        throw new InvalidArgumentException(
                            'Please confirm your email before completing checkout.',
                        );
                    }
                    RateLimitService::hit(
                        AbuseThrottleMiddleware::SCOPE_CHECKOUT,
                        'user:' . (int)$customer->user_id,
                    );
                    if (
                        RateLimitService::locked(
                            AbuseThrottleMiddleware::SCOPE_CHECKOUT,
                            'user:' . (int)$customer->user_id,
                            AbuseThrottleMiddleware::MAX_CHECKOUT_USER,
                        )
                    ) {
                        throw new InvalidArgumentException(
                            'Too many checkout attempts. Please wait a few minutes and try again.',
                        );
                    }
                    $posted = $this->postedAddress($addresses);
                    $result = $checkout->place(
                        $customer,
                        (int)$customer->user_id,
                        $cart,
                        $posted,
                        (bool)$this->request->getData('save_address'),
                        $attemptId,
                    );
                    $order = $result['order'];
                    $clientSecret = $result['client_secret'];
                    $this->Flash->success(__('Review the payment details below to finish this order.'));
                } catch (PaymentUncertainException $exception) {
                    $this->Flash->error(__(
                        'The payment service timed out. Your basket is still held. Please try again.',
                    ));
                    $errors['form'] = $exception->getMessage();
                } catch (InvalidArgumentException $exception) {
                    if (str_contains($exception->getMessage(), 'no longer valid')) {
                        $this->request->getSession()->delete(SensitiveSession::CHECKOUT_ATTEMPT);
                    }
                    $this->Flash->error($exception->getMessage());
                    $errors['form'] = $exception->getMessage();
                }
            }
        } elseif ($paymentsEnabled && $stripeConfigured) {
            $pending = $checkout->resumePending($customer);
            if ($pending !== null) {
                $order = $pending['order'];
                $clientSecret = $pending['client_secret'];
                $lines = $this->reviewLinesFromOrder($order);
                $totals = $this->totalsFromOrder($order, $totals);
            }
        }

        $this->set(compact(
            'customer',
            'lines',
            'totals',
            'addresses',
            'states',
            'paymentsEnabled',
            'stripeConfigured',
            'publishableKey',
            'clientSecret',
            'order',
            'errors',
            'attemptId',
        ));

        return null;
    }

    /**
     * @param string|null $id Order id.
     * @return void
     */
    public function confirmation(?string $id = null): void
    {
        $customer = $this->requireCustomer();
        $order = $this->fetchTable('SalesOrders')->find()
            ->contain(['SalesOrderItems', 'OrderAddresses'])
            ->where([
                'SalesOrders.id' => $this->recordId($id),
                'SalesOrders.customer_id' => $customer->id,
            ])
            ->first();
        if ($order === null) {
            throw new NotFoundException();
        }
        $this->set(compact('customer', 'order'));
    }

    /**
     * @param iterable<\App\Model\Entity\Address> $addresses Saved addresses.
     * @return array<string, mixed>
     */
    private function postedAddress(iterable $addresses): array
    {
        $savedId = (int)$this->request->getData('saved_address_id');
        if ($savedId > 0) {
            foreach ($addresses as $row) {
                if ((int)$row->id === $savedId) {
                    return [
                        'recipient_name' => (string)$row->recipient_name,
                        'company' => (string)($row->company ?? ''),
                        'line1' => (string)$row->line1,
                        'line2' => (string)($row->line2 ?? ''),
                        'suburb' => (string)$row->suburb,
                        'state' => (string)$row->state,
                        'postcode' => (string)$row->postcode,
                        'phone' => (string)($row->phone ?? ''),
                    ];
                }
            }
        }

        return [
            'recipient_name' => (string)$this->request->getData('recipient_name'),
            'company' => (string)$this->request->getData('company'),
            'line1' => (string)$this->request->getData('line1'),
            'line2' => (string)$this->request->getData('line2'),
            'suburb' => (string)$this->request->getData('suburb'),
            'state' => (string)$this->request->getData('state'),
            'postcode' => (string)$this->request->getData('postcode'),
            'phone' => (string)$this->request->getData('phone'),
        ];
    }

    /**
     * Review rows from live variant prices. Posted amounts are never read.
     *
     * @param \App\Model\Entity\Cart|null $cart Cart.
     * @return list<array<string, mixed>>
     */
    private function reviewLines(?Cart $cart): array
    {
        $lines = [];
        foreach ($cart->cart_items ?? [] as $item) {
            $variant = $this->fetchTable('ProductVariants')->get(
                (int)$item->product_variant_id,
                contain: ['Products'],
            );
            $qty = (int)$item->quantity;
            $unit = (int)$variant->price_cents;
            $lines[] = [
                'name' => (string)($variant->product->name ?? $variant->name),
                'variant' => (string)$variant->name,
                'qty' => $qty,
                'line_total_cents' => $unit * $qty,
            ];
        }

        return $lines;
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Held unpaid checkout.
     * @return list<array<string, mixed>>
     */
    private function reviewLinesFromOrder(SalesOrder $order): array
    {
        $lines = [];
        foreach ($order->sales_order_items ?? [] as $item) {
            $lines[] = [
                'name' => (string)$item->item_name_snapshot,
                'variant' => (string)($item->variant_name_snapshot ?? ''),
                'qty' => (int)$item->quantity,
                'line_total_cents' => (int)$item->line_total_cents,
            ];
        }

        return $lines;
    }

    /**
     * @param \App\Model\Entity\SalesOrder $order Held unpaid checkout.
     * @param array<string, int|string> $defaults Live cart totals (threshold, rates).
     * @return array<string, int|string>
     */
    private function totalsFromOrder(SalesOrder $order, array $defaults): array
    {
        $defaults['subtotal_cents'] = (int)$order->subtotal_cents;
        $defaults['shipping_cents'] = (int)$order->shipping_cents;
        $defaults['total_cents'] = (int)$order->grand_total_cents;
        $defaults['gst_cents'] = (int)$order->tax_cents;

        return $defaults;
    }

    /**
     * @param \App\Service\Cart\CartService $carts Cart service.
     * @return \App\Service\Checkout\CheckoutService
     */
    private function checkoutService(CartService $carts): CheckoutService
    {
        return new CheckoutService(
            new OrderService(new InventoryLedger()),
            $carts,
            PaymentGatewayFactory::create(),
        );
    }

    /**
     * @return string
     */
    private function checkoutAttemptId(): string
    {
        $session = $this->request->getSession();
        $stored = strtolower(trim((string)$session->read(SensitiveSession::CHECKOUT_ATTEMPT)));
        $posted = strtolower(trim((string)$this->request->getData('checkout_attempt_id')));
        if ($this->validAttemptId($stored)) {
            $candidate = $stored;
        } elseif ($this->validAttemptId($posted)) {
            $candidate = $posted;
        } else {
            $candidate = $this->newUuid();
        }
        $session->write(SensitiveSession::CHECKOUT_ATTEMPT, $candidate);

        return $candidate;
    }

    /**
     * @param string $value Candidate UUID.
     * @return bool
     */
    private function validAttemptId(string $value): bool
    {
        return (bool)preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $value,
        );
    }

    /**
     * @return string
     */
    private function newUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * @return \App\Model\Entity\Customer
     */
    private function requireCustomer(): Customer
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            throw new NotFoundException();
        }
        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => (int)$identity->getIdentifier()])
            ->first();
        if ($customer === null) {
            throw new NotFoundException();
        }

        return $customer;
    }
}
