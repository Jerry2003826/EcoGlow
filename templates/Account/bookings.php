<?php
/**
 * Customer service request list.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var iterable<\App\Model\Entity\ServiceRequest> $requests
 */

use App\Model\Entity\ServiceRequest;

$this->assign('title', 'Your bookings');
$this->Html->css('account', ['block' => true]);
$this->assign('breadcrumb', $this->element('admin/breadcrumb', [
    'items' => [
        ['label' => 'Account', 'url' => '/account'],
        ['label' => 'Bookings'],
    ],
]));
$labels = ServiceRequest::statusLabels();
?>
<div class="eg-page-head eg-page-head-start">
    <span class="eg-eyebrow">Your account</span>
    <h1 class="section-title">Bookings</h1>
</div>

<p class="mb-4">
    <a class="btn btn-eg-ghost" href="<?= $this->Url->build('/services/book') ?>">Book a service</a>
</p>

<?php if (count($requests) === 0) : ?>
    <p class="text-muted">You have not booked an installation or repair yet.</p>
<?php else : ?>
    <ul class="eg-cart-list">
        <?php foreach ($requests as $request) : ?>
            <li class="eg-card p-4 mb-3">
                <p class="mb-1">
                    <a href="<?= $this->Url->build('/account/bookings/' . (int)$request->id) ?>">
                        <?= h($request->request_number) ?>
                    </a>
                </p>
                <p class="account-order-status mb-1">
                    <?= h($request->service_type->name ?? 'Service') ?>
                    · <?= h($labels[$request->status] ?? $request->status) ?>
                </p>
                <p class="mb-0 product-meta">
                    <?= h($request->suburb) ?> <?= h($request->state) ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
