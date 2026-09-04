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

        $userId = (int)$identity->getIdentifier();

        $permissionService = new PermissionService();

        $canViewOrders = $permissionService->hasPermission($userId, 'orders.view');
        $canManageServices = $permissionService->hasPermission($userId, 'services.manage');

        if (!$canViewOrders && !$canManageServices) {
            throw new ForbiddenException();
        }

        $orders = [];
        $bookings = [];

        if ($canViewOrders) {
            $orders = $this->paginate(
                $this->fetchTable('SalesOrders')
                    ->find()
                    ->contain(['Customers'])
                    ->orderBy([
                        'SalesOrders.placed_at' => 'DESC',
                        'SalesOrders.id' => 'DESC',
                    ])
            );
        }

        if ($canManageServices) {
            $bookings = $this->fetchTable('ServiceBookings')
                ->find()
                ->contain(['Customers'])
                ->orderBy([
                    'ServiceBookings.scheduled_at' => 'ASC',
                ])
                ->all();
        }

        $this->set(compact('orders', 'bookings', 'canViewOrders', 'canManageServices'));
    }

    public function view(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (!$permissionService->hasPermission((int)$identity->getIdentifier(), 'orders.view')) {
            throw new ForbiddenException();
        }

        $order = $this->fetchTable('SalesOrders')->get($id, contain: ['Customers']);

        $this->set(compact('order'));
    }

    public function booking(int|string $id)
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();

        if (!$permissionService->hasPermission((int)$identity->getIdentifier(), 'services.manage')) {
            throw new ForbiddenException();
        }

        $booking = $this->fetchTable('ServiceBookings')->get($id, contain: ['Customers']);

        $this->set(compact('booking'));
    }
}
