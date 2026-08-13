<?php
declare(strict_types=1);

namespace App\Authorization;

/**
 * Maps admin controller actions onto permission_key values that already exist
 * in the permissions table. The keys themselves are never invented here —
 * only which action requires which existing key.
 *
 * Batch 2 can extend this map when the remaining modules land; the resolver
 * stays data-driven against roles / role_permissions / overrides.
 */
final class AdminPermissionMap
{
    /**
     * Sentinel meaning "any active RBAC permission".
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
                self::ANY => [self::ANY],
            ],
            'ComingSoon' => [
                self::ANY => [self::ANY],
            ],
            'ContactMessages' => [
                self::ANY => ['messages.manage'],
            ],
            'Customers' => [
                self::ANY => ['customers.view', 'customers.edit'],
            ],
            'Invoices' => [
                'createFromOrder' => ['invoices.issue'],
                'send' => ['invoices.issue'],
                'recordPayment' => ['payments.record'],
                self::ANY => ['invoices.issue'],
            ],
            'Reports' => [
                self::ANY => ['reports.view', 'reports.financial'],
            ],
            'Users' => [
                self::ANY => ['access.manage'],
            ],
            'Orders' => [
                'add' => ['orders.create'],
                'searchProducts' => ['orders.create'],
                'searchCustomers' => ['orders.create'],
                'updatePromisedDate' => ['orders.manage'],
                'addNote' => ['orders.manage'],
                'updateStatus' => ['orders.manage', 'orders.dispatch'],
                self::ANY => ['orders.view', 'orders.create', 'orders.manage', 'orders.dispatch'],
            ],
            'Inventory' => [
                'adjust' => ['inventory.adjust'],
                self::ANY => ['inventory.view', 'inventory.adjust'],
            ],
        ];

        if (!isset($map[$controller])) {
            return [];
        }

        $actions = $map[$controller];
        if (isset($actions[$action])) {
            return $actions[$action];
        }
        if (isset($actions[self::ANY])) {
            return $actions[self::ANY];
        }

        return [];
    }
}
