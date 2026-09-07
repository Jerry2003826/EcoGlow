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

        $this->requirePermission('inventory.view');

        $items = $this->fetchTable('Inventory')
            ->find()
            ->contain(['Products.Suppliers'])
            ->orderBy(['Inventory.Quantity' => 'ASC'])
            ->all();

        $this->set(compact('items'));
    }
}
