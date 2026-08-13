<?php
/**
 * Breadcrumb slot for the admin top bar.
 *
 * @var \App\View\AppView $this
 * @var array<int, array{label: string, url?: array|string}> $items
 */
?>
<nav aria-label="Breadcrumb">
    <ol class="eg-breadcrumb">
        <?php foreach ($items as $index => $item) : ?>
            <?php $isLast = $index === array_key_last($items); ?>
            <li <?= $isLast ? 'aria-current="page"' : '' ?>>
                <?php if (!$isLast && !empty($item['url'])) : ?>
                    <a href="<?= $this->Url->build($item['url']) ?>"><?= h($item['label']) ?></a>
                <?php else : ?>
                    <?= h($item['label']) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
