<?php
/**
 * Users, roles and the permission matrix.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 * @var iterable<\App\Model\Entity\Role> $roles
 * @var iterable<\App\Model\Entity\Permission> $permissions
 * @var array<int, array<int, bool>> $grants
 * @var iterable<\App\Model\Entity\UserPermissionOverride> $overrides
 */

use Cake\I18n\DateTime;

$roles = iterator_to_array($roles);
$permissions = iterator_to_array($permissions);
$users = iterator_to_array($users);
$overrides = iterator_to_array($overrides);
$protectedKeys = ['master', 'elevated_staff'];

$this->assign('title', 'Users & roles');
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => 'Users & roles']],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Access</span>
    <h1>Users &amp; roles</h1>
</div>

<p class="admin-note mb-4">
    Master access and Elevated staff are protected system roles and cannot be deleted.
    You cannot remove your own <code class="permission-key">access.manage</code> grant — that would lock you out.
    Per-user overrides: <strong>deny always wins over allow</strong>, and allow wins over a role grant.
</p>

<section class="admin-section" aria-labelledby="users-heading" data-admin-fold>
    <div class="admin-panel">
        <div class="admin-panel-head is-fold">
            <h2 id="users-heading">
                <button type="button" class="admin-fold-toggle" data-admin-fold-toggle
                        aria-expanded="true" aria-controls="users-fold">
                    Staff accounts
                </button>
            </h2>
            <div class="admin-fold-search">
                <label class="visually-hidden" for="users-search">Search staff accounts</label>
                <input type="search" id="users-search" class="form-control" data-admin-fold-search
                       placeholder="Search email or name" autocomplete="off">
            </div>
        </div>
        <div id="users-fold" data-admin-fold-body>
        <div class="table-responsive">
            <table class="table table-eg align-middle" aria-label="Staff accounts">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="text-end">Account</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <?php
                        $activeRoles = [];
                        foreach ($user->user_roles ?? [] as $assignment) {
                            if ($assignment->revoked_at !== null) {
                                continue;
                            }
                            if ($assignment->role) {
                                $activeRoles[] = $assignment->role;
                            }
                        }
                        $activeIds = array_map(static fn($role): int => (int)$role->id, $activeRoles);
                        $name = trim((string)$user->get('first_name') . ' ' . (string)$user->get('last_name'));
                        $status = (string)($user->get('status') ?: 'active');
                        $lastLogin = $user->get('last_login_at');
                        $roleNames = array_map(static fn($role): string => (string)$role->name, $activeRoles);
                        $rowSearch = strtolower($user->email . ' ' . $name . ' ' . $status . ' ' . implode(' ', $roleNames));
                        ?>
                        <tr data-admin-fold-row data-search="<?= h($rowSearch) ?>">
                            <td><?= h($user->email) ?></td>
                            <td><?= h($name !== '' ? $name : '—') ?></td>
                            <td>
                                <?php foreach ($roles as $role) : ?>
                                    <label class="admin-check">
                                        <input type="checkbox"
                                               form="roles-<?= (int)$user->id ?>"
                                               name="role_ids[]"
                                               value="<?= (int)$role->id ?>"
                                            <?= in_array((int)$role->id, $activeIds, true) ? ' checked' : '' ?>>
                                        <?= h($role->name) ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?= $this->element('admin/status_pill', [
                                    'status' => $status,
                                    'label' => $status === 'active' ? 'Active' : 'Inactive',
                                    'toneOverride' => $status === 'active' ? 'success' : 'muted',
                                ]) ?>
                            </td>
                            <td class="text-nowrap">
                                <?= $lastLogin
                                    ? h((new DateTime($lastLogin))->setTimezone('Australia/Melbourne')->format('d M Y, H:i'))
                                    : 'Never' ?>
                            </td>
                            <td class="text-end">
                                <div class="admin-account-actions">
                                    <?= $this->Form->create(null, [
                                        'url' => ['action' => 'updateRoles', $user->id],
                                        'id' => 'roles-' . (int)$user->id,
                                        'class' => 'admin-role-form',
                                    ]) ?>
                                    <?= $this->Form->button('Save roles', ['class' => 'btn btn-sm btn-eg-ghost']) ?>
                                    <?= $this->Form->end() ?>
                                    <?= $this->Form->postButton(
                                        $status === 'active' ? 'Deactivate' : 'Activate',
                                        ['action' => 'toggleActive', $user->id],
                                        [
                                            'class' => $status === 'active' ? 'btn btn-sm btn-eg-danger' : 'btn btn-sm btn-eg-ghost',
                                            'confirm' => $status === 'active'
                                                ? 'Deactivate this account? They will not be able to sign in.'
                                                : null,
                                        ],
                                    ) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="admin-empty mt-3" data-admin-fold-empty hidden>No matching staff accounts.</p>
        </div>
    </div>
</section>

