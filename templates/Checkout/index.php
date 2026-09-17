<?php
// templates/Checkout/index.php
?>

<div class="checkout-card">
    <h1>Checkout</h1>

    <div class="checkout-section">
        <h2>Item summary</h2>

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
    </div>

    <?= $this->Form->create() ?>

    <div class="checkout-section">
        <?php if ($customer): ?>
            <h2>Customer information</h2>
            <p><strong>Name:</strong> <?= h($customer->CustomerName) ?></p>
            <p><strong>Email:</strong> <?= h($customer->Email) ?></p>
            <p><strong>Phone:</strong> <?= h($customer->Phone) ?></p>
            <?= $this->Form->control('Address', [
                'label' => 'Delivery Address',
                'value' => $customer->Address,
                'required' => true,
            ]) ?>
        <?php else: ?>
            <h2>Guest information</h2>
            <p class="checkout-note">
                You are checking out as a guest. Your contact details will be used for order updates and delivery communication.
            </p>
            <?= $this->Form->control('GuestName', [
                'label' => 'Name',
                'required' => true,
                'pattern' => '[A-Za-z ]+',
                'title' => 'Please enter letters only.',
            ]) ?>
            <?php if (!empty($errors['GuestName'])): ?>
                <p class="error">Please enter letters only for your name.</p>
            <?php endif; ?>

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
    </div>

    <div class="checkout-section">
        <h2>Payment details</h2>
        <?= $this->Form->control('card_number', [
            'label' => 'Test Card Number',
            'required' => true,
            'inputmode' => 'numeric',
            'pattern' => '[0-9]{16}',
            'title' => 'Please enter a valid 16-digit test card number.',
        ]) ?>
        <?php if (!empty($errors['card_number'])): ?>
            <p class="error">Please enter the 16-digit test card number.</p>
        <?php endif; ?>

        <?= $this->Form->control('expiry_date', [
            'label' => 'Expiry Date',
            'required' => true,
            'inputmode' => 'numeric',
            'pattern' => '(0[1-9]|1[0-2])/[0-9]{2}',
            'title' => 'Please enter a valid expiry date in MM/YY format.',
        ]) ?>
        <?php if (!empty($errors['expiry_date'])): ?>
            <p class="error">Please enter the test expiry date 12/31.</p>
        <?php endif; ?>

        <?= $this->Form->control('cvv', [
            'label' => 'CVV',
            'required' => true,
            'inputmode' => 'numeric',
            'pattern' => '[0-9]{3}',
            'maxlength' => 3,
            'title' => 'Please enter a valid 3-digit CVV.',
        ]) ?>
        <?php if (!empty($errors['cvv'])): ?>
            <p class="error">Please enter the 3-digit test CVV.</p>
        <?php endif; ?>
        <?= $this->Form->button('Complete Order') ?>
    </div>

    <?= $this->Form->end() ?>
</div>
