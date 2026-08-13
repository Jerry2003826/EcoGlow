<?php
/**
 * Status pill with a text label. Colour is a secondary cue only.
 *
 * @var \App\View\AppView $this
 * @var string $status
 */

use App\Model\Entity\SalesOrder;

$labels = SalesOrder::statusLabels();
$label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
$tone = SalesOrder::statusTone($status);
?>
<span class="admin-status admin-status-<?= h($tone) ?>">
    <?= h($label) ?>
</span>
