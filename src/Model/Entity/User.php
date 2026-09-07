<?php
// src/Model/Entity/User.php

declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'Email' => true,
        'Password' => true,
        'UserRole' => true,
        'Username' => true,
        'Status' => true,
        'customer' => true,
    ];

    protected function _setPassword(?string $password): ?string
    {
        if (!$password) {
            return $password;
        }

        return (new DefaultPasswordHasher())->hash($password);
    }

    protected function _getPassword(): ?string
    {
        return $this->_fields['Password'] ?? null;
    }

    public function getIdentifier()
    {
        return $this->UserId;
    }
}
