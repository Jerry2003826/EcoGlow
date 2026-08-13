<?php
/**
 * Invoice email — HTML part.
 *
 * @var \App\View\AppView $this
 * @var string $bodyText Invoice summary.
 * @var string $subject Email subject.
 * @var string $invoiceNumber Invoice number.
 */
$this->assign('title', $subject);
?>
<div style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #2F2E2C;">
    <h1 style="font-size: 20px; margin: 0 0 16px;">Invoice <?= h($invoiceNumber !== '' ? $invoiceNumber : '') ?></h1>

    <p style="margin: 0 0 16px; white-space: pre-wrap;"><?= h($bodyText) ?></p>

    <p style="margin: 24px 0 0; color: #5B5545;">Eco Glow Lighting</p>
</div>
