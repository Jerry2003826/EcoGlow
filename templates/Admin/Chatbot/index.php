<?php
// templates/Admin/Chatbot/index.php

$this->assign('title', 'Chatbot FAQs');
?>
<h1 class="page-title">Chatbot FAQs</h1>

<p class="page-lead">
    These questions and answers are given to the AI assistant so it can reply accurately. Hidden entries are kept but not used.
    <?= $this->Html->link(
        'Add FAQ',
        ['action' => 'add'],
        ['class' => 'admin-green-button']
    ) ?>
</p>

<div class="panel-card">
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Order</th>
                <th>Question</th>
                <th>Answer</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($faqs) === 0): ?>
                <tr>
                    <td colspan="5">
                        <?= h('No FAQs yet. Add the first one.') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($faqs as $faq): ?>
                    <?php
                    $answer = (string)$faq->Answer;

                    if (mb_strlen($answer) > 90) {
                        $answer = mb_strimwidth($answer, 0, 90, '…');
                    }
                    ?>
                    <tr
                        class="admin-clickable-row"
                        data-href="/admin/chatbot/edit/<?= (int)$faq->FaqId ?>"
                    >
                        <td><?= h((string)$faq->SortOrder) ?></td>
                        <td><?= h($faq->Question) ?></td>
                        <td><?= h($answer) ?></td>
                        <td>
                            <?php if ((int)$faq->IsActive === 1): ?>
                                <span class="chatbot-faq-status chatbot-faq-status-visible">
                                    <?= h('Visible') ?>
                                </span>
                            <?php else: ?>
                                <span class="chatbot-faq-status chatbot-faq-status-hidden">
                                    <?= h('Hidden') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $this->Html->link(
                                'Edit',
                                ['action' => 'edit', $faq->FaqId]
                            ) ?>

                            <?= $this->Form->postLink(
                                (int)$faq->IsActive === 1 ? 'Hide' : 'Show',
                                ['action' => 'toggle', $faq->FaqId]
                            ) ?>

                            <?= $this->Form->postLink(
                                'Delete',
                                ['action' => 'delete', $faq->FaqId],
                                [
                                    'confirm' => 'Delete this FAQ?',
                                    'class' => 'admin-delete-link',
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
