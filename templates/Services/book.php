<?php
/**
 * Customer installation and repair request.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ServiceType> $serviceTypes
 * @var array<string, string> $states
 * @var array<string, string> $windows
 * @var bool $bookingsOpen
 * @var \App\Model\Entity\Customer|null $customer
 */
$this->assign('title', 'Book installation or repair');
$this->Html->css(['account', 'checkout'], ['block' => true]);
$serviceTypes = $serviceTypes ?? [];
$states = $states ?? [];
$windows = $windows ?? [];
$bookingsOpen = $bookingsOpen ?? false;
$customer = $customer ?? null;
?>
<div class="container py-5 checkout-page account-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Book a service</li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Licensed work</span>
        <h1 class="section-title">Book installation or repair</h1>
    </div>

    <?php if (!$bookingsOpen) : ?>
        <p class="checkout-alert" role="status">
            Installation and repair bookings are not open yet. Please use the contact form.
        </p>
        <a class="btn btn-eg-primary" href="<?= $this->Url->build('/contact') ?>">Contact us</a>
    <?php else : ?>
        <div class="eg-card p-4 p-md-5" style="max-width: 40rem;">
            <?= $this->Form->create(null, ['url' => '/services/book']) ?>
            <div class="mb-3">
                <label class="form-label" for="service-type-id">Service type</label>
                <select class="form-select" name="service_type_id" id="service-type-id" required>
                    <option value="">Select</option>
                    <?php foreach ($serviceTypes as $type) : ?>
                        <option value="<?= (int)$type->id ?>"><?= h($type->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="contact-name">Contact name</label>
                <input class="form-control" type="text" name="contact_name" id="contact-name" required
                       autocomplete="name"
                       value="<?= h($customer->first_name . ' ' . (string)$customer->last_name) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="contact-phone">Phone</label>
                <input class="form-control" type="tel" name="contact_phone" id="contact-phone"
                       autocomplete="tel" value="<?= h((string)$customer->phone) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="address-line1">Street address</label>
                <input class="form-control" type="text" name="address_line1" id="address-line1"
                       required autocomplete="address-line1">
            </div>
            <div class="mb-3">
                <label class="form-label" for="address-line2">Apartment, suite (optional)</label>
                <input class="form-control" type="text" name="address_line2" id="address-line2"
                       autocomplete="address-line2">
            </div>
            <div class="mb-3">
                <label class="form-label" for="suburb">Suburb / city</label>
                <input class="form-control" type="text" name="suburb" id="suburb"
                       required autocomplete="address-level2">
            </div>
            <div class="mb-3">
                <label class="form-label" for="state">State or territory</label>
                <select class="form-select" name="state" id="state" required autocomplete="address-level1">
                    <option value="">Select</option>
                    <?php foreach ($states as $code => $label) : ?>
                        <option value="<?= h($code) ?>"><?= h($code) ?> — <?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="postcode">Postcode</label>
                <input class="form-control" type="text" name="postcode" id="postcode"
                       required inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="postal-code">
            </div>
            <div class="mb-3">
                <label class="form-label" for="preferred-date">Preferred date (optional)</label>
                <input class="form-control" type="date" name="preferred_date" id="preferred-date">
            </div>
            <fieldset class="mb-3">
                <legend class="checkout-legend">Preferred time of day</legend>
                <?php foreach ($windows as $key => $label) : ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="preferred_window"
                               id="window-<?= h($key) ?>" value="<?= h($key) ?>" required>
                        <label class="form-check-label" for="window-<?= h($key) ?>"><?= h($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <div class="mb-4">
                <label class="form-label" for="issue-description">What do you need?</label>
                <textarea class="form-control" name="issue_description" id="issue-description"
                          required rows="5" minlength="10"></textarea>
            </div>
            <div class="d-grid">
                <?= $this->Form->button('Send request', ['class' => 'btn btn-eg-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    <?php endif; ?>
</div>
