<?php
/**
 * Single-page checkout: address, shipping, review, Stripe Payment Element.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var list<array<string, mixed>> $lines
 * @var array<string, int|string> $totals
 * @var iterable<\App\Model\Entity\Address> $addresses
 * @var array<string, string> $states
 * @var bool $paymentsEnabled
 * @var string $publishableKey
 * @var string|null $clientSecret
 * @var \App\Model\Entity\SalesOrder|null $order
 * @var array<string, string> $errors
 */

use Cake\Routing\Router;

$this->assign('title', 'Checkout');
$this->Html->css(['account', 'checkout'], ['block' => true]);

$lines = $lines ?? [];
$totals = $totals ?? [];
$addresses = $addresses ?? [];
$states = $states ?? [];
$errors = $errors ?? [];
$paymentsEnabled = $paymentsEnabled ?? false;
$stripeConfigured = $stripeConfigured ?? false;
$publishableKey = $publishableKey ?? '';
$clientSecret = $clientSecret ?? null;
$order = $order ?? null;
$isEmpty = $lines === [];
$formError = (string)($errors['form'] ?? '');
$confirmationUrl = $order
    ? Router::url('/checkout/confirmation/' . (int)$order->id, true)
    : '';
?>
<div class="container py-5 checkout-page account-page">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= $this->Url->build('/cart') ?>">Basket</a></li>
            <li aria-current="page">Checkout</li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Secure checkout</span>
        <h1 class="section-title">Checkout</h1>
    </div>

    <?php if ($formError !== '') : ?>
        <div class="checkout-alert" role="alert" id="checkout-form-error">
            <?= h($formError) ?>
        </div>
    <?php endif; ?>

    <?php if ($isEmpty && $clientSecret === null) : ?>
        <p class="text-muted">Your basket is empty.</p>
        <a class="btn btn-eg-primary" href="<?= $this->Url->build('/shop') ?>">Continue shopping</a>
    <?php else : ?>
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <?php if ($clientSecret === null) : ?>
                    <?= $this->Form->create(null, [
                        'url' => '/checkout',
                        'id' => 'checkout-form',
                        'novalidate' => true,
                    ]) ?>

                    <section class="eg-card p-4 p-md-5 mb-4" aria-labelledby="checkout-address-heading">
                        <h2 id="checkout-address-heading" class="checkout-section-title">Delivery address</h2>
                        <?php if (count($addresses) > 0) : ?>
                            <fieldset class="mb-4">
                                <legend class="checkout-legend">Saved addresses</legend>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="saved_address_id"
                                           id="saved-address-new" value="0" checked>
                                    <label class="form-check-label" for="saved-address-new">Use a new address</label>
                                </div>
                                <?php foreach ($addresses as $row) : ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="saved_address_id"
                                               id="saved-address-<?= (int)$row->id ?>"
                                               value="<?= (int)$row->id ?>">
                                        <label class="form-check-label" for="saved-address-<?= (int)$row->id ?>">
                                            <?= h($row->recipient_name) ?> —
                                            <?= h($row->line1) ?>,
                                            <?= h($row->suburb) ?>
                                            <?= h($row->state) ?>
                                            <?= h($row->postcode) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label" for="recipient-name">Recipient name</label>
                            <input class="form-control" type="text" name="recipient_name" id="recipient-name"
                                   required autocomplete="name" minlength="2"
                                   aria-describedby="<?= $formError !== '' ? 'checkout-form-error' : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="line1">Street address</label>
                            <input class="form-control" type="text" name="line1" id="line1"
                                   required autocomplete="address-line1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="line2">Apartment, suite (optional)</label>
                            <input class="form-control" type="text" name="line2" id="line2"
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
                                   required inputmode="numeric" pattern="\d{4}" maxlength="4"
                                   autocomplete="postal-code" aria-describedby="postcode-hint">
                            <p class="form-text" id="postcode-hint">Four digits, for example 3000.</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control" type="tel" name="phone" id="phone" autocomplete="tel">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="save_address" value="1"
                                   id="save-address">
                            <label class="form-check-label" for="save-address">Save this address to my account</label>
                        </div>
                    </section>

                    <div class="d-grid mb-4">
                        <?php if ($paymentsEnabled && $stripeConfigured) : ?>
                            <?= $this->Form->button('Continue to payment', [
                                'class' => 'btn btn-eg-primary',
                                'id' => 'checkout-submit',
                            ]) ?>
                        <?php elseif (!$paymentsEnabled) : ?>
                            <p class="checkout-alert" role="status">
                                Online payment is not open yet. Please contact us to complete your order.
                            </p>
                        <?php else : ?>
                            <p class="checkout-alert" role="status">
                                Card payment is not configured on this server yet. Your basket is held;
                                please contact us to complete the order.
                            </p>
                        <?php endif; ?>
                    </div>
                    <?= $this->Form->end() ?>
                <?php else : ?>
                    <section class="eg-card p-4 p-md-5 mb-4" aria-labelledby="checkout-pay-heading">
                        <h2 id="checkout-pay-heading" class="checkout-section-title">Payment</h2>
                        <p class="mb-3">
                            Order <?= h($order->order_number ?? '') ?> is held pending payment.
                            Do not refresh this page until the payment finishes.
                        </p>
                        <?php if ($publishableKey === '') : ?>
                            <p class="checkout-alert" role="alert">
                                Stripe is not configured on this server yet. Your order is saved;
                                a staff member can take payment once keys are in place.
                            </p>
                        <?php else : ?>
                            <div id="payment-element" class="checkout-payment-element"></div>
                            <p id="payment-message" class="checkout-alert mt-3" role="alert" hidden></p>
                            <div class="d-grid mt-3">
                                <button type="button" class="btn btn-eg-primary" id="pay-button">
                                    Pay <?= $this->Money->aud((int)($totals['total_cents'] ?? 0)) ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <section class="eg-card eg-summary p-4 p-md-5 mb-4" aria-labelledby="checkout-ship-heading">
                    <h2 id="checkout-ship-heading" class="checkout-section-title">Delivery</h2>
                    <?php if ((int)($totals['shipping_cents'] ?? 0) === 0) : ?>
                        <p class="mb-0">Free delivery — this order is at or above
                            <?= $this->Money->aud((int)($totals['free_threshold_cents'] ?? 15000)) ?>.</p>
                    <?php else : ?>
                        <p class="mb-0">Standard delivery
                            <?= $this->Money->aud((int)($totals['shipping_cents'] ?? 0)) ?>.
                            Free from <?= $this->Money->aud((int)($totals['free_threshold_cents'] ?? 15000)) ?>.</p>
                    <?php endif; ?>
                </section>

                <section class="eg-card eg-summary p-4 p-md-5" aria-labelledby="checkout-review-heading">
                    <h2 id="checkout-review-heading" class="checkout-section-title">Order review</h2>
                    <ul class="checkout-lines">
                        <?php foreach ($lines as $line) : ?>
                            <li>
                                <span>
                                    <?= h($line['name'] ?? '') ?>
                                    <?php if (!empty($line['variant']) && ($line['variant'] ?? '') !== 'Default') : ?>
                                        <span class="product-meta"> · <?= h($line['variant']) ?></span>
                                    <?php endif; ?>
                                    <span class="product-meta"> × <?= (int)($line['qty'] ?? $line['quantity'] ?? 1) ?></span>
                                </span>
                                <span><?= $this->Money->aud((int)($line['line_total_cents'] ?? 0)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="eg-kv-list mb-0">
                        <div class="eg-kv-row">
                            <dt>Subtotal</dt>
                            <dd><?= $this->Money->aud((int)($totals['subtotal_cents'] ?? 0)) ?></dd>
                        </div>
                        <div class="eg-kv-row">
                            <dt>Delivery</dt>
                            <dd><?= $this->Money->aud((int)($totals['shipping_cents'] ?? 0)) ?></dd>
                        </div>
                        <div class="eg-kv-row is-total">
                            <dt>Total</dt>
                            <dd><?= $this->Money->aud((int)($totals['total_cents'] ?? 0)) ?></dd>
                        </div>
                    </dl>
                    <p class="eg-summary-note mb-0">
                        Includes GST of <?= $this->Money->aud((int)($totals['gst_cents'] ?? 0)) ?>.
                    </p>
                </section>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php if ($clientSecret && $publishableKey !== '') : ?>
    <?php $this->append('scriptBottom'); ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    var stripe = Stripe(<?= json_encode($publishableKey) ?>);
    var elements = stripe.elements({
        clientSecret: <?= json_encode($clientSecret) ?>,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#124C24',
                colorBackground: '#FBF9F5',
                colorText: '#2F2E2C',
                colorDanger: '#7A2617',
                fontFamily: 'Inter, system-ui, sans-serif',
                borderRadius: '0.3rem',
                colorTextSecondary: '#5B5545'
            },
            rules: {
                '.Input': {
                    borderColor: '#C9BCA9',
                    backgroundColor: '#FBF9F5',
                    color: '#2F2E2C',
                    boxShadow: 'none'
                },
                '.Input:focus': {
                    borderColor: '#124C24',
                    boxShadow: '0 0 0 0.2rem rgba(18, 76, 36, 0.14)'
                },
                '.Label': {
                    color: '#2F2E2C'
                }
            }
        }
    });
    var paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');
    var payButton = document.getElementById('pay-button');
    var messageEl = document.getElementById('payment-message');
    payButton.addEventListener('click', function () {
        payButton.disabled = true;
        stripe.confirmPayment({
            elements: elements,
            confirmParams: {
                return_url: <?= json_encode($confirmationUrl) ?>
            }
        }).then(function (result) {
            if (result.error) {
                messageEl.hidden = false;
                messageEl.textContent = result.error.message || 'Payment could not be completed.';
                payButton.disabled = false;
            }
        });
    });
})();
</script>
    <?php $this->end(); ?>
<?php endif; ?>
