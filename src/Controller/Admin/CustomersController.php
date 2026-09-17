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

    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('customers.edit');

        $table = $this->fetchTable('Customers');
        $customer = $table->get((int)$id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $customer = $table->patchEntity($customer, $this->request->getData(), [
                'fields' => ['CustomerName', 'Email', 'Address', 'Phone'],
            ]);

            if ($table->save($customer)) {
                $this->Flash->success('Customer updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The customer could not be updated.');
        }

        $this->set(compact('customer'));
    }

    public function editGuest(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('customers.edit');

        $table = $this->fetchTable('Guests');
        $guest = $table->get((int)$id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $guest = $table->patchEntity($guest, $this->request->getData(), [
                'fields' => ['GuestName', 'Email', 'Address', 'Phone'],
            ]);

            if ($table->save($guest)) {
                $this->Flash->success('Guest updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('The guest could not be updated.');
        }

        $this->set(compact('guest'));
    }
}
