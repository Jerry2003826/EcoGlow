<?php
// src/Controller/Admin/InventoryController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class InventoryController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $items = $this->fetchTable('Inventory')
            ->find()
            ->contain(['Products.Suppliers'])
            ->orderBy(['Inventory.Quantity' => 'ASC'])
            ->all();

        $categories = $this->fetchTable('ProductCategories')
            ->find()
            ->contain(['Products'])
            ->orderBy(['CategoryName' => 'ASC'])
            ->all();

        $this->set(compact('items', 'categories'));
    }

    public function edit($id = null)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $inventory = $this->fetchTable('Inventory')
            ->find()
            ->contain(['Products'])
            ->where(['Inventory.ProductId' => (int)$id])
            ->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $inventory = $this->fetchTable('Inventory')->patchEntity(
                $inventory,
                $this->request->getData()
            );

            if ($this->fetchTable('Inventory')->save($inventory)) {
                $this->Flash->success(__('The inventory has been saved.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The inventory could not be saved. Please, try again.'));
        }

        $this->set(compact('inventory'));
    }

    public function addCategory()
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('inventory.adjust');

        $table = $this->fetchTable('ProductCategories');
        $category = $table->newEntity([
            'CategoryName' => trim((string)$this->request->getData('CategoryName')),
        ]);

        if ($table->save($category)) {
            $this->Flash->success('Category saved.');
        } else {
            $this->Flash->error('The category could not be saved.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function deleteCategory(int|string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('inventory.adjust');

        $category = $this->fetchTable('ProductCategories')->get(
            (int)$id,
            contain: ['Products']
        );

        if (!empty($category->products)) {
            $this->Flash->error('Category cannot be deleted while products exist.');

            return $this->redirect(['action' => 'index']);
        }

        if ($this->fetchTable('ProductCategories')->delete($category)) {
            $this->Flash->success('Category deleted.');
        } else {
            $this->Flash->error('The category could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
