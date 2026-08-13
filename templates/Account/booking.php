<?php
/**
 * Customer service request detail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\ServiceRequest $request
 */

use App\Model\Entity\ServiceRequest;

$this->assign('title', $request->request_number);
$this->Html->css('account', ['block' => true]);
$labels = ServiceRequest::statusLabels();
$appointment = $request->service_appointments[0] ?? null;
?>
<div class="container py-5 account-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= $this->Url->build('/account') ?>">Account</a></li>
            <li><a href="<?= $this->Url->build('/account/bookings') ?>">Bookings</a></li>
            <li aria-current="page"><?= h($request->request_number) ?></li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Booking</span>
        <h1 class="section-title"><?= h($request->request_number) ?></h1>
        <p class="account-order-status mb-0">
            <?= h($labels[$request->status] ?? $request->status) ?>
        </p>
    </div>

    <?= $this->element('account/nav', ['current' => 'bookings']) ?>

    <div class="eg-card p-4 mb-4">
        <h2 class="h5 mb-3"><?= h($request->service_type->name ?? 'Service') ?></h2>
        <p><?= nl2br(h($request->issue_description)) ?></p>
        <p class="mb-0">
            <?= h($request->address_line1) ?><br>
            <?= h($request->suburb) ?> <?= h($request->state) ?> <?= h($request->postcode) ?>
        </p>
    </div>

    <?php if ($appointment) : ?>
        <div class="eg-card p-4">
            <h2 class="h5 mb-3">Scheduled visit</h2>
            <p class="mb-0">
                <?= h($appointment->starts_at?->setTimezone('Australia/Melbourne')->format('d M Y, H:i')) ?>
                –
                <?= h($appointment->ends_at?->setTimezone('Australia/Melbourne')->format('H:i')) ?>
                (Melbourne time)
            </p>
        </div>
    <?php endif; ?>
</div>
