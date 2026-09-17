<?php
// src/Identity/PermissionIdentity.php

declare(strict_types=1);

namespace App\Identity;

use App\Service\PermissionService;
use Authentication\IdentityInterface as AuthenticationIdentityInterface;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface as AuthorizationIdentityInterface;

class PermissionIdentity extends IdentityDecorator implements
    AuthorizationIdentityInterface,
    AuthenticationIdentityInterface
{
    public function __construct(
        AuthorizationServiceInterface $authorizationService,
        $identity
    ) {
        parent::__construct($authorizationService, $identity);
    }

    public function getIdentifier(): array|string|int|null
    {
        $data = $this->getOriginalData();

        return $data->UserId
            ?? $data->id
            ?? null;
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
