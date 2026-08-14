<?php
/**
 * Password reset email — HTML part.
 *
 * Styling is inlined because email clients strip <style> blocks.
 *
 * @var \App\View\AppView $this
 * @var string $resetUrl Absolute, single-use password reset link.
 * @var int $expiresInHours Hours until the link stops working.
 */
$this->assign('title', 'Reset your Eco Glow Lighting password');
?>
<div style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1f2233;">
    <h1 style="font-size: 20px; margin: 0 0 16px;">Reset your password</h1>

    <p style="margin: 0 0 16px;">
        We received a request to reset the password for your Eco Glow Lighting
        admin account. Choose a new password using the link below.
    </p>

    <p style="margin: 0 0 24px;">
        <a href="<?= h($resetUrl) ?>"
           style="display: inline-block; padding: 12px 22px; background: #2f6f4f; color: #ffffff;
                  text-decoration: none; border-radius: 6px; font-weight: bold;">
            Reset my password
        </a>
    </p>

    <p style="margin: 0 0 16px;">
        If the button does not work, copy this link into your browser:<br>
        <a href="<?= h($resetUrl) ?>" style="color: #2f6f4f; word-break: break-all;"><?= h($resetUrl) ?></a>
    </p>

    <p style="margin: 0 0 16px;">
        This link stops working in <?= h((string)$expiresInHours) ?> hour<?= $expiresInHours === 1 ? '' : 's' ?> and can only be used once.
    </p>

    <p style="margin: 0 0 8px; color: #5a5f73;">
        If you did not ask for a password reset you can ignore this email —
        your current password stays unchanged.
    </p>

    <p style="margin: 24px 0 0; color: #5a5f73;">Eco Glow Lighting</p>
</div>
