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
}
