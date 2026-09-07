<?php
// src/Controller/ProductsController.php

declare(strict_types=1);

namespace App\Controller;

class ProductsController extends AppController
{
    public function index()
    {
        $q = trim((string)$this->request->getQuery('q'));
        $category = (string)$this->request->getQuery('category');

        $query = $this->fetchTable('Products')
            ->find()
            ->contain(['Inventory']);

        if ($q !== '') {
            $query->where([
                'OR' => [
                    'ProductName LIKE' => '%' . $q . '%',
                    'Description LIKE' => '%' . $q . '%',
                ],
            ]);
        }

        if ($category !== '') {
            $query->where([
                'CategoryId' => (int)$category,
            ]);
        }

        if ($this->request->is('post')) {
            $productId = (int)$this->request->getData('product_id');

            $cartId = $this->request->getSession()->read('CartId');

            if (!$cartId) {
                $cart = $this->fetchTable('ShoppingCarts')->newEntity([
                    'InstallOptIn' => 0,
                    'InstallFee' => 200.00,
                ]);

                $this->fetchTable('ShoppingCarts')->saveOrFail($cart);

                $cartId = $cart->CartId;

                $this->request->getSession()->write('CartId', $cartId);
            }

            $cartItems = $this->fetchTable('CartItems');

            $item = $cartItems->find()
                ->where([
                    'CartId' => $cartId,
                    'ProductId' => $productId,
                ])
                ->first();

            if ($item) {
                $item->Quantity += 1;
            } else {
                $item = $cartItems->newEntity([
                    'CartId' => $cartId,
                    'ProductId' => $productId,
                    'Quantity' => 1,
                ]);
            }

            $cartItems->saveOrFail($item);

            $this->Flash->success(
                'Added to cart. Continue shopping or view cart.'
            );

            return $this->redirect([
                'action' => 'index',
                '?' => [
                    'q' => $q,
                    'category' => $category,
                ],
            ]);
        }

        $products = $query->all();

        $categories = $this->fetchTable('ProductCategories')
            ->find()
            ->all();

        $this->set(compact(
            'products',
            'q',
            'category',
            'categories'
        ));
    }
}
