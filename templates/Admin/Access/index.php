<?php
// templates/Admin/Access/index.php

$this->assign('title', 'Access');
$currentIdentity = $this->request->getAttribute('identity');
$currentUserId = $currentIdentity?->getIdentifier();
?>

<h1 class="page-title">Access Management</h1>

<div class="panel-card">
    <table class="table admin-table">
        <thead>
        <tr>
            <th>Email</th>
            <th>Current Role</th>
            <th>Role Assignment</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <?php
            $currentRole = $user->user_roles[0]->role->role_key ?? '';
            ?>
            <tr>
                <td><?= h($user->email) ?></td>
                <td><?= h($currentRole) ?></td>
                <td>
                    <?php if ((int)$user->id === (int)$currentUserId): ?>
                        You cannot change your own role.
                    <?php else: ?>
                        <?= $this->Form->create(null) ?>
                        <?= $this->Form->hidden('user_id', ['value' => $user->id]) ?>
                        <?= $this->Form->control('role_key', [
                            'label' => false,
                            'options' => collection($roles)->combine('role_key', 'name')->toArray(),
                            'value' => $currentRole,
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
