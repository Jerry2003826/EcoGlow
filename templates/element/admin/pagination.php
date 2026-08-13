<?php
/**
 * Bootstrap-shaped pagination matching storefront .page-link buttons.
 *
 * @var \App\View\AppView $this
 * @var string $label
 * @var string $counter
 */
$label = $label ?? 'Pagination';
$counter = $counter ?? 'Page {{page}} of {{pages}}, showing {{current}} of {{count}}';
?>
<nav aria-label="<?= h($label) ?>" class="mt-4">
    <ul class="pagination justify-content-center">
        <?= $this->Paginator->first('« First') ?>
        <?= $this->Paginator->prev('‹ Prev') ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next('Next ›') ?>
        <?= $this->Paginator->last('Last »') ?>
    </ul>
</nav>
<p class="text-center small text-muted"><?= $this->Paginator->counter($counter) ?></p>
