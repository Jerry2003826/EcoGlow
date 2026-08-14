<?php
declare(strict_types=1);

namespace App\Service\Authorization;

use App\Service\AuditLogger;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * PO-facing RBAC mutations. Deny overrides win; the actor cannot drop their
 * own access.manage grant.
 */
class AccessService
{
    use LocatorAwareTrait;

    public const ACCESS_MANAGE = 'access.manage';

    /**
     * @param \App\Service\Authorization\PermissionService $permissions Resolver.
     * @param \App\Service\AuditLogger $audit Audit writer.
     */
    public function __construct(
        private PermissionService $permissions,
        private AuditLogger $audit,
    ) {
    }

    /**
     * Replace a role's permission grants with the posted set.
     *
     * @param int $roleId Role id.
     * @param array<int, int> $permissionIds Permission ids that should remain granted.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function replaceRolePermissions(int $roleId, array $permissionIds, int $actorUserId): void
    {
        try {
            $this->connection()->transactional(function () use ($roleId, $permissionIds, $actorUserId): void {
                $this->writeRolePermissions($roleId, $permissionIds, $actorUserId);
                $this->assertActorKeepsAccessManage($actorUserId);
            });
        } finally {
            $this->permissions->forget($actorUserId);
        }
    }

    /**
     * Save every role's grants in one transaction so a rejected
     * access.manage removal cannot leave earlier roles half-written.
     *
     * @param array<int, array<int, int>> $grantsByRoleId Permission ids keyed by role id.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function replaceAllRolePermissions(array $grantsByRoleId, int $actorUserId): void
    {
        try {
            $this->connection()->transactional(function () use ($grantsByRoleId, $actorUserId): void {
                foreach ($grantsByRoleId as $roleId => $permissionIds) {
                    $this->writeRolePermissions((int)$roleId, $permissionIds, $actorUserId);
                }
                $this->assertActorKeepsAccessManage($actorUserId);
            });
        } finally {
            $this->permissions->forget($actorUserId);
        }
    }

    /**
     * @param int $userId Target user.
     * @param array<int, int> $roleIds Role ids that should remain active.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function replaceUserRoles(int $userId, array $roleIds, int $actorUserId): void
    {
        $this->fetchTable('Users')->get($userId);
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        try {
            $this->connection()->transactional(function () use ($userId, $roleIds, $actorUserId): void {
                $table = $this->fetchTable('UserRoles');
                $active = $table->find()
                    ->where(['user_id' => $userId, 'revoked_at IS' => null])
                    ->all();
                $currentIds = [];
                foreach ($active as $row) {
                    $currentIds[] = (int)$row->role_id;
                    if (!in_array((int)$row->role_id, $roleIds, true)) {
                        $row->revoked_at = DateTime::now('UTC');
                        $table->saveOrFail($row);
                    }
                }
                foreach ($roleIds as $roleId) {
                    if ($roleId < 1 || in_array($roleId, $currentIds, true)) {
                        continue;
                    }
                    $this->fetchTable('Roles')->get($roleId);
                    $assignment = $table->newEmptyEntity();
                    $assignment->user_id = $userId;
                    $assignment->role_id = $roleId;
                    $assignment->granted_by_user_id = $actorUserId;
                    $assignment->starts_at = DateTime::now('UTC');
                    $table->saveOrFail($assignment);
                }

                $this->assertActorKeepsAccessManage($actorUserId);
                $this->audit->record(
                    $actorUserId,
                    'user_roles.replace',
                    'users',
                    $userId,
                    ['role_ids' => $currentIds],
                    ['role_ids' => $roleIds],
                );
                $this->fetchTable('Users')->bumpAuthVersion(
                    $this->fetchTable('Users')->get($userId),
                );
            });
        } finally {
            $this->permissions->forget($actorUserId);
            $this->permissions->forget($userId);
        }
    }

    /**
     * Set or clear a per-user override. Deny wins over allow.
     *
     * @param int $userId Target user.
     * @param int $permissionId Permission id.
     * @param string $effect allow|deny|inherit.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function setOverride(int $userId, int $permissionId, string $effect, int $actorUserId): void
    {
        if (!in_array($effect, ['allow', 'deny', 'inherit'], true)) {
            throw new InvalidArgumentException('Override must be allow, deny or inherit.');
        }
        $this->fetchTable('Users')->get($userId);
        $this->fetchTable('Permissions')->get($permissionId);

        try {
            $this->connection()->transactional(function () use ($userId, $permissionId, $effect, $actorUserId): void {
                $table = $this->fetchTable('UserPermissionOverrides');
                $open = $table->find()
                    ->where([
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                        'ends_at IS' => null,
                    ])
                    ->all();
                $before = [];
                foreach ($open as $row) {
                    $before[] = $row->effect;
                    $row->ends_at = DateTime::now('UTC');
                    $table->saveOrFail($row);
                }
                if ($effect !== 'inherit') {
                    $override = $table->newEmptyEntity();
                    $override->user_id = $userId;
                    $override->permission_id = $permissionId;
                    $override->effect = $effect;
                    $override->granted_by_user_id = $actorUserId;
                    $override->starts_at = DateTime::now('UTC');
                    $table->saveOrFail($override);
                }

                $this->assertActorKeepsAccessManage($actorUserId);
                $this->audit->record(
                    $actorUserId,
                    'user_permission_override.set',
                    'users',
                    $userId,
                    ['permission_id' => $permissionId, 'effect' => $before],
                    ['permission_id' => $permissionId, 'effect' => $effect],
                );
            });
        } finally {
            $this->permissions->forget($actorUserId);
        }
    }

    /**
     * @param int $userId Target user.
     * @param bool $active True to enable.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    public function setUserActive(int $userId, bool $active, int $actorUserId): void
    {
        if ($userId === $actorUserId && !$active) {
            throw new InvalidArgumentException('You cannot deactivate your own account.');
        }
        $user = $this->fetchTable('Users')->get($userId);
        $before = $user->status ?? null;
        $user->set('status', $active ? 'active' : 'inactive');
        $this->fetchTable('Users')->saveOrFail($user);
        $this->fetchTable('Users')->bumpAuthVersion($user);
        $this->permissions->forget($userId);
        $this->audit->record(
            $actorUserId,
            $active ? 'user.activate' : 'user.deactivate',
            'users',
            $userId,
            ['status' => $before],
            ['status' => $user->status],
        );
    }

    /**
     * Replace one role's grants and write an audit row. Caller owns the transaction.
     *
     * @param int $roleId Role id.
     * @param array<int, int> $permissionIds Permission ids that should remain granted.
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    private function writeRolePermissions(int $roleId, array $permissionIds, int $actorUserId): void
    {
        $role = $this->fetchTable('Roles')->get($roleId);
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        $table = $this->fetchTable('RolePermissions');
        $current = $table->find()->where(['role_id' => $role->id])->all();
        $currentIds = [];
        foreach ($current as $row) {
            $currentIds[] = (int)$row->permission_id;
        }

        foreach ($current as $row) {
            if (!in_array((int)$row->permission_id, $permissionIds, true)) {
                $table->deleteOrFail($row);
            }
        }
        $now = DateTime::now('UTC');
        foreach ($permissionIds as $permissionId) {
            if ($permissionId < 1 || in_array($permissionId, $currentIds, true)) {
                continue;
            }
            $grant = $table->newEmptyEntity();
            $grant->role_id = $role->id;
            $grant->permission_id = $permissionId;
            $grant->granted_by_user_id = $actorUserId;
            $grant->created = $now;
            $table->saveOrFail($grant);
        }

        $this->audit->record(
            $actorUserId,
            'role_permissions.replace',
            'roles',
            (int)$role->id,
            ['permission_ids' => $currentIds],
            ['permission_ids' => $permissionIds],
        );
    }

    /**
     * @param int $actorUserId Acting staff user.
     * @return void
     */
    private function assertActorKeepsAccessManage(int $actorUserId): void
    {
        $this->permissions->forget($actorUserId);
        if (!$this->permissions->has($actorUserId, self::ACCESS_MANAGE)) {
            throw new InvalidArgumentException(
                'That change would remove your own access.manage permission, which would lock you out.',
            );
        }
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('Users')->getConnection();
    }
}
