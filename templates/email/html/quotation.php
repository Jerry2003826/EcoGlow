<?php

use App\Service\Money;

/** @var object $quotation */
/** @var string $customerName */
/** @var string $itemsSummary */
?>
<p>Hello <?= h($customerName) ?>,</p>

<p>Your quotation has been issued.</p>

<p>
    <strong>Quotation Number:</strong>
    <?= h($quotation->quotation_number) ?>
</p>

<h3>Items</h3>

<pre><?= h($itemsSummary) ?></pre>

<p>
    <strong>Subtotal:</strong>
    <?= h(Money::formatAud((int)$quotation->subtotal_cents)) ?><br>
    <strong>GST:</strong>
    <?= h(Money::formatAud((int)$quotation->tax_cents)) ?><br>
    <strong>Total:</strong>
    <?= h(Money::formatAud((int)$quotation->grand_total_cents)) ?>
</p>

<p>Thank you,<br>Eco Glow</p>
