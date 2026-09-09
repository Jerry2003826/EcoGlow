<?php
// templates/Orders/receipt.php
?>

<div class="receipt-card">
    <h1>Receipt</h1>

    <p><strong>OrderId:</strong> <?= h((string)$order->OrderId) ?></p>
    <p><strong>Status:</strong> <?= h((string)$order->Status) ?></p>

    <h2>Buyer details</h2>

    <?php if ($customer): ?>
        <p><strong>Name:</strong> <?= h($customer->CustomerName) ?></p>
        <p><strong>Email:</strong> <?= h($customer->Email) ?></p>
        <p><strong>Address:</strong> <?= h($customer->Address) ?></p>
        <p><strong>Phone:</strong> <?= h($customer->Phone) ?></p>
    <?php elseif ($guest): ?>
        <p><strong>Name:</strong> <?= h($guest->GuestName) ?></p>
        <p><strong>Email:</strong> <?= h($guest->Email) ?></p>
        <p><strong>Address:</strong> <?= h($guest->Address) ?></p>
        <p><strong>Phone:</strong> <?= h($guest->Phone) ?></p>
    <?php endif; ?>

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
                <td>$<?= number_format((float)$item->UnitPrice, 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="receipt-summary">
        <p>Products Total: $<?= number_format((float)$productsTotal, 2) ?></p>
        <p>Installation Fee: $<?= number_format((float)$installFee, 2) ?></p>
        <p>Grand Total: $<?= number_format((float)$grandTotal, 2) ?></p>
    </div>

    <?= $this->Html->link(
        'Continue to the landing page',
        '/',
        ['class' => 'button']
    ) ?>
</div>
