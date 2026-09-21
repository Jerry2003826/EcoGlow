<?php
// src/Security/AdminPermissionMap.php
declare(strict_types=1);

namespace App\Security;

final class AdminPermissionMap
{
    /**
     * Return the permission keys required for an admin controller action.
     *
     * @param string $controller Controller name.
     * @param string $action Action name.
     * @return array<int, string>
     */
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
                'index' => ['inventory.view', 'inventory.adjust', 'products.view'],
                'edit' => ['inventory.adjust'],
                'addCategory' => ['inventory.adjust'],
                'deleteCategory' => ['inventory.adjust'],
            ],
            'Suppliers' => [
                'index' => ['suppliers.view'],
                'view' => ['suppliers.view'],
                'contact' => ['suppliers.view'],
                'add' => ['suppliers.view'],
                'edit' => ['suppliers.view'],
                'delete' => ['suppliers.view'],
            ],
            'Customers' => [
                'index' => ['customers.view', 'customers.edit'],
                'view' => ['customers.view', 'customers.edit'],
                'edit' => ['customers.edit'],
                'editGuest' => ['customers.edit'],
            ],
            'Messages' => [
                'index' => ['messages.manage'],
                'view' => ['messages.manage'],
                'contact' => ['messages.manage'],
                'updateStatus' => ['messages.manage'],
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
                'edit' => ['shipments.view'],
            ],
            'Products' => [
                'index' => ['products.view', 'inventory.view', 'inventory.adjust'],
                'add' => ['products.view'],
                'edit' => ['products.view'],
                'delete' => ['products.view'],
            ],
            'Access' => [
                'index' => ['access.manage'],
                'updateRole' => ['access.manage'],
            ],
            'Content' => [
                'index' => ['content.manage'],
                'saveText' => ['content.manage'],
                'addBlock' => ['content.manage'],
                'editBlock' => ['content.manage'],
                'deleteBlock' => ['content.manage'],
                'moveBlock' => ['content.manage'],
                'toggleBlock' => ['content.manage'],
            ],
        ];

        return $map[$controller][$action] ?? [];
    }
}
