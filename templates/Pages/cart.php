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
 * @var list<array<string, mixed>> $cartLines
 * @var list<array<string, mixed>> $savedLines
 * @var float $freeDeliveryFrom
 * @var float $deliveryFlat
 * @var float $subtotal
 * @var float $delivery
 * @var float $total
 * @var float $gstIncluded
 * @var float $awayFromFree
 */
$this->assign('title', 'Your Basket');

$cartLines = $cartLines ?? [];
$savedLines = $savedLines ?? [];
$freeDeliveryFrom = $freeDeliveryFrom ?? 150.00;
$deliveryFlat = $deliveryFlat ?? 14.95;
$subtotal = $subtotal ?? 0.0;
$delivery = $delivery ?? 0.0;
$total = $total ?? 0.0;
$gstIncluded = $gstIncluded ?? 0.0;
$awayFromFree = $awayFromFree ?? 0.0;
$isEmpty = $cartLines === [];

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

    <?php $cartFlash = $this->Flash->render(); ?>
    <?php if ($cartFlash) : ?>
        <?= $cartFlash ?>
    <?php endif; ?>

    <div class="row g-4 g-lg-5" data-cart
         data-free-from="<?= h((string)$freeDeliveryFrom) ?>"
         data-delivery="<?= h((string)$deliveryFlat) ?>">
        <div class="col-lg-8 reveal">
            <ul class="eg-cart-list" data-cart-list <?= $isEmpty ? 'hidden' : '' ?>>
                <?php foreach ($cartLines as $lineIndex => $line) : ?>
                    <?php
                    $lineHref = !empty($line['slug'])
                        ? $this->Url->build('/shop/product/' . $line['slug'])
                        : $productUrl;
                    ?>
                    <li class="eg-cart-line" data-cart-line data-price="<?= h((string)$line['price']) ?>">
                        <span class="eg-cart-thumb">
                            <?= $this->Html->image('products/' . $line['image'], [
                                'alt' => '',
                                'width' => 800,
                                'height' => 800,
                                'loading' => $lineIndex === 0 ? 'eager' : 'lazy',
                                'decoding' => 'async',
                            ]) ?>
                        </span>

                        <div class="eg-cart-name">
                            <a class="eg-cart-title" href="<?= h($lineHref) ?>"><?= h($line['name']) ?></a>
                            <p class="product-meta mb-0"><?= h($line['meta']) ?></p>
                            <p class="product-meta mb-0">Finish: <?= h($line['variant']) ?></p>
                            <?= $this->Form->create(null, ['url' => '/cart/remove']) ?>
                            <?= $this->Form->hidden('item_id', ['value' => (int)($line['id'] ?? 0)]) ?>
                            <button type="submit" class="eg-cart-remove">
                                Remove<span class="visually-hidden"> <?= h($line['name']) ?> from basket</span>
                            </button>
                            <?= $this->Form->end() ?>
                            <?= $this->Form->create(null, ['url' => '/cart/save-later']) ?>
                            <?= $this->Form->hidden('item_id', ['value' => (int)($line['id'] ?? 0)]) ?>
                            <button type="submit" class="eg-cart-remove">
                                Save for later<span class="visually-hidden"> <?= h($line['name']) ?></span>
                            </button>
                            <?= $this->Form->end() ?>
                        </div>

                        <p class="eg-cart-unit">
                            <span class="eg-cart-label">Each</span>
                            $<?= h(number_format($line['price'], 2)) ?>
                        </p>

                        <?= $this->Form->create(null, [
                            'url' => '/cart/update',
                            'class' => 'eg-cart-qty',
                            'data-cart-persist' => '1',
                        ]) ?>
                        <?= $this->Form->hidden('item_id', ['value' => (int)($line['id'] ?? 0)]) ?>
                            <label class="eg-cart-label" for="cart-qty-<?= $lineIndex ?>">Quantity</label>
                            <div class="eg-qty" data-qty>
                                <button type="button" data-qty-step="-1"
                                        aria-label="Decrease quantity of <?= h($line['name']) ?>">&minus;</button>
                                <input type="number" id="cart-qty-<?= $lineIndex ?>" name="quantity"
                                       value="<?= h((string)$line['qty']) ?>" min="1" max="99"
                                       inputmode="numeric" autocomplete="off" data-qty-input>
                                <button type="button" data-qty-step="1"
                                        aria-label="Increase quantity of <?= h($line['name']) ?>">+</button>
                            </div>
                        <?= $this->Form->end() ?>

                        <p class="eg-cart-total">
                            <span class="eg-cart-label">Subtotal</span>
                            <span data-cart-line-total>
                                $<?= h(number_format($line['price'] * $line['qty'], 2)) ?>
                            </span>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="eg-card p-4 text-center" data-cart-empty <?= $isEmpty ? '' : 'hidden' ?>>
                <p class="mb-3">Your basket is empty.</p>
                <a class="btn btn-eg-primary" href="<?= h($shopUrl) ?>">
                    Browse the range
                    <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>
            </div>

            <?php if ($savedLines !== []) : ?>
                <h2 class="section-title h4 mt-5 mb-3">Saved for later</h2>
                <ul class="eg-cart-list">
                    <?php foreach ($savedLines as $saved) : ?>
                        <li class="eg-cart-line">
                            <span class="eg-cart-thumb">
                                <?= $this->Html->image('products/' . $saved['image'], [
                                    'alt' => '',
                                    'width' => 800,
                                    'height' => 800,
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                ]) ?>
                            </span>
                            <div class="eg-cart-name">
                                <span class="eg-cart-title"><?= h($saved['name']) ?></span>
                                <p class="product-meta mb-0"><?= h($saved['meta']) ?></p>
                                <p class="product-meta mb-0">Finish: <?= h($saved['variant']) ?></p>
                                <?= $this->Form->create(null, ['url' => '/cart/move-to-cart']) ?>
                                <?= $this->Form->hidden('saved_id', ['value' => (int)$saved['id']]) ?>
                                <button type="submit" class="eg-cart-remove">Move to basket</button>
                                <?= $this->Form->end() ?>
                            </div>
                            <p class="eg-cart-unit">
                                <span class="eg-cart-label">Each</span>
                                $<?= h(number_format($saved['price'], 2)) ?>
                            </p>
                            <p class="eg-cart-qty">
                                <span class="eg-cart-label">Quantity</span>
                                <?= (int)$saved['qty'] ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

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
                    <?php if ($isEmpty) : ?>
                        <button type="button" class="btn btn-eg-primary" disabled
                                aria-describedby="checkout-pending">
                            Proceed to checkout
                        </button>
                    <?php else : ?>
                        <a href="<?= $this->Url->build('/checkout') ?>" class="btn btn-eg-primary">
                            Proceed to checkout
                        </a>
                    <?php endif; ?>
                </div>
                <p class="eg-note" id="checkout-pending">
                    <?php if ($isEmpty) : ?>
                        Add a lamp to continue to checkout.
                    <?php else : ?>
                        You will sign in if needed, then pay in full on the next page.
                    <?php endif; ?>
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
<?php $this->append('scriptBottom'); ?>
<script>
document.querySelector('[data-cart]')?.addEventListener('eg:qty', function (event) {
    var form = event.target.closest('form[data-cart-persist]');
    if (form) {
        form.submit();
    }
});
document.querySelector('[data-cart]')?.addEventListener('change', function (event) {
    if (event.target.matches('[data-qty-input]')) {
        var form = event.target.closest('form[data-cart-persist]');
        if (form) {
            form.submit();
        }
    }
});
</script>
<?php $this->end(); ?>
