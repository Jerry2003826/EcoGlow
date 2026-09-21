<?php
// templates/Admin/Content/edit_block.php

$isFeature = ($block->BlockType ?? '') === 'feature';
$pageTitle = $isFeature ? 'Edit product range card' : 'Edit homepage banner slide';

$this->assign('title', $pageTitle);
?>

<h1 class="page-title"><?= h($pageTitle) ?></h1>

<div class="panel-card">
    <?= $this->Form->create($block, [
        'class' => 'admin-form',
        'type' => 'file',
        'url' => ['action' => 'editBlock', $block->BlockId],
    ]) ?>

    <p class="content-section-help">
        This item appears in the <?= h($isFeature ? 'product range cards' : 'homepage banner slider') ?> section.
    </p>

    <?= $this->Form->control('Title', [
        'label' => 'Title',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('Description', [
        'label' => 'Description',
        'type' => 'textarea',
        'rows' => 4,
        'required' => true,
    ]) ?>
    <?= $this->Form->control('ButtonLabel', [
        'label' => 'Button label',
    ]) ?>
    <?= $this->Form->control('ButtonUrl', [
        'label' => 'Button link',
        'placeholder' => '/products',
    ]) ?>
    <p class="content-field-hint">
        Use a page path like /products, an in-page link like #shop, or a full https:// address.
    </p>
    <?= $this->Form->control('AltText', [
        'label' => 'Image description',
        'required' => true,
    ]) ?>

    <?php if (!empty($block->ImagePath)): ?>
        <div class="content-current-image">
            <p>Current image</p>
            <?= $this->Html->image($block->ImagePath, [
                'alt' => $block->AltText,
                'class' => 'content-thumb content-thumb-lg',
            ]) ?>
        </div>
    <?php endif; ?>

    <?= $this->Form->control('image', [
        'label' => 'Replace image (optional)',
        'type' => 'file',
        'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
    ]) ?>

    <div class="form-actions">
        <?= $this->Form->button('Update', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to landing page', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
        <?= $this->Html->link('View home page', '/', [
            'class' => 'admin-back-link',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
