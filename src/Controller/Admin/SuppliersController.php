<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class SuppliersController extends AdminController
{
    public function index()
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

        $suppliers = $this->fetchTable('Suppliers')
            ->find()
            ->contain(['Products'])
            ->orderBy([
                'Suppliers.name' => 'ASC',
            ])
            ->all();

        $this->set(compact('suppliers'));
    }

    public function view(int|string $id)
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

        $supplier = $this->fetchTable('Suppliers')
            ->get($id, contain: ['Products']);

        $this->set(compact('supplier'));
    }
}
