<?php
declare(strict_types=1);

namespace App\Service\Authorization;

use Cake\Datasource\ConnectionInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Resolves effective permissions from the five RBAC tables.
 *
 * Deny overrides win over allow overrides, which win over role grants.
 * Nothing here hard-codes which keys a role owns — that lives in the
 * role_permissions rows seeded for master, elevated_staff and standard_staff.
 */
class PermissionService
{
    use LocatorAwareTrait;

    /**
     * Per-request cache of resolved permission keys, keyed by user id.
     *
     * @var array<int, array<string, bool>>
     */
    private array $resolved = [];

    /**
     * Per-request cache of active role rows, keyed by user id.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $roles = [];

    /**
     * Whether the identity holds a given permission_key.
     *
     * @param int $userId Authenticated user id.
     * @param string $permissionKey Value from permissions.permission_key.
     * @return bool
     */
    public function has(int $userId, string $permissionKey): bool
    {
        $granted = $this->effectivePermissions($userId);

        return isset($granted[$permissionKey]);
    }

    /**
     * Whether the identity has at least one live permission.
     *
     * Used for the dashboard and coming-soon pages, which are staff-only but
     * are not tied to a single module key.
     *
     * @param int $userId Authenticated user id.
     * @return bool
     */
    public function hasAny(int $userId): bool
    {
        return $this->effectivePermissions($userId) !== [];
    }

    /**
     * Whether any of the listed keys is granted.
     *
     * @param int $userId Authenticated user id.
     * @param array<int, string> $permissionKeys Permission keys to test.
     * @return bool
     */
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

    /**
     * Drop cached grants so a later check sees a mutation in this request.
     *
     * @param int|null $userId User to forget, or null for everyone cached here.
     * @return void
     */
    public function forget(?int $userId = null): void
    {
        if ($userId === null) {
            $this->resolved = [];
            $this->roles = [];

            return;
        }
        unset($this->resolved[$userId], $this->roles[$userId]);
    }

    /**
     * Active role rows for display in the admin top bar.
     *
     * @param int $userId Authenticated user id.
     * @return array<int, array<string, mixed>>
     */
    public function rolesFor(int $userId): array
    {
        $this->load($userId);

        return $this->roles[$userId] ?? [];
    }

    /**
     * Effective permission keys after applying overrides.
     *
     * @param int $userId Authenticated user id.
     * @return array<string, bool>
     */
    public function effectivePermissions(int $userId): array
    {
        $this->load($userId);

        return $this->resolved[$userId] ?? [];
    }

    /**
     * Load roles, grants and overrides once per user per request.
     *
     * @param int $userId Authenticated user id.
     * @return void
     */
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

    /**
     * Application default connection (aliased to test during PHPUnit).
     *
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Users')->getConnection();
    }
}
