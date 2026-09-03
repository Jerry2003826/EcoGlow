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
                ],
            ],

            'Inventory' => [
                'index' => [
                    'inventory.view',
                    'inventory.adjust',
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
