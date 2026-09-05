<?php

$this->assign('title', 'Customer');
?>

<h1 class="page-title">
    <?= h(trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))) ?>
</h1>

<div class="panel-card">
    <p><strong>Email:</strong> <?= h($customer->email) ?></p>
    <p><strong>Phone:</strong> <?= h($customer->phone) ?></p>
    <p><strong>Address:</strong> <?= h($customer->address_line_1) ?></p>
    <p><strong>Address 2:</strong> <?= h($customer->address_line_2) ?></p>
    <p><strong>Suburb:</strong> <?= h($customer->suburb) ?></p>
    <p><strong>State:</strong> <?= h($customer->state) ?></p>
    <p><strong>Postcode:</strong> <?= h($customer->postcode) ?></p>
    <p><strong>Country:</strong> <?= h($customer->country) ?></p>

    <?php if ($canViewAge): ?>
        <p><strong>Age:</strong> <?= h((string)$customer->age) ?></p>
    <?php endif; ?>

    <?= $this->Html->link('Edit Customer', ['action' => 'edit', $customer->id]) ?>
</div>
