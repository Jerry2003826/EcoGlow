<?php
declare(strict_types=1);

namespace App\Authorization;

/**
 * Maps admin controller actions onto permission_key values that already exist
 * in the permissions table. Unknown actions are denied.
 */
final class AdminPermissionMap
{
    /**
     * Sentinel kept for callers that still test the any-permission path.
     *
     * @var string
     */
    public const ANY = '*';

    /**
     * Permission keys required for a controller action.
     *
     * An empty list means the action is unknown and must be denied.
     *
     * @param string $controller CakePHP controller name (no prefix).
     * @param string $action CakePHP action name.
     * @return array<int, string>
     */
    public static function requiredKeys(string $controller, string $action): array
    {
        $map = [
            'Dashboard' => [
                'index' => [
                    'orders.view',
                    'customers.view',
                    'messages.manage',
                    'inventory.view',
                    'reports.view',
                    'reports.financial',
                ],
            ],
            'ComingSoon' => [
                'index' => [
                    'orders.view',
                    'customers.view',
                    'inventory.view',
                    'access.manage',
                    'invoices.issue',
                    'reports.view',
                    'catalogue.manage',
                    'quotations.manage',
                ],
            ],
            'ContactMessages' => [
                'index' => ['messages.manage'],
                'view' => ['messages.manage'],
                'markRead' => ['messages.manage'],
                'reply' => ['messages.manage'],
                'updateStatus' => ['messages.manage'],
                'assign' => ['messages.manage'],
                'delete' => ['messages.manage'],
            ],
            'Customers' => [
                'index' => ['customers.view', 'customers.edit'],
                'view' => ['customers.view', 'customers.edit'],
            ],
            'Invoices' => [
                'index' => ['invoices.issue'],
                'view' => ['invoices.issue'],
                'createFromOrder' => ['invoices.issue'],
                'send' => ['invoices.issue'],
                'recordPayment' => ['payments.record'],
                'refund' => ['refunds.process'],
            ],
            'Reports' => [
                'index' => ['reports.view'],
                'financial' => ['reports.financial'],
            ],
            'Users' => [
                'index' => ['access.manage'],
                'toggleActive' => ['access.manage'],
                'updateRoles' => ['access.manage'],
                'updateRolePermissions' => ['access.manage'],
                'updateMatrix' => ['access.manage'],
                'setOverride' => ['access.manage'],
                'revokeSessions' => ['access.manage'],
            ],
            'Orders' => [
                'index' => ['orders.view', 'orders.create', 'orders.manage', 'orders.dispatch'],
                'view' => ['orders.view', 'orders.create', 'orders.manage', 'orders.dispatch'],
                'add' => ['orders.create'],
                'searchProducts' => ['orders.create'],
                'searchCustomers' => ['orders.create'],
                'updatePromisedDate' => ['orders.manage'],
                'addNote' => ['orders.manage'],
                'updateStatus' => ['orders.manage', 'orders.dispatch'],
                'refund' => ['refunds.process'],
            ],
            'Appointments' => [
                'index' => ['services.manage', 'services.dispatch'],
                'view' => ['services.manage', 'services.dispatch'],
                'schedule' => ['services.dispatch'],
                'updateStatus' => ['services.manage', 'services.dispatch'],
                'addWorkLog' => ['services.manage'],
                'addPart' => ['services.manage'],
            ],
            'Inventory' => [
                'index' => ['inventory.view', 'inventory.adjust'],
                'adjust' => ['inventory.adjust'],
            ],
        ];

        return $map[$controller][$action] ?? [];
    }
}
