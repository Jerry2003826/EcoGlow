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

    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('shipments.view');

        $table = $this->fetchTable('Delivery');
        $shipment = $table->get((int)$id, contain: ['Orders']);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $rawDate = trim((string)($data['DeliveryDate'] ?? ''));
            $data['DeliveryDate'] = $rawDate === ''
                ? null
                : str_replace('T', ' ', $rawDate) . (strlen($rawDate) === 16 ? ':00' : '');

            $shipment = $table->patchEntity($shipment, $data, [
                'fields' => ['DeliveryAddress', 'DeliveryStatus', 'DeliveryDate'],
            ]);

            if ($table->save($shipment)) {
                $this->Flash->success('Shipment updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The shipment could not be updated.');
        }

        $this->set(compact('shipment'));
    }
}
