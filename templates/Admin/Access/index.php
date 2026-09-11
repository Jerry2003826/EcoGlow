<?php
// templates/Admin/Access/index.php

$this->assign('title', 'Access');
$currentUserId = (int)($this->request->getAttribute('identity')?->getIdentifier() ?? 0);
?>

<h1 class="page-title">Access</h1>

<div class="panel-card">
    <h2>Staff access</h2>
    <p>Admin can open every admin page. Staff can open only the modules you tick below.</p>

    <?= $this->Form->create(null) ?>
    <?= $this->Form->hidden('form', ['value' => 'modules']) ?>

    <div class="access-checkbox-list">
        <?php foreach ($modules as $module): ?>
            <label class="access-checkbox-item">
                <input
                    type="checkbox"
                    name="allowed[]"
                    value="<?= h($module->PermissionKey) ?>"
                    <?= (int)$module->Allowed === 1 ? 'checked' : '' ?>
                >
                <?= h($module->ModuleLabel) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <?= $this->Form->button('Save staff access') ?>
    <?= $this->Form->end() ?>
</div>

<div class="panel-card">
    <h2>User roles</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Email</th>
                <th>Username</th>
                <th>Role</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= h($user->Email) ?></td>
                    <td><?= h($user->Username) ?></td>
                    <td>
                        <?php if ((int)$user->UserId === $currentUserId): ?>
                            <?= h($user->UserRole) ?> — you cannot change your own role.
                        <?php else: ?>
                            <?= $this->Form->create(null, ['class' => 'access-role-form']) ?>
                            <?= $this->Form->hidden('form', ['value' => 'role']) ?>
                            <?= $this->Form->hidden('UserId', ['value' => $user->UserId]) ?>
                            <?= $this->Form->control('UserRole', [
                                'label' => false,
                                'options' => [
                                    'admin' => 'admin',
                                    'staff' => 'staff',
                                    'customer' => 'customer',
                                ],
                                'value' => $user->UserRole,
                            ]) ?>
                            <?= $this->Form->button('Save') ?>
                            <?= $this->Form->end() ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
