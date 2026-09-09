<?php
// src/Controller/Admin/DashboardController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class DashboardController extends AdminController
{
    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $permissionService = new PermissionService();
        $userId = (int)$identity->getIdentifier();

        if (!$permissionService->hasPermission($userId, 'dashboard.view')) {
            throw new ForbiddenException('Forbidden');
        }

        $ordersTable = $this->fetchTable('Orders');
        $paymentsTable = $this->fetchTable('Payments');
        $inventoryTable = $this->fetchTable('Inventory');

        $today = date('Y-m-d');

        $widgets = [];

        $widgets['orders'] = [
            'orders_total' => $ordersTable->find()
                ->where([
                    'DATE(OrderDate) =' => $today,
                ])
                ->count(),
        ];

        $widgets['pendingShipment'] = $ordersTable->find()
            ->where([
                'Status <>' => 'delivered',
            ])
            ->count();

        $widgets['guestOrdersToday'] = $ordersTable->find()
            ->where([
                'DATE(OrderDate) =' => $today,
                'GuestId IS NOT' => null,
            ])
            ->count();

        $widgets['customerOrdersToday'] = $ordersTable->find()
            ->where([
                'DATE(OrderDate) =' => $today,
                'CustomerId IS NOT' => null,
            ])
            ->count();

        $recentOrders = $ordersTable
            ->find()
            ->contain(['Customers', 'Guests'])
            ->orderBy([
                'OrderDate' => 'DESC',
                'OrderId' => 'DESC',
            ])
            ->limit(8)
            ->all();

        $user = $this->fetchTable('Users')->get($userId);
        $isAdmin = $user->UserRole === 'admin';

        if ($isAdmin) {
            $lowStockItems = $inventoryTable->find()
                ->contain(['Products'])
                ->all();

            $filtered = [];

            foreach ($lowStockItems as $item) {
                if ((int)$item->Quantity <= (int)$item->LowStockThreshold) {
                    $filtered[] = [
                        'name' => $item->product->ProductName ?? '',
                        'quantity' => (int)$item->Quantity,
                    ];
                }
            }

            $widgets['inventory'] = [
                'total' => count($filtered),
            ];

            $widgets['lowStockItems'] = $filtered;
        }

        if ($isAdmin) {
            $totalRevenue = 0.00;

            $payments = $paymentsTable->find()
                ->orderBy([
                    'PaymentId' => 'DESC',
                ])
                ->all();

            foreach ($payments as $payment) {
                $totalRevenue += (float)$payment->Amount;
            }

            $widgets['financial'] = [
                'revenue' => $totalRevenue,
            ];

            $widgets['transactions'] = [];

            foreach ($payments as $payment) {
                $widgets['transactions'][] = [
                    'PaymentId' => $payment->PaymentId,
                    'Amount' => (float)$payment->Amount,
                    'PaymentDate' => $payment->PaymentDate ?? null,
                    'OrderId' => $payment->OrderId,
                ];
            }
        }

        $this->set(compact('widgets', 'recentOrders'));
    }
}
