<?php
// src/Controller/Admin/ProductsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class ProductsController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $products = $this->fetchTable('Products')
            ->find()
            ->contain(['ProductCategories', 'Suppliers', 'Inventory'])
            ->orderBy(['ProductName' => 'ASC'])
            ->all();

        $this->set(compact('products'));
    }

    public function add()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $productsTable = $this->fetchTable('Products');
        $product = $productsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $product = $productsTable->patchEntity($product, $this->request->getData());

            if ($productsTable->save($product)) {
                $inventoryTable = $this->fetchTable('Inventory');
                $existing = $inventoryTable
                    ->find()
                    ->where(['ProductId' => (int)$product->ProductId])
                    ->first();

                if (!$existing) {
                    $inventoryTable->save($inventoryTable->newEntity([
                        'ProductId' => (int)$product->ProductId,
                        'Quantity' => 0,
                        'LowStockThreshold' => 5,
                    ]));
                }

                $this->Flash->success('Product saved.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The product could not be saved.');
        }

        $this->setProductFormLists();
        $this->set(compact('product'));
    }

    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $productsTable = $this->fetchTable('Products');
        $product = $productsTable->get((int)$id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $product = $productsTable->patchEntity($product, $this->request->getData());

            if ($productsTable->save($product)) {
                $this->Flash->success('Product updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The product could not be updated.');
        }

        $this->setProductFormLists();
        $this->set(compact('product'));
    }

    public function delete(int|string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('products.view');

        $orderItem = $this->fetchTable('OrderItems')
            ->find()
            ->where(['ProductId' => (int)$id])
            ->first();

        if ($orderItem) {
            $this->Flash->error('Product cannot be deleted while it appears on an order.');

            return $this->redirect(['action' => 'index']);
        }

        $product = $this->fetchTable('Products')->get((int)$id);
        $inventory = $this->fetchTable('Inventory')
            ->find()
            ->where(['ProductId' => (int)$id])
            ->first();

        if ($inventory) {
            $this->fetchTable('Inventory')->delete($inventory);
        }

        if ($this->fetchTable('Products')->delete($product)) {
            $this->Flash->success('Product deleted.');
        } else {
            $this->Flash->error('The product could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    private function setProductFormLists(): void
    {
        $categories = $this->fetchTable('ProductCategories')
            ->find('list', [
                'keyField' => 'CategoryId',
                'valueField' => 'CategoryName',
            ])
            ->orderBy(['CategoryName' => 'ASC'])
            ->toArray();

        $suppliers = $this->fetchTable('Suppliers')
            ->find('list', [
                'keyField' => 'SupplierId',
                'valueField' => 'SupplierName',
            ])
            ->orderBy(['SupplierName' => 'ASC'])
            ->toArray();

        $this->set(compact('categories', 'suppliers'));
    }
}
