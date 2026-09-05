<?php

$this->assign('title', 'Messages');
?>

<h1 class="page-title">Messages</h1>

<?php if (count($messages) === 0): ?>
    <div class="empty-state">
        No messages found.
    </div>
<?php else: ?>
    <div class="panel-card">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Reference</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Read</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            (string)($message->reference_number ?: $message->id),
                            ['action' => 'view', $message->id]
                        ) ?>
                    </td>
                    <td><?= h($message->name) ?></td>
                    <td><?= h($message->email) ?></td>
                    <td><?= h($message->subject) ?></td>
                    <td><?= $message->is_read ? 'Yes' : 'No' ?></td>
                    <td><?= h((string)$message->created) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
