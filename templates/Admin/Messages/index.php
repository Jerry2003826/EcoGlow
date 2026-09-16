<?php
// templates/Admin/Messages/index.php

$this->assign('title', 'Enquiries');
?>

<h1 class="page-title">Enquiries</h1>

<div class="panel-card">
    <p>Enquiries submitted from the public contact form.</p>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>EnquiryId</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($messages) === 0): ?>
                <tr>
                    <td colspan="5">No enquiries yet.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            (string)$message->EnquiryId,
                            ['action' => 'view', $message->EnquiryId]
                        ) ?>
                    </td>
                    <td><?= h($message->Name) ?></td>
                    <td><?= h($message->Email) ?></td>
                    <td><?= h($message->Subject) ?></td>
                    <td>
                        <?= $message->CreatedDate
                            ? $message->CreatedDate->format('d/m/Y H:i')
                            : ''
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
