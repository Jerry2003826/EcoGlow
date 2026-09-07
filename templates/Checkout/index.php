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
                <td><?= h($item->product->ProductName) ?></td>
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
    <?= $this->Form->control('GuestName') ?>
    <?= $this->Form->control('Email') ?>
    <?= $this->Form->control('Address') ?>
    <?= $this->Form->control('Phone') ?>
    <?= $this->Form->control('card_number', [
        'label' => 'Test Card Number',
        'value' => '4242424242424242',
    ]) ?>

    <p>Use test card number 4242424242424242</p>

    <?= $this->Form->button('Complete Order') ?>
    <?= $this->Form->end() ?>
</div>
