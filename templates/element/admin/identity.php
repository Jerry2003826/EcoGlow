<?php
/**
 * Primary label with optional code chip and muted meta.
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var string|null $code
 * @var string|null $meta
 */
$code = $code ?? null;
$meta = $meta ?? null;
?>
<div class="admin-identity">
    <strong><?= h($title) ?></strong>
    <?php if ($code || $meta) : ?>
        <span class="admin-identity-meta">
            <?php if ($code) : ?>
                <code class="admin-code"><?= h($code) ?></code>
            <?php endif; ?>
            <?php if ($meta) : ?>
                <span><?= h($meta) ?></span>
            <?php endif; ?>
        </span>
    <?php endif; ?>
</div>
