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

        $this->set(compact('items'));
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
}
