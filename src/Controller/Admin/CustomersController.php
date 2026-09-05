<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class CustomersController extends AdminController
{
    protected function can(int $userId, array $keys): bool
    {
        $service = new PermissionService();

        foreach ($keys as $key) {
            if ($service->hasPermission($userId, $key)) {
                return true;
            }
        }

        return false;
    }

    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->getIdentifier();

        if (!$this->can($userId, ['customers.view', 'customers.edit'])) {
            throw new ForbiddenException();
        }

        $customers = $this->fetchTable('Customers')
            ->find()
            ->orderBy([
                'Customers.first_name' => 'ASC',
                'Customers.last_name' => 'ASC',
            ])
            ->all();

        $this->set(compact('customers'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->getIdentifier();

        if (!$this->can($userId, ['customers.view', 'customers.edit'])) {
            throw new ForbiddenException();
        }

        $service = new PermissionService();

        $customer = $this->fetchTable('Customers')->get($id);

        $canViewAge = $service->hasPermission($userId, 'customers.sensitive.view');

        $this->set(compact('customer', 'canViewAge'));
    }

    public function edit(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->getIdentifier();

        $service = new PermissionService();

        if (!$service->hasPermission($userId, 'customers.edit')) {
            throw new ForbiddenException();
        }

        $customer = $this->fetchTable('Customers')->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            if (!$service->hasPermission($userId, 'customers.sensitive.view')) {
                unset($data['age']);
            }

            $customer = $this->fetchTable('Customers')
                ->patchEntity($customer, $data);

            if ($this->fetchTable('Customers')->save($customer)) {
                return $this->redirect([
                    'action' => 'view',
                    $customer->id,
                ]);
            }
        }

        $canViewAge = $service->hasPermission($userId, 'customers.sensitive.view');

        $this->set(compact('customer', 'canViewAge'));
    }
}
