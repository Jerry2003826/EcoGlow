<?php
/**
 * Status pill with a text label. Colour is a secondary cue only.
 *
 * @var \App\View\AppView $this
 * @var string $status
 * @var string|null $label
 * @var string|null $toneOverride
 */

use App\Model\Entity\SalesOrder;

$labels = SalesOrder::statusLabels();
$tone = $toneOverride ?? SalesOrder::statusTone($status);
$label = $label ?? $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
?>
<span class="admin-status admin-status-<?= h($tone) ?>">
    <?= h($label) ?>
</span>
