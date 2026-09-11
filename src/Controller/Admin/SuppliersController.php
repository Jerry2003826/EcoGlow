<?php
// src/Controller/Admin/SuppliersController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class SuppliersController extends AdminController
{

    protected function requireAccessPermission(): void
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }
        if ($identity->get('UserRole') == 'staff') {
            throw new ForbiddenException('Forbidden');
        }

        $service = new PermissionService();

        if (!$service->hasPermission(
            (int)$identity->getIdentifier(),
            'access.manage'
        )) {
            throw new ForbiddenException('Forbidden');
        }
    }

    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->requireAccessPermission();

    }

    public function index()
    {
        if ($this->request->getAttribute('identity') === null) {
            return;
        }


        $suppliers = $this->fetchTable('Suppliers')
            ->find()
            ->contain(['Products'])
            ->orderBy(['SupplierName' => 'ASC'])
            ->all();

        $this->set(compact('suppliers'));
    }
}
