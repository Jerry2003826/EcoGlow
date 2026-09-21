<?php
// templates/Admin/Content/add_block.php

$blockType = (string)($blockType ?? '');
$isFeature = $blockType === 'feature';
$typeLabel = $isFeature ? 'product range card' : 'homepage banner slide';
$pageTitle = $blockType === '' ? 'Add homepage item' : 'Add ' . $typeLabel;

$this->assign('title', $pageTitle);
?>

<h1 class="page-title"><?= h($pageTitle) ?></h1>

<div class="panel-card">
    <?= $this->Form->create($block, [
        'class' => 'admin-form',
        'type' => 'file',
        'url' => ['action' => 'addBlock'],
    ]) ?>

    <?php if ($blockType !== ''): ?>
        <?= $this->Form->hidden('BlockType', ['value' => $blockType]) ?>
        <p class="content-section-help">
            This item will appear in the <?= h($isFeature ? 'product range cards' : 'homepage banner slider') ?> section.
        </p>
    <?php else: ?>
        <?= $this->Form->control('BlockType', [
            'label' => 'Section',
            'options' => [
                'carousel' => 'Homepage banner slider',
                'feature' => 'Product range cards',
            ],
            'empty' => 'Choose a section',
            'required' => true,
        ]) ?>
    <?php endif; ?>

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
    <?= $this->Form->control('image', [
        'label' => 'Image',
        'type' => 'file',
        'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
        'required' => true,
    ]) ?>

    <div class="form-actions">
        <?= $this->Form->button('Save', ['class' => 'admin-green-button']) ?>
        <?= $this->Html->link('Back to landing page', ['action' => 'index'], ['class' => 'admin-back-link']) ?>
        <?= $this->Html->link('View home page', '/', [
            'class' => 'admin-back-link',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
