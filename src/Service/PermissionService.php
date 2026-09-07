<?php
// src/Service/PermissionService.php

declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

class PermissionService
{
    public function hasPermission(int $userId, string $permission): bool
    {
        $user = TableRegistry::getTableLocator()
            ->get('Users')
            ->find()
            ->where(['UserId' => $userId])
            ->first();

        if (!$user) {
            return false;
        }

        if ($user->UserRole === 'admin') {
            return true;
        }

        if ($user->UserRole === 'staff') {
            return in_array($permission, [
                'dashboard.view',
                'orders.view',
                'customers.view',
                'invoices.issue',
            ], true);
        }

        return false;
    }
}
