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

