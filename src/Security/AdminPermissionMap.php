<?php
// src/Security/AdminPermissionMap.php

declare(strict_types=1);

namespace App\Security;

final class AdminPermissionMap
{
    public static function permissionsFor(string $controller, string $action): array
    {
        $map = [
            'Dashboard' => [
                'index' => ['dashboard.view'],
            ],
            'ComingSoon' => [
                'index' => ['dashboard.view'],
            ],
            'Orders' => [
                'index' => ['orders.view', 'services.manage'],
                'view' => ['orders.view'],
                'booking' => ['services.manage'],
                'add' => ['orders.create', 'orders.manage'],
                'addBooking' => ['services.manage'],
                'updateStatus' => ['orders.manage'],
                'updateDelivery' => ['orders.manage'],
                'updateBooking' => ['services.manage'],
            ],
            'Inventory' => [
                'index' => ['inventory.view', 'inventory.adjust'],
            ],
            'Suppliers' => [
                'index' => ['purchasing.manage'],
                'view' => ['purchasing.manage'],
                'edit' => ['purchasing.manage'],
            ],
            'Customers' => [
                'index' => ['customers.view', 'customers.edit'],
                'view' => ['customers.view', 'customers.edit'],
                'edit' => ['customers.edit'],
            ],
            'Messages' => [
                'index' => ['messages.manage'],
                'view' => ['messages.manage'],
            ],
            'Reports' => [
                'financial' => ['reports.financial'],
            ],
            'Quotations' => [
                'index' => ['quotations.manage'],
                'view' => ['quotations.manage'],
                'add' => ['quotations.manage'],
            ],
            'Invoices' => [
                'index' => ['invoices.issue'],
                'view' => ['invoices.issue'],
                'add' => ['invoices.issue'],
            ],
            'Access' => [
                'index' => ['access.manage'],
                'updateRole' => ['access.manage'],
            ],
        ];

        return $map[$controller][$action] ?? [];
    }
}
