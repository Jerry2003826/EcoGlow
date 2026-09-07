<?php
// templates/Cart/index.php
?>

<h1>Shopping Cart</h1>

<?= $this->Form->create() ?>

<?php if (!empty($items)): ?>
    <?php foreach ($items as $item): ?>
        <div>
            <strong><?= h($item->product->ProductName) ?></strong>

            <div>
                Qty:
                <input
                    type="number"
                    min="1"
                    name="qty[<?= (int)$item->CartItemId ?>]"
                    value="<?= (int)$item->Quantity ?>"
                >
            </div>
        </div>
    <?php endforeach; ?>

    <div>
        <label>
            <input
                type="checkbox"
                name="install_opt_in"
                value="1"
                <?= (!empty($cart) && (int)$cart->InstallOptIn === 1) ? 'checked' : '' ?>
            >
            Installation (+ $<?= number_format((float)($cart->InstallFee ?? 200), 2) ?>)
        </label>
    </div>

    <div>
        Total:
        $<?= number_format((float)$subtotal, 2) ?>
    </div>

    <?= $this->Form->button('Update Cart') ?>
<?php endif; ?>

<?= $this->Form->end() ?>

<p>
    <?= $this->Html->link(
        'Continue Shopping',
        ['controller' => 'Products', 'action' => 'index']
    ) ?>
</p>

<p>
    <?= $this->Html->link(
        'Checkout',
        ['controller' => 'Checkout', 'action' => 'index']
    ) ?>
</p>
