-- config/db/patch_20260907_i1_live_modules.sql
-- Additive only. Safe to run on dest after scheme.sql is already loaded.

ALTER TABLE `Quotation`
    ADD COLUMN `Status` VARCHAR(50) NOT NULL DEFAULT 'issued',
    ADD COLUMN `Notes` TEXT NULL,
    ADD COLUMN `EmailedAt` DATETIME NULL;
