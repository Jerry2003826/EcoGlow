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
                <th>Status</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($messages) === 0): ?>
                <tr>
                    <td colspan="6">No enquiries yet.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <?php
                    $status = (string)($message->Status ?? 'New');
                    $statusClass = match ($status) {
                        'Pending' => 'status-pending',
                        'Resolved' => 'status-resolved',
                        default => 'status-new',
                    };
                ?>
                <tr
                    class="admin-clickable-row"
                    data-href="/admin/messages/view/<?= (int)$message->EnquiryId ?>"
                >
                    <td>
                        <?= $this->Html->link(
                            (string)$message->EnquiryId,
                            ['action' => 'view', $message->EnquiryId]
                        ) ?>
                    </td>
                    <td><?= h($message->Name) ?></td>
                    <td>
                        <?php if (!empty($message->Email)): ?>
                            <?= $this->Html->link(
                                (string)$message->Email,
                                'mailto:' . $message->Email
                            ) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($message->Subject) ?></td>
                    <td>
                        <span class="<?= h($statusClass) ?>"><?= h($status) ?></span>
                    </td>
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

<div class="panel-card">
    <h2>Contact enquirer</h2>

    <?= $this->Form->create(null, [
        'url' => ['action' => 'contact'],
        'class' => 'admin-form',
    ]) ?>

    <?= $this->Form->control('enquiry_id', [
        'label' => 'Enquiry',
        'options' => $enquiryOptions ?? [],
        'required' => true,
    ]) ?>

    <?= $this->Form->control('subject', [
        'label' => 'Subject',
        'required' => true,
    ]) ?>

    <?= $this->Form->control('message', [
        'label' => 'Message',
        'type' => 'textarea',
        'rows' => 8,
        'required' => true,
    ]) ?>

    <?= $this->Form->button('Send Email') ?>
    <?= $this->Form->end() ?>
</div>
