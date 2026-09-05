<?php

declare(strict_types=1);

namespace App\Security;

final class AdminPermissionMap
{
    public static function permissionsFor(
        string $controller,
        string $action
    ): array {
        $map = [
            'Dashboard' => [
                'index' => [
                    'dashboard.view',
                ],
            ],

            'ComingSoon' => [
                'index' => [
                    'dashboard.view',
                ],
            ],

            'Orders' => [
                'index' => [
                    'orders.view',
                    'services.manage',
                ],
                'view' => [
                    'orders.view',
                ],
                'booking' => [
                    'services.manage',
                ],
            ],

            'Inventory' => [
                'index' => [
                    'inventory.view',
                    'inventory.adjust',
                ],
            ],

            'Suppliers' => [
                'index' => [
                    'purchasing.manage',
                ],
                'view' => [
                    'purchasing.manage',
                ],
            ],

            'Customers' => [
                'index' => [
                    'customers.view',
                    'customers.edit',
                ],
                'view' => [
                    'customers.view',
                    'customers.edit',
                ],
                'edit' => [
                    'customers.edit',
                ],
            ],

            'Quotations' => [
                'index' => [
                    'quotations.manage',
                ],
                'view' => [
                    'quotations.manage',
                ],
                'add' => [
                    'quotations.manage',
                ],
            ],

            'Invoices' => [
                'index' => [
                    'invoices.issue',
                ],
                'view' => [
                    'invoices.issue',
                ],
                'add' => [
                    'invoices.issue',
                ],
            ],

            'Messages' => [
                'index' => [
                    'messages.manage',
                ],
                'view' => [
                    'messages.manage',
                ],
            ],

            'Reports' => [
                'financial' => [
                    'reports.financial',
                ],
            ],
        ];

        return $map[$controller][$action] ?? [];
    }
}
