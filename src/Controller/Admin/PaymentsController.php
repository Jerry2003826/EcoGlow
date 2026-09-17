<?php
// src/Controller/Admin/PaymentsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class PaymentsController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('payments.view');

        $payments = $this->fetchTable('Payments')
            ->find()
            ->contain(['Orders.Customers', 'Orders.Guests'])
            ->orderBy(['PaymentId' => 'DESC'])
            ->all();

        $this->set(compact('payments'));
    }
}
