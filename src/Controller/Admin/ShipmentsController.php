<?php
// src/Controller/Admin/ShipmentsController.php

declare(strict_types=1);

namespace App\Controller\Admin;

class ShipmentsController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('shipments.view');

        $shipments = $this->fetchTable('Delivery')
            ->find()
            ->contain(['Orders.Customers', 'Orders.Guests'])
            ->orderBy(['DeliveryId' => 'DESC'])
            ->all();

        $this->set(compact('shipments'));
    }
}
