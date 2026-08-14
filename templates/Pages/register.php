<?php
/**
 * Customer registration page — warm-earth storefront theme.
 *
 * Static page: `PagesController::display()` renders this through the explicit
 * `/register` route in config/routes.php.
 *
 * PENDING CLIENT CONFIRMATION — the field set below is a proposal, not a spec.
 * The requirements document only says customers "register and log in when
 * purchasing"; it does not list the fields, and the client has not confirmed
 * them. So this is the smallest set that can create an account at all: name,
 * email, password, confirmation. Likely additions once the client decides are a
 * delivery address, a phone number for installation bookings, and a marketing
 * opt-in — each of which is a new control here plus a column on the customers
 * table. Nothing else on the page depends on the list, so adding a field is a
 * local change.
 *
 * There is no customers table and no session for a shopper, so submission is
 * disabled rather than posted somewhere inert. The fields themselves are real —
 * labelled, typed, and carrying the autocomplete and validation attributes the
 * live form will use — so the client can review the shape of the form now.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Create an Account');
?>
<div class="container">
    <nav aria-label="Breadcrumb" class="pt-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li aria-current="page">Create account</li>
        </ol>
    </nav>

    <div class="auth-wrap">
        <div class="eg-card auth-card reveal">
            <div class="auth-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h1 class="h3 mb-1 text-center">Create your account</h1>
            <p class="text-muted text-center small mb-4">
                Track orders, save finishes and book installation in one place.
            </p>

            <?= $this->Form->create(null) ?>
            <div class="mb-3">
                <?= $this->Form->control('name', [
                    'type' => 'text',
                    'label' => 'Full name',
                    'class' => 'form-control',
                    'autocomplete' => 'name',
                    'required' => true,
                ]) ?>
            </div>
            <div class="mb-3">
                <?= $this->Form->control('email', [
                    'type' => 'email',
                    'label' => 'Email',
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'required' => true,
                ]) ?>
            </div>
            <div class="mb-3">
                <?= $this->Form->control('password', [
                    'type' => 'password',
                    'label' => 'Password',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'minlength' => 8,
                    'aria-describedby' => 'password-help',
                    'required' => true,
                ]) ?>
                <p class="form-text mb-0" id="password-help">At least 8 characters.</p>
            </div>
            <div class="mb-4">
                <?= $this->Form->control('password_confirm', [
                    'type' => 'password',
                    'label' => 'Confirm password',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'minlength' => 8,
                    'required' => true,
                ]) ?>
            </div>
            <div class="d-grid">
                <?= $this->Form->button(__('Create account'), [
                    'class' => 'btn btn-eg-primary',
                    'disabled' => true,
                    'aria-describedby' => 'register-pending',
                ]) ?>
            </div>
            <?= $this->Form->end() ?>

            <p class="eg-note mb-0" id="register-pending">
                Accounts go live with the customer backend, and the fields above are still
                waiting on the client&rsquo;s sign-off. Need something now?
                <a href="<?= $this->Url->build('/contact') ?>">Send us a message</a>.
            </p>
        </div>
    </div>
</div>
