<?php

declare(strict_types=1);

namespace App\Policy;

use App\Security\AdminPermissionMap;
use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;

class RequestPolicy
{
    public function canAccess(
        ?IdentityInterface $identity,
        ServerRequest $request
    ): bool {
        $prefix = (string)$request->getParam('prefix');

        if ($prefix !== 'Admin') {
            return true;
        }

        if ($identity === null) {

            return true;
        }

        $permissions = AdminPermissionMap::permissionsFor(
            (string)$request->getParam('controller'),
            (string)$request->getParam('action')
        );

        if ($permissions === []) {
            return false;
        }

        if (
            !method_exists($identity, 'hasPermission')
            && !method_exists($identity, 'can')
        ) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (
                method_exists($identity, 'hasPermission')
                && $identity->hasPermission($permission)
            ) {
                return true;
            }

            if (
                !method_exists($identity, 'hasPermission')
                && method_exists($identity, 'can')
                && $identity->can($permission)
            ) {
                return true;
            }
        }

        return false;
    }
}
