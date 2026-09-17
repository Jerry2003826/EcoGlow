<?php

$this->assign('title', 'Coming Soon');
?>

<h1 class="page-title">
    <?= h(ucwords(str_replace('-', ' ', $module))) ?>
</h1>

<div class="panel-card">
    <h2>Coming Soon</h2>

    <p>
        This module is not implemented yet.
    </p>

    <ul class="attention-list">
        <?php foreach ($features as $feature): ?>
            <li><?= h($feature) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
