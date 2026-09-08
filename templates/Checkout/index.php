<?php
// templates/Checkout/index.php
?>

<div class="checkout-card">
    <h1>Checkout</h1>

    <table class="receipt-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit Price</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?= h($item->product->ProductName) ?>
                    <?php if ((int)$cart->InstallOptIn === 1): ?>
                        <div>Installation: $80 x <?= (int)$item->Quantity ?></div>
                    <?php endif; ?>
                </td>
                <td><?= (int)$item->Quantity ?></td>
                <td>$<?= number_format((float)$item->product->Price, 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p>Products Total: $<?= number_format($productsTotal, 2) ?></p>
    <p>Installation Fee: $<?= number_format($installFee, 2) ?></p>
    <p>Grand Total: $<?= number_format($grandTotal, 2) ?></p>

    <?= $this->Form->create() ?>

    <?php if (!empty($errors['GuestName'])): ?>
        <p class="error">Please enter your name.</p>
    <?php endif; ?>

    <?= $this->Form->control('GuestName') ?>

    <?php if (!empty($errors['Email'])): ?>
        <p class="error">Please enter your email address.</p>
    <?php endif; ?>

    <?= $this->Form->control('Email') ?>

    <?= $this->Form->control('Address', [
        'required' => true,
    ]) ?>

    <?= $this->Form->control('Phone', [
        'required' => true,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]+',
    ]) ?>

    <?= $this->Form->control('card_number', [
        'label' => 'Test Card Number',
    ]) ?>

    <p>Use test card number 4242424242424242</p>

    <?= $this->Form->button('Complete Order') ?>

    <?= $this->Form->end() ?>
</div>
