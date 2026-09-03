<?php

declare(strict_types=1);

namespace App\Service\Authorization;

use Cake\Datasource\ConnectionInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

final class PermissionService
{
    use LocatorAwareTrait;

    private array $resolved = [];

    private array $roles = [];

    public function has(int $userId, string $permissionKey): bool
    {
        $this->load($userId);
        return (bool)($this->resolved[$userId][$permissionKey] ?? false);
    }

    public function hasAnyOf(int $userId, array $permissionKeys): bool
    {
        foreach ($permissionKeys as $key) {
            if ($key === '*') {
                return $this->hasAny($userId);
            }
            if ($this->has($userId, $key)) {
                return true;
            }
        }

        return false;
    }

    public function hasAny(int $userId): bool
    {
        $this->load($userId);
        return count($this->resolved[$userId] ?? []) > 0;
    }

    public function forget(?int $userId = null): void
    {
        if ($userId === null) {
            $this->resolved = [];
            $this->roles = [];

            return;
        }
        unset($this->resolved[$userId], $this->roles[$userId]);
    }

    public function rolesFor(int $userId): array
    {
        $this->load($userId);

        return $this->roles[$userId] ?? [];
    }

    public function effectivePermissions(int $userId): array
    {
        $this->load($userId);

        return $this->resolved[$userId] ?? [];
    }

    private function load(int $userId): void
    {
        if (isset($this->resolved[$userId])) {
            return;
        }

        $user = $this->fetchTable('Users')->find()
            ->where([
                'Users.id' => $userId,
                'Users.status' => 'active',
                'Users.deleted IS' => null,
            ])
            ->first();
        if ($user === null) {
            $this->roles[$userId] = [];
            $this->resolved[$userId] = [];

            return;
        }

        $connection = $this->connection();
        $nowSql = 'UTC_TIMESTAMP(6)';

        $roles = $connection->execute(
            "SELECT r.id, r.role_key, r.name
               FROM user_roles ur
               INNER JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
              WHERE ur.user_id = ?
                AND ur.revoked_at IS NULL
                AND ur.starts_at <= {$nowSql}
                AND (ur.ends_at IS NULL OR ur.ends_at > {$nowSql})",
            [$userId],
            ['integer'],
        )->fetchAll('assoc');

        $this->roles[$userId] = $roles;

        $granted = [];
        if ($roles !== []) {
            $roleIds = array_map('intval', array_column($roles, 'id'));
            $placeholders = implode(', ', array_fill(0, count($roleIds), '?'));
            $rows = $connection->execute(
                "SELECT DISTINCT p.permission_key
                   FROM role_permissions rp
                   INNER JOIN permissions p ON p.id = rp.permission_id
                  WHERE rp.role_id IN ({$placeholders})",
                $roleIds,
            )->fetchAll('assoc');
            foreach ($rows as $row) {
                $granted[(string)$row['permission_key']] = true;
            }
        }

        $overrides = $connection->execute(
            "SELECT p.permission_key, o.effect
               FROM user_permission_overrides o
               INNER JOIN permissions p ON p.id = o.permission_id
              WHERE o.user_id = ?
                AND o.starts_at <= {$nowSql}
                AND (o.ends_at IS NULL OR o.ends_at > {$nowSql})
              ORDER BY o.id ASC",
            [$userId],
            ['integer'],
        )->fetchAll('assoc');

        $denied = [];
        $allowed = [];
        foreach ($overrides as $override) {
            $key = (string)$override['permission_key'];
            if ($override['effect'] === 'deny') {
                $denied[$key] = true;
            } elseif ($override['effect'] === 'allow') {
                $allowed[$key] = true;
            }
        }

        foreach ($denied as $key => $_) {
            unset($granted[$key], $allowed[$key]);
        }
        foreach ($allowed as $key => $_) {
            $granted[$key] = true;
        }

        $this->resolved[$userId] = $granted;
    }

    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Users')->getConnection();
    }
}
