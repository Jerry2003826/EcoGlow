<?php
$this->assign('title', 'Users');
$this->Html->css('storefront', ['block' => true, 'timestamp' => true]);
?>
<div class="user-directory">
    <div class="user-page-header">
        <h1>Users</h1>
        <?= $this->Html->link('Create customer account', ['action' => 'add'], ['class' => 'button']) ?>
    </div>
    <p><?= $this->Html->link('Back to dashboard', '/admin') ?></p>
    <p>View or edit account details. To deactivate an account, edit its Status and choose Disabled.</p>
    <div class="user-table-wrap" tabindex="0" role="region" aria-label="User accounts">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('UserId', 'ID') ?></th>
                    <th><?= $this->Paginator->sort('Username', 'Username') ?></th>
                    <th><?= $this->Paginator->sort('Email', 'Email') ?></th>
                    <th><?= $this->Paginator->sort('UserRole', 'Role') ?></th>
                    <th><?= $this->Paginator->sort('Status', 'Status') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int)$user->UserId ?></td>
                    <td><?= h($user->Username) ?></td>
                    <td><?= h($user->Email) ?></td>
                    <td><?= h(ucfirst((string)$user->UserRole)) ?></td>
                    <td><?= h(ucfirst((string)$user->Status)) ?></td>
                    <td class="actions">
                        <div class="user-actions">
                            <?= $this->Html->link('View', ['action' => 'view', $user->UserId]) ?>
                            <?= $this->Html->link('Edit', ['action' => 'edit', $user->UserId]) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
