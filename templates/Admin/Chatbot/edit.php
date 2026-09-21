<?php
// templates/Admin/Chatbot/edit.php

$this->assign('title', 'Edit FAQ');
?>
<h1 class="page-title">Edit FAQ</h1>

<div class="panel-card">
    <?= $this->Form->create($faq, ['class' => 'admin-form']) ?>

    <?= $this->Form->control('Question', [
        'label' => 'Question',
        'required' => true,
        'maxlength' => 255,
    ]) ?>

    <?= $this->Form->control('Answer', [
        'type' => 'textarea',
        'rows' => 5,
        'label' => 'Answer',
        'required' => true,
    ]) ?>

    <?= $this->Form->control('SortOrder', [
        'type' => 'number',
        'label' => 'Display order',
        'help' => 'Lower numbers appear first.',
    ]) ?>

    <?= $this->Form->control('IsActive', [
        'type' => 'checkbox',
        'label' => 'Visible to the assistant',
    ]) ?>

    <div class="form-actions">
        <?= $this->Form->button('Update', ['class' => 'admin-green-button']) ?>

        <?= $this->Html->link(
            'Back to FAQs',
            ['action' => 'index'],
            ['class' => 'admin-back-link']
        ) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
