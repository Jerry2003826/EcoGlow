<?php
/**
 * Password reset email — plain-text part.
 *
 * @var \App\View\AppView $this
 * @var string $resetUrl Absolute, single-use password reset link.
 * @var int $expiresInHours Hours until the link stops working.
 */
?>
Reset your password
===================

We received a request to reset the password for your Eco Glow Lighting admin
account. Open the link below to choose a new password:

<?= $resetUrl ?>


This link stops working in <?= $expiresInHours ?> hour<?= $expiresInHours === 1 ? '' : 's' ?> and can only be used once.

If you did not ask for a password reset you can ignore this email — your
current password stays unchanged.

Eco Glow Lighting
