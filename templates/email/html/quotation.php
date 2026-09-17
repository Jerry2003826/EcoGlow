<?php
/** @var object $quotation */
/** @var string $customerName */
?>
<p>Hello <?= h($customerName) ?>,</p>
<p>Your Eco Glow quotation <strong>QT-<?= (int)$quotation->QuotationId ?></strong> is ready.</p>
<p>Total (AUD, GST inclusive): <strong>$<?= number_format((float)$quotation->TotalAmount, 2) ?></strong></p>
<?php if (!empty($quotation->Notes)): ?>
<p>Details:</p>
<pre><?= h((string)$quotation->Notes) ?></pre>
<?php endif; ?>
<p>Thank you,<br>Eco Glow Lighting</p>
