<?php
// templates/email/text/supplier_contact.php
?>
Hello <?= $supplierName ?>


You have received a supplier enquiry from Eco Glow Lighting.

Subject:
<?= $subjectText ?>


Message:
<?= $messageText ?>


<?php if (!empty($senderEmail)): ?>
Sender:
<?= $senderEmail ?>


<?php endif; ?>
Kind regards,
Eco Glow Lighting
