<?php
/** @var object $quotation */
/** @var string $customerName */
?>
Hello <?= $customerName ?>,

Your Eco Glow quotation QT-<?= (int)$quotation->QuotationId ?> is ready.

Total (AUD, GST inclusive): $<?= number_format((float)$quotation->TotalAmount, 2) ?>

<?php if (!empty($quotation->Notes)): ?>
Details:
<?= (string)$quotation->Notes ?>
<?php endif; ?>

Thank you,
Eco Glow Lighting
