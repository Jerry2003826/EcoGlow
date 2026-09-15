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
                'updateStock' => ['inventory.adjust'],
            ],
            'ComingSoon' => [
                'index' => ['dashboard.view'],
            ],
            'Orders' => [
                'index' => ['orders.view'],
                'view' => ['orders.view'],
                'updateDelivery' => ['orders.view'],
                'updateStatus' => ['orders.view'],
                'addRepair' => ['orders.view'],
            ],
            'Inventory' => [
                'index' => ['inventory.view', 'inventory.adjust'],
                'edit' => ['inventory.adjust'],
            ],
            'Suppliers' => [
                'index' => ['suppliers.view'],
                'view' => ['suppliers.view'],
                'contact' => ['suppliers.view'],
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
                'index' => ['reports.financial'],
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
                'generate' => ['invoices.issue'],
            ],
            'Payments' => [
                'index' => ['payments.view'],
            ],
            'Shipments' => [
                'index' => ['shipments.view'],
            ],
            'Products' => [
                'index' => ['products.view'],
            ],
            'Access' => [
                'index' => ['access.manage'],
                'updateRole' => ['access.manage'],
            ],
        ];

        return $map[$controller][$action] ?? [];
    }
}