<section class="admin-section" aria-labelledby="matrix-heading" data-admin-fold>
    <div class="admin-panel">
        <div class="admin-panel-head is-fold">
            <h2 id="matrix-heading">
                <button type="button" class="admin-fold-toggle" data-admin-fold-toggle
                        aria-expanded="true" aria-controls="matrix-fold">
                    Permission matrix
                </button>
            </h2>
            <div class="admin-fold-search">
                <label class="visually-hidden" for="matrix-search">Search permissions</label>
                <input type="search" id="matrix-search" class="form-control" data-admin-fold-search
                       placeholder="Search permission" autocomplete="off">
            </div>
        </div>
        <div id="matrix-fold" data-admin-fold-body>
        <p class="admin-note mb-3">
            Ticking a cell writes <code class="permission-key">role_permissions</code>. Unticking removes the grant.
            Master access and Elevated staff are protected presets — they cannot be deleted from this screen.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'updateMatrix']]) ?>
        <div class="admin-matrix-wrap">
            <table class="table table-eg admin-matrix" aria-label="Role permission matrix">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <?php foreach ($roles as $role) : ?>
                            <th>
                                <?= h($role->name) ?>
                                <?php if (in_array($role->role_key, $protectedKeys, true)) : ?>
                                    <div class="small text-muted">Protected</div>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $permission) : ?>
                        <?php
                        $permSearch = strtolower(
                            (string)$permission->permission_key . ' ' . (string)$permission->name,
                        );
                        ?>
                        <tr data-admin-fold-row data-search="<?= h($permSearch) ?>">
                            <td>
                                <code class="permission-key"><?= h($permission->permission_key) ?></code>
                                <div class="small text-muted"><?= h($permission->name) ?></div>
                            </td>
                            <?php foreach ($roles as $role) : ?>
                                <?php $checked = !empty($grants[(int)$role->id][(int)$permission->id]); ?>
                                <td>
                                    <label>
                                        <span class="visually-hidden">
                                            <?= h($permission->permission_key) ?> for <?= h($role->name) ?>
                                        </span>
                                        <input type="checkbox"
                                               name="grants[<?= (int)$role->id ?>][]"
                                               value="<?= (int)$permission->id ?>"
                                            <?= $checked ? ' checked' : '' ?>>
                                    </label>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="admin-empty mt-3" data-admin-fold-empty hidden>No matching permissions.</p>
        <?= $this->Form->button('Save matrix', ['class' => 'btn btn-eg-primary mt-3']) ?>
        <?= $this->Form->end() ?>
        </div>
    </div>
</section>

<section class="admin-section" aria-labelledby="override-heading" data-admin-fold>
    <div class="admin-panel">
        <div class="admin-panel-head is-fold">
            <h2 id="override-heading">
                <button type="button" class="admin-fold-toggle" data-admin-fold-toggle
                        aria-expanded="true" aria-controls="override-fold">
                    Per-user overrides
                </button>
            </h2>
            <div class="admin-fold-search">
                <label class="visually-hidden" for="override-search">Search overrides</label>
                <input type="search" id="override-search" class="form-control" data-admin-fold-search
                       placeholder="Search user or permission" autocomplete="off">
            </div>
        </div>
        <div id="override-fold" data-admin-fold-body>
        <p class="admin-note mb-3">
            Use these for a one-off extra allow or a hard deny. <strong>Deny takes priority over allow</strong>,
            and both take priority over the role matrix. Choose Inherit to clear an open override.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'setOverride']]) ?>
        <div class="admin-filter-row">
            <div class="admin-field">
                <label for="override-user">User</label>
                <select name="user_id" id="override-user" class="form-select" required>
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?= (int)$user->id ?>"><?= h($user->email) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="override-permission">Permission</label>
                <select name="permission_id" id="override-permission" class="form-select" required>
                    <option value="">Select a permission</option>
                    <?php foreach ($permissions as $permission) : ?>
                        <option value="<?= (int)$permission->id ?>"><?= h($permission->permission_key) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="override-effect">Effect</label>
                <select name="effect" id="override-effect" class="form-select" required>
                    <option value="allow">Allow</option>
                    <option value="deny">Deny (wins)</option>
                    <option value="inherit">Inherit (clear)</option>
                </select>
            </div>
            <?= $this->Form->button('Save override', ['class' => 'btn btn-eg-ghost']) ?>
        </div>
        <?= $this->Form->end() ?>

        <?php if ($overrides === []) : ?>
            <?= $this->element('admin/empty', [
                'title' => 'No open overrides.',
                'body' => 'A deny or extra allow will list here.',
            ]) ?>
        <?php else : ?>
            <div class="table-responsive mt-3">
                <table class="table table-eg align-middle" aria-label="Open permission overrides">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Permission</th>
                            <th>Effect</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overrides as $override) : ?>
                            <?php
                            $overrideSearch = strtolower(
                                (string)($override->user->email ?? '') . ' ' .
                                (string)($override->permission->permission_key ?? '') . ' ' .
                                (string)$override->effect,
                            );
                            ?>
                            <tr data-admin-fold-row data-search="<?= h($overrideSearch) ?>">
                                <td><?= h($override->user->email ?? '#' . $override->user_id) ?></td>
                                <td class="cell-id"><?= h($override->permission->permission_key ?? '') ?></td>
                                <td><?= h($override->effect) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="admin-empty mt-3" data-admin-fold-empty hidden>No matching overrides.</p>
        <?php endif; ?>
        </div>
    </div>
</section>
