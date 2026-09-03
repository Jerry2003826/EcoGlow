<?php

declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;
use DateTimeImmutable;

class PermissionService
{
    public function hasPermission(
        int $userId,
        string $permissionKey
    ): bool {
        $permissionsTable = TableRegistry::getTableLocator()
            ->get('Permissions');

        $overridesTable = TableRegistry::getTableLocator()
            ->get('UserPermissionOverrides');

        $userRolesTable = TableRegistry::getTableLocator()
            ->get('UserRoles');

        $permission = $permissionsTable
            ->find()
            ->select(['id'])
            ->where([
                'permission_key' => $permissionKey,
            ])
            ->first();

        if ($permission === null) {
            return false;
        }

        $override = $overridesTable
            ->find()
            ->where([
                'user_id' => $userId,
                'permission_id' => $permission->id,
            ])
            ->first();

        if ($override !== null && $override->effect === 'deny') {
            return false;
        }

        if ($override !== null && $override->effect === 'allow') {
            return true;
        }

        $now = new DateTimeImmutable('now');

        $count = $userRolesTable
            ->find()
            ->matching('Roles.RolePermissions.Permissions', function ($q) use ($permissionKey) {
                return $q->where([
                    'Permissions.permission_key' => $permissionKey,
                ]);
            })
            ->where([
                'UserRoles.user_id' => $userId,
                'UserRoles.revoked_at IS' => null,
            ])
            ->andWhere(function ($exp) use ($now) {
                return $exp->or([
                    'UserRoles.starts_at IS' => null,
                    'UserRoles.starts_at <=' => $now,
                ]);
            })
            ->andWhere(function ($exp) use ($now) {
                return $exp->or([
                    'UserRoles.ends_at IS' => null,
                    'UserRoles.ends_at >=' => $now,
                ]);
            })
            ->count();

        return $count > 0;
    }
}
