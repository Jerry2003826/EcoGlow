<?php
// src/Controller/Admin/SuppliersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class SuppliersController extends AdminController
{
    protected function ensurePermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'purchasing.manage'
            )
        ) {
            throw new ForbiddenException();
        }
    }

    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->ensurePermission();

        $suppliers = $this->fetchTable('Suppliers')
            ->find()
            ->contain(['Products'])
            ->orderBy(['Suppliers.name' => 'ASC'])
            ->all();

        $this->set(compact('suppliers'));
    }

    public function view(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->ensurePermission();

        $supplier = $this->fetchTable('Suppliers')
            ->get($id, contain: ['Products']);

        $this->set(compact('supplier'));
    }

    public function edit(int|string $id)
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }

        $this->ensurePermission();

        $suppliers = $this->fetchTable('Suppliers');

        $supplier = $suppliers->get($id, contain: ['Products']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $supplier = $suppliers->patchEntity(
                $supplier,
                $this->request->getData()
            );

            if ($suppliers->save($supplier)) {
                $this->Flash->success('Supplier updated.');

                return $this->redirect([
                    'action' => 'view',
                    $supplier->id,
                ]);
            }

            $this->Flash->error('Unable to save supplier.');
        }

        $this->set(compact('supplier'));
    }
}
