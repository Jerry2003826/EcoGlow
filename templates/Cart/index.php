<?php
// templates/Cart/index.php

$this->Html->css('cart', ['block' => true]);
?>

<div class="cart-page">
    <section class="cart-banner" aria-labelledby="cart-page-title">
        <div class="cart-banner-content">
            <h1 id="cart-page-title">Shopping Cart</h1>
        </div>
    </section>

    <div class="cart-content">
        <?php if (empty($items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <?= $this->Html->link(
                    'Continue Shopping',
                    '/products',
                    ['class' => 'button']
                ) ?>
            </div>
        <?php else: ?>
            <?php $subtotal = 0.00; ?>
            <?php foreach ($items as $item): ?>
                <?php $subtotal += (float)$item->product->Price * (int)$item->Quantity; ?>
            <?php endforeach; ?>

            <?= $this->Form->create() ?>

            <div class="cart-layout">
                <section class="cart-items" aria-labelledby="cart-items-title">
                    <div class="cart-section-heading">
                        <div>
                            <h2 id="cart-items-title">Your Cart</h2>
                        </div>
                        <span><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></span>
                    </div>

                    <div class="cart-item-list">
                        <?php foreach ($items as $item): ?>
                            <article class="cart-item">

                                <div class="cart-item-media">
                                    <?= $this->Html->image($item->product->Image, [
                                        'class' => 'cart-item-thumb',
                                        'alt' => (string)$item->product->ProductName,
                                    ]) ?>
                                </div>

                                <div class="cart-item-details">
                                    <div class="cart-item-heading">
                                        <h3><?= h($item->product->ProductName) ?></h3>
                                        
                                    </div>

                                    <p class="cart-item-description">
                                        <?= h((string)($item->product->Description ?? 'Thoughtfully selected lighting for a warmer home.')) ?>
                                    </p>

                                    <div class="cart-item-meta">
                                        <strong>$<?= number_format((float)$item->product->Price * (int)$item->Quantity, 2) ?></strong>
                                    </div>
                                </div>

                                <div class="cart-item-controls">
                                    <span class="quantity-label">Quantity</span>
                                    <div class="quantity-stepper">
                                        <button
                                            type="button"
                                            class="quantity-button"
                                            aria-label="Decrease quantity"
                                            onclick="updateCartQuantity('cart-quantity-<?= (int)$item->CartItemId ?>', -1, <?= (int)($item->product->inventory?->Quantity ?? 1) ?>)"
                                        >
                                            &minus;
                                        </button>
                                        <span
                                            id="cart-quantity-display-<?= (int)$item->CartItemId ?>"
                                            class="quantity-value"
                                            aria-live="polite"
                                        ><?= (int)$item->Quantity ?></span>
                                        <input
                                            id="cart-quantity-<?= (int)$item->CartItemId ?>"
                                            type="hidden"
                                            name="qty[<?= (int)$item->CartItemId ?>]"
                                            value="<?= (int)$item->Quantity ?>"
                                        >
                                        <button
                                            type="button"
                                            class="quantity-button"
                                            aria-label="Increase quantity"
                                            onclick="updateCartQuantity('cart-quantity-<?= (int)$item->CartItemId ?>', 1, <?= (int)($item->product->inventory?->Quantity ?? 1) ?>)"
                                        >
                                            &plus;
                                        </button>
                                    </div>
                                </div>
                                    <button
                                        type="submit"
                                        name="remove_item"
                                        value="<?= (int)$item->CartItemId ?>"
                                        class="cart-remove"
                                    >
                                        Remove
                                    </button>
                                
                                    
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="install-box">
                        <label for="install-opt-in">
                            <span>Professional installation:  Let our team install each item in your cart for $80 per item.</span>
                        </label>
                        <input
                            id="install-opt-in"
                            type="checkbox"
                            name="install_opt_in"
                            value="1"
                            <?= ((int)$cart->InstallOptIn === 1) ? 'checked' : '' ?>
                            onchange="this.form.submit()"
                        >
                    </div>

                    <div class="cart-actions">
                        <?= $this->Html->link(
                            'Continue Shopping',
                            '/products',
                            ['class' => 'button button-secondary']
                        ) ?>
                    </div>
                </section>

                <aside class="cart-summary" aria-labelledby="cart-summary-title">
                    <p class="cart-eyebrow">Order total</p>
                    <h2 id="cart-summary-title">Summary</h2>
                    <dl>
                        <div>
                            <dt>Subtotal</dt>
                            <dd>$<?= number_format($subtotal, 2) ?></dd>
                        </div>
                        <div>
                            <dt>Installation fee</dt>
                            <dd>$<?= number_format((float)($cart->InstallFee ?? 0), 2) ?></dd>
                        </div>
                        <div class="summary-total">
                            <dt>Total</dt>
                            <dd>$<?= number_format((float)$grandTotal, 2) ?></dd>
                        </div>
                    </dl>
                    <?= $this->Html->link(
                        'Checkout',
                        '/checkout',
                        ['class' => 'button summary-checkout']
                    ) ?>
                   
                    <?= $this->Html->link(
                        'Continue Shopping',
                        '/products',
                        ['class' => 'button button-secondary button-secondary-checkout']
                    ) ?>
                
                    <p class="summary-note">Delivery details are confirmed at checkout.</p>
                </aside>
            </div>

            <?= $this->Form->end() ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateCartQuantity(inputId, change, maximum) {
        const input = document.getElementById(inputId);
        const display = document.getElementById(inputId.replace('cart-quantity-', 'cart-quantity-display-'));
        const nextQuantity = Math.max(1, Math.min(maximum, Number(input.value) + change));

        input.value = nextQuantity;
        display.textContent = nextQuantity;
        input.form.submit();
    }
</script>
