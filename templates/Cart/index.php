<?php
// templates/Cart/index.php
?>

<h1>Shopping Cart</h1>

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

<?= $this->Form->create() ?>

<table class="cart-table">
    <thead>
    <tr>
        <th>Item</th>
        <th>Quantity</th>
        <th>Total</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <?php
        $image = ((int)$item->product->CategoryId === 1)
            ? 'landing/pendants.jpg'
            : 'landing/wall-light.jpg';
        ?>
        <tr>
            <td class="cart-item-label">
                <?= $this->Html->image($image, [
                    'width' => 80,
                    'class' => 'cart-item-thumb',
                    'alt' => (string)$item->product->ProductName,
                ]) ?>
                <div>
                    <?= h($item->product->ProductName) ?>

                    <?php if ((int)$cart->InstallOptIn === 1): ?>
                        <div>
                            Installation:
                            $80 x <?= (int)$item->Quantity ?>
                            = $<?= number_format(80 * (int)$item->Quantity, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>

            <td>
                <input
                    type="number"
                    min="1"
                    max="<?= (int)($item->product->inventory?->Quantity ?? 1) ?>"
                    name="qty[<?= (int)$item->CartItemId ?>]"
                    value="<?= (int)$item->Quantity ?>"
                    onchange="this.form.submit()"
                >
            </td>

            <td>
                $<?= number_format(
                    (float)$item->product->Price * (int)$item->Quantity,
                    2
                ) ?>
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
            <?= ((int)$cart->InstallOptIn === 1) ? 'checked' : '' ?>
            onchange="this.form.submit()"
        >
        Add professional in-home installation for each item in this cart.
        The fee is $80 per item.
    </label>
</div>

<div class="cart-summary">
    Grand Total:
    $<?= number_format((float)$grandTotal, 2) ?>
</div>

<div class="cart-actions">
    <?= $this->Html->link(
        'Continue Shopping',
        '/products',
        ['class' => 'button']
    ) ?>
    <?= $this->Html->link(
        'Checkout',
        '/checkout',
        ['class' => 'button']
    ) ?>
</div>

<?= $this->Form->end() ?>

<?php endif; ?>
