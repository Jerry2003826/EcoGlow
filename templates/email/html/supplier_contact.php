<?php
// templates/email/html/supplier_contact.php
?>
<p>Hello <?= h($supplierName) ?>,</p>

<p>You have received a supplier enquiry from Eco Glow Lighting.</p>

<p>
    <strong>Subject:</strong>
    <?= h($subjectText) ?>
</p>

<p>
    <strong>Message:</strong>
</p>

<p><?= nl2br(h($messageText)) ?></p>

<?php if (!empty($senderEmail)): ?>
<p>
    <strong>Sender:</strong>
    <?= h($senderEmail) ?>
</p>
<?php endif; ?>

<p>Kind regards,<br>Eco Glow Lighting</p>
