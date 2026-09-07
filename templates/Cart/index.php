<?php
// templates/Cart/index.php
?>

<h1>Shopping Cart</h1>

<?php if (empty($items)): ?>
    <div class="empty-cart">
        <p>Your cart is empty.</p>

        <?= $this->Html->link('Continue Shopping', '/products') ?>
    </div>
<?php else: ?>

    <?= $this->Form->create() ?>

    <table class="cart-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= h($item->product->ProductName) ?></td>
                <td>
                    <input
                        type="number"
                        min="1"
                        name="qty[<?= (int)$item->CartItemId ?>]"
                        value="<?= (int)$item->Quantity ?>"
                    >
                </td>
                <td>
                    <button
                        type="submit"
                        name="remove_item"
                        value="<?= (int)$item->CartItemId ?>"
                    >
                        Remove
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="install-box">
        <label>
            <input
                type="checkbox"
                name="install_opt_in"
                value="1"
                <?= ((int)($cart->InstallOptIn ?? 0) === 1) ? 'checked' : '' ?>
            >
            Installation (+ $200)
        </label>
    </div>

    <div class="cart-summary">
        Running Total:
        $<?= number_format((float)$subtotal, 2) ?>
    </div>

    <div class="cart-actions">
        <?= $this->Form->button('Update Cart') ?>
        <?= $this->Html->link('Continue Shopping', '/products') ?>
        <?= $this->Html->link('Checkout', '/checkout') ?>
    </div>

    <?= $this->Form->end() ?>

<?php endif; ?>
