<?php
// templates/Admin/Chatbot/add.php

$this->assign('title', 'Add FAQ');
?>
<h1 class="page-title">Add FAQ</h1>

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
        'default' => 0,
    ]) ?>

    <p class="content-field-hint">Lower numbers appear first.</p>

    <?= $this->Form->control('IsActive', [
        'type' => 'checkbox',
        'label' => 'Visible to the assistant',
        'checked' => true,
    ]) ?>

    <div class="form-actions">
        <?= $this->Form->button('Save', ['class' => 'admin-green-button']) ?>

        <?= $this->Html->link(
            'Back to FAQs',
            ['action' => 'index'],
            ['class' => 'admin-back-link']
        ) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
