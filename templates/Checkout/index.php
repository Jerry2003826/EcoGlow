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
    
    <?php if($customer): ?>

        <h3>Customer Information</h3>
        <p><strong>Name:</strong> <?= h($customer->CustomerName) ?></p>
        <p><strong>Email:</strong> <?= h($customer->Email) ?></p>
        <p><strong>Phone:</strong> <?= h($customer->Phone) ?></p>
        <?= $this->Form->control('Address', [
            'label' => 'Delivery Address',
            'value' => $customer->Address,
            'required' => true,
        ]) ?>

    <?php else: ?>


        <?= $this->Form->control('GuestName', [
            'required' => true,
            'pattern' => '[A-Za-z ]+',
            'title' => 'Please enter letters only.',
        ]) ?>

        <?= $this->Form->control('Email', [
            'required' => true,
            'type' => 'email',
            'title' => 'Please enter a valid email address (e.g., example@example.com).',
        ]) ?>
        <?php if (!empty($errors['Email'])): ?>
            <p class="error">Please enter your email address with correct format (e.g., example@example.com).</p>
        <?php endif; ?>

        <?= $this->Form->control('Address', [
            'required' => true,
        ]) ?>

        <?= $this->Form->control('Phone', [
            'required' => true,
            'inputmode' => 'numeric',
            'pattern' => '04[0-9]{8}',
            'title' => 'Please enter a valid Australian mobile number starting with 04 followed by 8 digits.',
        ]) ?>

    <?php endif; ?>

    <?= $this->Form->control('card_number', [
        'label' => 'Test Card Number',
        'required' => true,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]{16}',
        'title' => 'Please enter a valid 16-digit test card number.',
    ]) ?>

    <?= $this->Form->control('expiry_date', [
        'label' => 'Expiry Date',
        'required' => true,
        'inputmode' => 'numeric',
        'pattern' => '(0[1-9]|1[0-2])/[0-9]{2}',
        'title' => 'Please enter a valid expiry date in MM/YY format.',
    ]) ?>

    <?= $this->Form->control('cvv', [
        'label' => 'CVV',
        'required' => true,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]{3}',
        'maxlength' => 3,
        'title' => 'Please enter a valid 3-digit CVV.',
    ]) ?>
    <p>Use test card number 4242424242424242 and CVV 123 with expiry date 12/31</p>

    <?= $this->Form->button('Complete Order') ?>

    <?= $this->Form->end() ?>
</div>
