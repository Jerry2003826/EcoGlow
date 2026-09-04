<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class OrdersController extends AdminController
{
    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();
        $userId = (int)$identity->getIdentifier();

        if (!$permissionService->hasPermission($userId, 'orders.view')) {
            throw new ForbiddenException('Forbidden');
        }

        $query = $this->fetchTable('SalesOrders')
            ->find()
            ->contain(['Customers'])
            ->orderBy([
                'SalesOrders.placed_at' => 'DESC',
                'SalesOrders.id' => 'DESC',
            ]);

        $orders = $this->paginate($query);

        $this->set(compact('orders'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();
        $userId = (int)$identity->getIdentifier();

        if (!$permissionService->hasPermission($userId, 'orders.view')) {
            throw new ForbiddenException('Forbidden');
        }

        $order = $this->fetchTable('SalesOrders')->get($id, contain: ['Customers']);

        $this->set(compact('order'));
    }
}
