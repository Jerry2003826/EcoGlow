<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Cart;
use App\Model\Entity\Customer;
use App\Service\AustralianStates;
use App\Service\Cart\CartService;
use App\Service\Checkout\CheckoutService;
use App\Service\FeatureFlagService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\PaymentGatewayFactory;
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
                    $posted = $this->postedAddress($addresses);
                    $result = $checkout->place(
                        $customer,
                        (int)$customer->user_id,
                        $cart,
                        $posted,
                        (bool)$this->request->getData('save_address'),
                    );
                    $order = $result['order'];
                    $clientSecret = $result['client_secret'];
                    $this->Flash->success(__('Review the payment details below to finish this order.'));
                } catch (InvalidArgumentException $exception) {
                    $this->Flash->error($exception->getMessage());
                    $errors['form'] = $exception->getMessage();
                }
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
