<?php
// src/Controller/Admin/SuppliersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Mailer\SupplierMailer;

class SuppliersController extends AdminController
{
    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('suppliers.view');

        $suppliersTable = $this->fetchTable('Suppliers');

        $suppliers = $suppliersTable
            ->find()
            ->contain(['Products'])
            ->orderBy(['SupplierName' => 'ASC'])
            ->all();

        $supplierOptions = $suppliersTable
            ->find('list', [
                'keyField' => 'SupplierId',
                'valueField' => 'SupplierName',
            ])
            ->orderBy(['SupplierName' => 'ASC'])
            ->toArray();

        $this->set(compact('suppliers', 'supplierOptions'));
    }

    public function contact()
    {
        $this->request->allowMethod(['post']);

        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->requirePermission('suppliers.view');

        $identity = $this->request->getAttribute('identity');
        $supplierId = (int)$this->request->getData('supplier_id');
        $subject = trim((string)$this->request->getData('subject'));
        $message = trim((string)$this->request->getData('message'));

        $supplier = $this->fetchTable('Suppliers')
            ->find()
            ->where(['SupplierId' => $supplierId])
            ->first();

        if (!$supplier) {
            $this->Flash->error('Supplier not found.');

            return $this->redirect(['action' => 'index']);
        }

        if (empty($supplier->Email)) {
            $this->Flash->error('This supplier does not have an email address.');

            return $this->redirect(['action' => 'index']);
        }

        if ($subject === '' || $message === '') {
            $this->Flash->error('Subject and message are required.');

            return $this->redirect(['action' => 'index']);
        }

        try {
            (new SupplierMailer())->send('supplierContact', [
                $supplier,
                $subject,
                $message,
                (string)($identity?->get('Email') ?? ''),
            ]);
            $this->Flash->success('Supplier email sent successfully.');
        } catch (\Throwable $exception) {
            $this->Flash->error(
                'The email could not be delivered from this host. Please try again later.'
            );
        }

        return $this->redirect(['action' => 'index']);
    }
}
