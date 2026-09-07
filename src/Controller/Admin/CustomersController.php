<?php
// src/Controller/Admin/CustomersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class CustomersController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('customers.view');

        $customers = $this->fetchTable('Customers')
            ->find()
            ->orderBy(['CustomerName' => 'ASC'])
            ->all();

        $guests = $this->fetchTable('Guests')
            ->find()
            ->orderBy(['GuestId' => 'DESC'])
            ->all();

        $this->set(compact('customers', 'guests'));
    }
}
