<?php
// templates/Orders/receipt.php
?>

<h1>Receipt</h1>

<p>
    Order:
    <?= h((string)$order->OrderId) ?>
</p>

<?php if ($guest): ?>
    <p>
        Guest:
        <?= h($guest->GuestName) ?>
    </p>

    <p>
        Email:
        <?= h($guest->Email) ?>
    </p>

    <p>
        Address:
        <?= h($guest->Address) ?>
    </p>

    <p>
        Phone:
        <?= h($guest->Phone) ?>
    </p>
<?php endif; ?>

<table>
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

<p>
    Products Total:
    $<?= number_format((float)$subtotal, 2) ?>
</p>

<p>
    Installation Fee:
    $<?= number_format((float)$installFee, 2) ?>
</p>

<p>
    Grand Total:
    $<?= number_format((float)$total, 2) ?>
</p>

<p>
    Status:
    <?= h($order->Status) ?>
</p>
