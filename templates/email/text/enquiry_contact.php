<?php
// templates/email/text/enquiry_contact.php
?>
Hello <?= $enquiryName ?>


You have received a reply from Eco Glow Lighting.

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
