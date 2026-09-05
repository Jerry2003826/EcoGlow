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

            'Messages' => [
                'index' => [
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
