<?php

declare(strict_types=1);

namespace App\Identity;

use App\Service\PermissionService;
use Authentication\IdentityInterface as AuthenticationIdentity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;

class PermissionIdentity extends IdentityDecorator implements IdentityInterface, AuthenticationIdentity
{
    public function __construct(
        AuthorizationServiceInterface $authorizationService,
        $identity
    ) {
        parent::__construct(
            $authorizationService,
            $identity
        );
    }

    public function getIdentifier(): array|string|int|null
    {
        if (is_object($this->identity) && method_exists($this->identity, 'getIdentifier')) {
            return $this->identity->getIdentifier();
        }

        return $this->identity['id'] ?? null;
    }

    public function hasPermission(string $permissionKey): bool
    {
        $service = new PermissionService();

        return $service->hasPermission(
            (int)$this->getIdentifier(),
            $permissionKey
        );
    }
}
