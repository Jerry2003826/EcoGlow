<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AuditLogger;
use App\Service\Authorization\AccessService;
use Cake\Http\Response;
use Cake\Log\Log;
use InvalidArgumentException;

/**
 * Configurable access: users, roles and the permission matrix.
 */
class UsersController extends AdminController
{
    /**
     * @var \App\Service\Authorization\AccessService
     */
    private AccessService $access;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->access = new AccessService($this->permissions, new AuditLogger());
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $users = $this->fetchTable('Users')->find()
            ->contain(['UserRoles' => ['Roles']])
            ->where(['Users.deleted IS' => null])
            ->orderBy(['Users.email' => 'ASC'])
            ->all();
        $roles = $this->fetchTable('Roles')->find()
            ->where(['role_key !=' => 'customer'])
            ->orderBy(['id' => 'ASC'])
            ->all();
        $permissions = $this->fetchTable('Permissions')->find()
            ->orderBy(['module' => 'ASC', 'permission_key' => 'ASC'])
            ->all();
        $grants = [];
        foreach ($this->fetchTable('RolePermissions')->find() as $row) {
            $grants[(int)$row->role_id][(int)$row->permission_id] = true;
        }
        $overrides = $this->fetchTable('UserPermissionOverrides')->find()
            ->contain(['Permissions', 'Users'])
            ->where(['UserPermissionOverrides.ends_at IS' => null])
            ->orderBy(['UserPermissionOverrides.id' => 'DESC'])
            ->all();

        $this->set(compact('users', 'roles', 'permissions', 'grants', 'overrides'));
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function toggleActive(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $user = $this->fetchTable('Users')->get($this->recordId($id));
            $active = (string)($user->get('status') ?: 'active') === 'active';
            $this->access->setUserActive((int)$user->id, !$active, $this->actorId());
            $this->Flash->success($active ? __('Account deactivated.') : __('Account activated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function updateRoles(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $roleIds = array_map('intval', (array)$this->request->getData('role_ids'));
        try {
            $this->access->replaceUserRoles($this->recordId($id), $roleIds, $this->actorId());
            $this->Flash->success(__('Roles updated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null
     */
    public function updateRolePermissions(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $permissionIds = array_map('intval', (array)$this->request->getData('permission_ids'));
        try {
            $this->access->replaceRolePermissions($this->recordId($id), $permissionIds, $this->actorId());
            $this->Flash->success(__('Role permissions updated.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Save the whole matrix in one post: grants[roleId][] = permissionId.
     *
     * @return \Cake\Http\Response|null
     */
    public function updateMatrix(): ?Response
    {
        $this->request->allowMethod(['post']);
        $grants = (array)$this->request->getData('grants');
        try {
            $roles = $this->fetchTable('Roles')->find()
                ->where(['role_key !=' => 'customer'])
                ->all();
            $byRole = [];
            foreach ($roles as $role) {
                $byRole[(int)$role->id] = array_map('intval', (array)($grants[$role->id] ?? []));
            }
            $this->access->replaceAllRolePermissions($byRole, $this->actorId());
            $this->Flash->success(__('Permission matrix saved.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function setOverride(): ?Response
    {
        $this->request->allowMethod(['post']);
        try {
            $this->access->setOverride(
                (int)$this->request->getData('user_id'),
                (int)$this->request->getData('permission_id'),
                (string)$this->request->getData('effect'),
                $this->actorId(),
            );
            $this->Flash->success(__('Override saved. Deny always wins over allow.'));
        } catch (InvalidArgumentException $exception) {
            $this->Flash->error($exception->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Increment auth_version so every other device must sign in again.
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function revokeSessions(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $user = $this->fetchTable('Users')->get($this->recordId($id));
        $this->fetchTable('Users')->bumpAuthVersion($user);
        $this->permissions->forget((int)$user->id);
        $this->Flash->success(__('All sessions for that account have been revoked.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Clear MFA so the staff member must enrol again.
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function resetMfa(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $users = $this->fetchTable('Users');
        $user = $users->get($this->recordId($id));
        $user->set('mfa_enabled', false);
        $user->set('mfa_secret', null);
        $user->set('mfa_confirmed_at', null);
        $user->set('mfa_last_timestep', null);
        $user->set('mfa_recovery_hashes', null);
        $user->set('auth_version', (int)($user->get('auth_version') ?: 1) + 1);
        $users->saveOrFail($user);
        $this->permissions->forget((int)$user->id);
        Log::info('Staff MFA reset', [
            'target_user_id' => (int)$user->id,
            'actor_user_id' => $this->actorId(),
        ]);
        $this->Flash->success(__('Two-factor authentication was reset. The user must enrol again.'));

        return $this->redirect(['action' => 'index']);
    }
}
