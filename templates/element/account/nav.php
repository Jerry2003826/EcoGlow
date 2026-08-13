<?php
/**
 * Customer account section links.
 *
 * @var \App\View\AppView $this
 * @var string $current
 */
$items = [
    'index' => ['label' => 'Profile', 'url' => '/account'],
    'addresses' => ['label' => 'Addresses', 'url' => '/account/addresses'],
    'orders' => ['label' => 'Orders', 'url' => '/account/orders'],
    'bookings' => ['label' => 'Bookings', 'url' => '/account/bookings'],
];
?>
<nav class="account-nav" aria-label="Account">
    <?php foreach ($items as $key => $item) : ?>
        <a href="<?= $this->Url->build($item['url']) ?>"
           <?= $current === $key ? 'aria-current="page"' : '' ?>>
            <?= h($item['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>
