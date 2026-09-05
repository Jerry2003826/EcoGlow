<?php

use App\Service\Money;

/** @var object $quotation */
/** @var string $customerName */
/** @var string $itemsSummary */
?>
Hello <?= $customerName ?>


Your quotation has been issued.

Quotation Number: <?= $quotation->quotation_number . PHP_EOL ?>

Items:
<?= $itemsSummary . PHP_EOL ?>


Subtotal: <?= Money::formatAud((int)$quotation->subtotal_cents) . PHP_EOL ?>
GST: <?= Money::formatAud((int)$quotation->tax_cents) . PHP_EOL ?>
Total: <?= Money::formatAud((int)$quotation->grand_total_cents) . PHP_EOL ?>


Thank you,
Eco Glow
