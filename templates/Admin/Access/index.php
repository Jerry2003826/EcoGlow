<?php
// templates/Admin/Access/index.php

$this->assign('title', 'Access');
$currentUserId = (int)($this->request->getAttribute('identity')?->getIdentifier() ?? 0);
?>

<h1 class="page-title">Access</h1>

<div class="panel-card">
    <h2>Staff access</h2>
    <p>Admin can open every admin page. Staff can always open the dashboard. Other modules follow the boxes you tick below.</p>

    <?= $this->Form->create(null) ?>
    <?= $this->Form->hidden('form', ['value' => 'modules']) ?>

    <div class="access-checkbox-list">
        <?php foreach ($modules as $module): ?>
            <?php if ($module->PermissionKey === 'dashboard.view'): ?>
                <?php continue; ?>
            <?php endif; ?>
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
    <h2>Per-staff access</h2>
    <p>Ticks on this form override the global staff access settings above for that person only. An unticked box is stored as denied for that staff member.</p>

    <?php if ($staffUsers->isEmpty()): ?>
        <p>No active staff users are available to assign personal access.</p>
    <?php else: ?>
        <?php foreach ($staffUsers as $staffUser): ?>
            <?php
            $staffUserId = (int)$staffUser->UserId;
            $staffName = (string)($staffUser->Username !== null && $staffUser->Username !== ''
                ? $staffUser->Username
                : $staffUser->Email);
            $overrides = $staffOverrides[$staffUserId] ?? [];
            ?>
            <h3><?= h($staffName) ?></h3>
            <p><?= h((string)$staffUser->Email) ?></p>

            <?= $this->Form->create(null) ?>
            <?= $this->Form->hidden('form', ['value' => 'user']) ?>
            <?= $this->Form->hidden('userId', ['value' => $staffUserId]) ?>

            <div class="access-checkbox-list">
                <?php foreach ($permissionOptions as $key => $label): ?>
                    <label class="access-checkbox-item">
                        <input
                            type="checkbox"
                            name="allowed[]"
                            value="<?= h($key) ?>"
                            <?= !empty($overrides[$key]) ? 'checked' : '' ?>
                        >
                        <?= h($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <?= $this->Form->button('Save personal access') ?>
            <?= $this->Form->end() ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
