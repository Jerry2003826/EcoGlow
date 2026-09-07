<?php
// src/Controller/Admin/OrdersController.php

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

        if (
            !$permissionService->hasPermission(
                (int)$identity->getIdentifier(),
                'orders.view'
            )
        ) {
            throw new ForbiddenException('Forbidden');
        }

        $q = trim((string)$this->request->getQuery('q'));

        $query = $this->fetchTable('Orders')
            ->find()
            ->contain(['Guests'])
            ->orderBy([
                'OrderDate' => 'DESC',
                'OrderId' => 'DESC',
            ]);

        if ($q !== '') {
            $orders = $query->all()->filter(function ($order) use ($q) {
                $guestName = $order->guest->GuestName ?? '';
                $guestEmail = $order->guest->Email ?? '';

                return str_contains((string)$order->OrderId, $q)
                    || stripos($guestName, $q) !== false
                    || stripos($guestEmail, $q) !== false;
            });
        } else {
            $orders = $query->all();
        }

        $this->set(compact('orders', 'q'));
    }
}
