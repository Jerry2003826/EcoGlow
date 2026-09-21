<?php
// templates/Admin/Content/index.php

$this->assign('title', 'Landing page');

$sections = [
    [
        'id' => 'content-banner',
        'open' => true,
        'title' => 'Homepage banner slider',
        'help' => 'The rotating images at the very top of the home page.',
        'texts' => $bannerTexts ?? [],
        'blocks' => $carouselBlocks ?? [],
        'addType' => 'carousel',
        'addLabel' => 'Add banner slide',
    ],
    [
        'id' => 'content-intro',
        'open' => false,
        'title' => 'Main introduction',
        'help' => 'The large heading and text below the slider.',
        'texts' => $heroTexts ?? [],
        'blocks' => [],
        'addType' => '',
        'addLabel' => '',
    ],
    [
        'id' => 'content-products',
        'open' => false,
        'title' => 'Product range cards',
        'help' => 'The product cards on the home page, plus the heading above them.',
        'texts' => $productTexts ?? [],
        'blocks' => $featureBlocks ?? [],
        'addType' => 'feature',
        'addLabel' => 'Add product card',
    ],
    [
        'id' => 'content-service',
        'open' => false,
        'title' => 'Service promotion',
        'help' => 'The green band near the bottom inviting customers to get in touch.',
        'texts' => $serviceTexts ?? [],
        'blocks' => [],
        'addType' => '',
        'addLabel' => '',
    ],
];
?>

<h1 class="page-title">Landing page</h1>

<div class="content-page-actions">
    <?= $this->Html->link('View home page', '/', [
        'class' => 'admin-green-button',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]) ?>
</div>

<p class="page-lead">
    Change the words and pictures customers see on the home page.
</p>

<nav class="content-jump-nav" aria-label="Page sections">
    <?php foreach ($sections as $section): ?>
        <a href="#<?= h($section['id']) ?>"><?= h($section['title']) ?></a>
    <?php endforeach; ?>
</nav>

<?php foreach ($sections as $section): ?>
    <details
        class="content-section panel-card"
        id="<?= h($section['id']) ?>"
        <?= $section['open'] ? 'open' : '' ?>
    >
        <summary><?= h($section['title']) ?></summary>
        <p class="content-section-help"><?= h($section['help']) ?></p>

        <?php if ($section['texts'] !== []): ?>
            <?= $this->Form->create(null, [
                'url' => ['action' => 'saveText'],
                'class' => 'admin-form',
            ]) ?>
            <?php foreach ($section['texts'] as $text): ?>
                <?php
                    $inputId = 'text-' . str_replace('.', '-', (string)$text->TextKey);
                    $isTextarea = $text->FieldType === 'textarea';
                    $options = [
                        'name' => 'text[' . $text->TextKey . ']',
                        'id' => $inputId,
                        'label' => $text->FieldLabel,
                        'type' => $isTextarea ? 'textarea' : 'text',
                        'val' => $text->TextValue,
                        'required' => true,
                    ];
                    if ($isTextarea) {
                        $options['rows'] = 4;
                    }
                ?>
                <?= $this->Form->control($inputId, $options) ?>
            <?php endforeach; ?>
            <div class="form-actions">
                <?= $this->Form->button('Save text', ['class' => 'admin-green-button']) ?>
            </div>
            <?= $this->Form->end() ?>
        <?php endif; ?>

        <?php if ($section['addType'] !== '' || $section['blocks'] !== []): ?>
            <div class="content-block-toolbar">
                <?php if ($section['addType'] !== ''): ?>
                    <?= $this->Html->link($section['addLabel'], [
                        'action' => 'addBlock',
                        '?' => ['BlockType' => $section['addType']],
                    ], ['class' => 'admin-green-button']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($section['blocks'] !== []): ?>
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        $blockCount = count($section['blocks']);
                    ?>
                    <?php foreach ($section['blocks'] as $index => $block): ?>
                        <?php
                            $isFirst = $index === 0;
                            $isLast = $index === $blockCount - 1;
                            $isVisible = (bool)$block->IsActive;
                        ?>
                        <tr class="<?= $isVisible ? '' : 'content-block-hidden' ?>">
                            <td>
                                <?= $this->Html->image($block->ImagePath, [
                                    'alt' => $block->AltText,
                                    'class' => 'content-thumb',
                                ]) ?>
                            </td>
                            <td>
                                <div class="content-block-title"><?= h($block->Title) ?></div>
                                <div class="content-block-copy">
                                    <?= h(mb_strimwidth((string)$block->Description, 0, 90, '…', 'UTF-8')) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($isVisible): ?>
                                    <span class="content-status-visible">Visible</span>
                                <?php else: ?>
                                    <span class="content-status-hidden">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="content-order-actions">
                                    <?php if (!$isFirst): ?>
                                        <?= $this->Form->postLink('Top', [
                                            'action' => 'moveBlock',
                                            $block->BlockId,
                                            'top',
                                        ], ['class' => 'content-order-button']) ?>
                                        <?= $this->Form->postLink('Up', [
                                            'action' => 'moveBlock',
                                            $block->BlockId,
                                            'up',
                                        ], ['class' => 'content-order-button']) ?>
                                    <?php endif; ?>
                                    <?php if (!$isLast): ?>
                                        <?= $this->Form->postLink('Down', [
                                            'action' => 'moveBlock',
                                            $block->BlockId,
                                            'down',
                                        ], ['class' => 'content-order-button']) ?>
                                        <?= $this->Form->postLink('Bottom', [
                                            'action' => 'moveBlock',
                                            $block->BlockId,
                                            'bottom',
                                        ], ['class' => 'content-order-button']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="content-block-actions">
                                    <?= $this->Html->link('Edit', [
                                        'action' => 'editBlock',
                                        $block->BlockId,
                                    ], ['class' => 'admin-action-link']) ?>
                                    <?= $this->Form->postLink(
                                        $isVisible ? 'Hide' : 'Show',
                                        ['action' => 'toggleBlock', $block->BlockId],
                                        ['class' => 'admin-action-link']
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        'Delete',
                                        ['action' => 'deleteBlock', $block->BlockId],
                                        [
                                            'confirm' => 'Delete this item? This cannot be undone.',
                                            'class' => 'admin-delete-link',
                                        ]
                                    ) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($section['addType'] !== ''): ?>
            <p class="empty-state">No items in this section yet.</p>
        <?php endif; ?>
    </details>
<?php endforeach; ?>
