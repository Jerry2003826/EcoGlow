<?php
// src/Controller/Admin/SuppliersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class SuppliersController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('suppliers.view');

        $suppliers = $this->fetchTable('Suppliers')
            ->find()
            ->contain(['Products'])
            ->orderBy(['SupplierName' => 'ASC'])
            ->all();

        $this->set(compact('suppliers'));
    }
}
