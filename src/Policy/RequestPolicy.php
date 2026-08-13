<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AdminPermissionMap;
use App\Service\Authorization\PermissionService;
use Authorization\IdentityInterface;
use Authorization\Policy\RequestPolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\Http\ServerRequest;

/**
 * Request-level authorization. Public storefront routes are allowed through;
 * every Admin prefix action is checked against the RBAC tables.
 */
class RequestPolicy implements RequestPolicyInterface
{
    /**
     * Permission resolver shared with the admin layout.
     *
     * @var \App\Service\Authorization\PermissionService
     */
    private PermissionService $permissions;

    /**
     * Constructor.
     *
     * @param \App\Service\Authorization\PermissionService|null $permissions Permission resolver.
     */
    public function __construct(?PermissionService $permissions = null)
    {
        $this->permissions = $permissions ?? new PermissionService();
    }

    /**
     * @inheritDoc
     */
    public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool|ResultInterface
    {
        $prefix = $request->getParam('prefix');
        $controller = (string)$request->getParam('controller');
        if ($prefix !== 'Admin' || $controller === 'Error') {
            return true;
        }

        if ($identity === null) {
            // AuthenticationComponent issues the login redirect for gated
            // actions. Denying here would turn that 302 into a 403.
            return true;
        }

        $userId = (int)$identity->getIdentifier();
        $action = (string)$request->getParam('action');
        $required = AdminPermissionMap::requiredKeys($controller, $action);

        if ($required === []) {
            return new Result(false, 'This staff action is not mapped to a permission.');
        }

        if ($this->permissions->hasAnyOf($userId, $required)) {
            return true;
        }

        return new Result(false, 'You do not have permission to do that.');
    }
}
