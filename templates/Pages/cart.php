<?php
/**
 * Basket page — warm-earth storefront theme.
 *
 * Static page: `PagesController::display()` renders this through the explicit
 * `/cart` route in config/routes.php. There is no orders backend and no
 * session basket yet, so the lines below are a local placeholder array using
 * the same field names as the listing page, with a quantity added. When a real
 * basket lands, $cartLines arrives from the controller and the totals move
 * server-side; the markup and the summary rows stay as they are.
 *
 * Quantity and removal work in the browser and the totals recalculate with
 * them, so the page is a basket you can actually edit. Checkout is disabled —
 * it is the one control here that cannot do what its label says yet.
 *
 * GST is shown as an inclusive component rather than added on top, which is how
 * Australian retail prices are quoted: every price on the site already contains
 * it, so Total = Subtotal + Delivery, and the GST line reports Total ÷ 11.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Your Basket');

$cartLines = [
    [
        'icon' => 'decor',
        'name' => 'Linen Drum Shade',
        'meta' => 'Natural linen, 45 cm',
        'variant' => 'Natural',
        'price' => 59.00,
        'qty' => 1,
    ],
    [
        'icon' => 'smart',
        'name' => 'Nimbus Smart Downlight',
        'meta' => 'Tunable white, app control',
        'variant' => 'Warm white',
        'price' => 45.00,
        'qty' => 1,
    ],
    [
        'icon' => 'decor',
        'name' => 'Rowan Rotary Dimmer',
        'meta' => 'Trailing-edge, 250 W',
        'variant' => 'Charcoal',
        'price' => 39.00,
        'qty' => 1,
    ],
];

/** Matches the delivery promise in the announcement bar in templates/layout/default.php. */
$freeDeliveryFrom = 150.00;
$deliveryFlat = 14.95;

$subtotal = 0.0;
foreach ($cartLines as $line) {
    $subtotal += $line['price'] * $line['qty'];
}

$delivery = $subtotal >= $freeDeliveryFrom ? 0.0 : $deliveryFlat;
$total = $subtotal + $delivery;
$gstIncluded = $total / 11;
$awayFromFree = $freeDeliveryFrom - $subtotal;

$shopUrl = $this->Url->build('/shop');
$productUrl = $this->Url->build('/shop/product');
?>
<div class="container py-5">
    <nav aria-label="Breadcrumb" class="mb-4 reveal">
        <ol class="eg-breadcrumb">
            <li><a href="<?= $this->Url->build('/') ?>">Home</a></li>
            <li><a href="<?= h($shopUrl) ?>">Shop</a></li>
            <li aria-current="page">Basket</li>
        </ol>
    </nav>

    <div class="eg-page-head eg-page-head-start reveal">
        <span class="eg-eyebrow">Nearly there</span>
        <h1 class="section-title">Your basket</h1>
    </div>

    <div class="row g-4 g-lg-5" data-cart
         data-free-from="<?= h((string)$freeDeliveryFrom) ?>"
         data-delivery="<?= h((string)$deliveryFlat) ?>">
        <div class="col-lg-8 reveal">
            <ul class="eg-cart-list" data-cart-list>
                <?php foreach ($cartLines as $lineIndex => $line) : ?>
                    <li class="eg-cart-line" data-cart-line data-price="<?= h((string)$line['price']) ?>">
                        <span class="eg-cart-thumb" aria-hidden="true">
                            <?= $this->element('lamp_icon', ['name' => $line['icon']]) ?>
                        </span>

                        <div class="eg-cart-name">
                            <a class="eg-cart-title" href="<?= h($productUrl) ?>"><?= h($line['name']) ?></a>
                            <p class="product-meta mb-0"><?= h($line['meta']) ?></p>
                            <p class="product-meta mb-0">Finish: <?= h($line['variant']) ?></p>
                            <button type="button" class="eg-cart-remove" data-cart-remove>
                                Remove<span class="visually-hidden"> <?= h($line['name']) ?> from basket</span>
                            </button>
                        </div>

                        <p class="eg-cart-unit">
                            <span class="eg-cart-label">Each</span>
                            $<?= h(number_format($line['price'], 2)) ?>
                        </p>

                        <div class="eg-cart-qty">
                            <label class="eg-cart-label" for="cart-qty-<?= $lineIndex ?>">Quantity</label>
                            <div class="eg-qty" data-qty>
                                <button type="button" data-qty-step="-1"
                                        aria-label="Decrease quantity of <?= h($line['name']) ?>">&minus;</button>
                                <input type="number" id="cart-qty-<?= $lineIndex ?>"
                                       value="<?= h((string)$line['qty']) ?>" min="1" max="99"
                                       inputmode="numeric" autocomplete="off" data-qty-input>
                                <button type="button" data-qty-step="1"
                                        aria-label="Increase quantity of <?= h($line['name']) ?>">+</button>
                            </div>
                        </div>

                        <p class="eg-cart-total">
                            <span class="eg-cart-label">Subtotal</span>
                            <span data-cart-line-total>
                                $<?= h(number_format($line['price'] * $line['qty'], 2)) ?>
                            </span>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="eg-card p-4 text-center" data-cart-empty hidden>
                <p class="mb-3">Your basket is empty.</p>
                <a class="btn btn-eg-primary" href="<?= h($shopUrl) ?>">
                    Browse the range
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>
            </div>

            <a class="eg-cart-continue" href="<?= h($shopUrl) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                Continue shopping
            </a>
        </div>

        <div class="col-lg-4 reveal" data-reveal-step="1">
            <div class="eg-card eg-summary">
                <h2 class="h5 mb-3">Order summary</h2>

                <dl class="eg-kv-list mb-0">
                    <div class="eg-kv-row">
                        <dt>Subtotal</dt>
                        <dd data-cart-subtotal>$<?= h(number_format($subtotal, 2)) ?></dd>
                    </div>
                    <div class="eg-kv-row">
                        <dt>Delivery</dt>
                        <dd data-cart-delivery>
                            <?= $delivery > 0 ? '$' . h(number_format($delivery, 2)) : 'Free' ?>
                        </dd>
                    </div>
                    <div class="eg-kv-row is-total">
                        <dt>Total</dt>
                        <dd data-cart-grand>$<?= h(number_format($total, 2)) ?></dd>
                    </div>
                </dl>

                <p class="eg-summary-note" data-cart-gst>
                    Includes GST of $<?= h(number_format($gstIncluded, 2)) ?>.
                </p>

                <p class="eg-summary-hint" data-cart-hint <?= $awayFromFree > 0 ? '' : 'hidden' ?>>
                    Add $<span data-cart-away><?= h(number_format(max(0, $awayFromFree), 2)) ?></span>
                    more to qualify for free delivery.
                </p>

                <div class="d-grid mt-3">
                    <button type="button" class="btn btn-eg-primary" disabled
                            aria-describedby="checkout-pending">
                        Proceed to checkout
                    </button>
                </div>
                <p class="eg-note" id="checkout-pending">
                    Checkout and payment go live with the orders backend. The basket above
                    is a working preview of the layout, not a real order.
                </p>

                <p class="eg-summary-account mb-0">
                    Shopping with us for the first time?
                    <a href="<?= $this->Url->build('/register') ?>">Create an account</a>
                    to track orders and installation bookings.
                </p>
            </div>
        </div>
    </div>
</div>
