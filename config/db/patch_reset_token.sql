-- config/db/patch_reset_token.sql
ALTER TABLE `User`
    ADD COLUMN `ResetToken` VARCHAR(64) NULL,
    ADD COLUMN `ResetTokenExpires` DATETIME NULL;
