<?php
/**
 * Coming-soon placeholder for later batches.
 *
 * @var \App\View\AppView $this
 * @var array{title: string, summary: string, points: array<int, string>} $comingSoon
 * @var string $moduleKey
 */
$this->assign('title', $comingSoon['title']);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [['label' => $comingSoon['title']]],
]));
?>
<div class="admin-page-head">
    <span class="eg-eyebrow">Coming in a later batch</span>
    <h1><?= h($comingSoon['title']) ?></h1>
</div>

<div class="admin-panel">
    <p class="mb-3"><?= h($comingSoon['summary']) ?></p>
    <div class="eg-note" role="note">
        <p class="mb-2">This screen will provide:</p>
        <ul class="mb-0">
            <?php foreach ($comingSoon['points'] as $point) : ?>
                <li><?= h($point) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
