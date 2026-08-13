<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Cart\CartService;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Persistent basket and save-for-later.
 */
class CartsController extends AppController
{
    /**
     * @var \App\Service\Cart\CartService
     */
    private CartService $carts;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated([
            'index', 'add', 'update', 'remove', 'saveLater', 'moveToCart',
        ]);
        $this->carts = new CartService();
        $this->viewBuilder()->setTemplatePath('Pages');
        $this->viewBuilder()->addHelper('Money');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $this->setCartView();

        return $this->render('cart');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $cart = $this->currentCart(true);
            $this->carts->add(
                $cart,
                (int)$this->request->getData('product_variant_id'),
                (int)$this->request->getData('quantity') ?: 1,
            );
            $this->Flash->success(__('Added to your basket.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect($this->referer('/cart'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function update(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $cart = $this->currentCart(true);
            $this->carts->updateQuantity(
                $cart,
                (int)$this->request->getData('item_id'),
                (int)$this->request->getData('quantity'),
            );
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function remove(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $cart = $this->currentCart(true);
            $this->carts->remove($cart, (int)$this->request->getData('item_id'));
            $this->Flash->success(__('Removed from your basket.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function saveLater(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $cart = $this->currentCart(true);
            [$customerId, $userId] = $this->customerIds();
            $this->carts->saveForLater(
                $cart,
                (int)$this->request->getData('item_id'),
                $customerId,
                $userId,
                $this->carts->token($this->request),
            );
            $this->Flash->success(__('Saved for later.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function moveToCart(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $cart = $this->currentCart(true);
            [$customerId, $userId] = $this->customerIds();
            $this->carts->moveToCart(
                $cart,
                (int)$this->request->getData('saved_id'),
                $customerId,
                $userId,
                $this->carts->token($this->request),
            );
            $this->Flash->success(__('Moved to your basket.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return void
     */
    private function setCartView(): void
    {
        $token = $this->carts->token($this->request);
        [$customerId, $userId] = $this->customerIds();
        $cart = $this->carts->current($userId, $token, false);
        $totals = $this->carts->totals($cart);
        $cartLines = $this->carts->viewLines($cart);
        $savedLines = $this->carts->savedLines($customerId, $userId, $token);

        $this->set(compact('cartLines', 'savedLines', 'totals'));
        $this->set([
            'freeDeliveryFrom' => $totals['free_threshold_cents'] / 100,
            'deliveryFlat' => $totals['flat_rate_cents'] / 100,
            'subtotal' => $totals['subtotal_cents'] / 100,
            'delivery' => $totals['shipping_cents'] / 100,
            'total' => $totals['total_cents'] / 100,
            'gstIncluded' => $totals['gst_cents'] / 100,
            'awayFromFree' => $totals['away_cents'] / 100,
        ]);
    }

    /**
     * @param bool $create Create if missing.
     * @return \App\Model\Entity\Cart
     */
    private function currentCart(bool $create): mixed
    {
        $token = $this->carts->token($this->request);
        [, $userId] = $this->customerIds();
        $cart = $this->carts->current($userId, $token, $create);
        if ($cart === null) {
            throw new InvalidArgumentException('Your basket could not be opened.');
        }

        return $cart;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function customerIds(): array
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return [null, null];
        }
        $userId = (int)$identity->getIdentifier();
        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => $userId])
            ->first();

        return [$customer ? (int)$customer->id : null, $userId];
    }
}
