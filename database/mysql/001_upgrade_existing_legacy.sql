-- Additive upgrade for the exact existing users/contact_messages schema documented by the project.

ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `password_reset_expires`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(32) NULL AFTER `last_name`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `role` VARCHAR(30) NULL DEFAULT NULL AFTER `phone`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'active' AFTER `role`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME(6) NULL AFTER `status`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `last_login_at` DATETIME(6) NULL AFTER `email_verified_at`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `failed_login_count` INT NOT NULL DEFAULT 0 AFTER `last_login_at`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `locked_until` DATETIME(6) NULL AFTER `failed_login_count`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `mfa_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `locked_until`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `mfa_secret_reference` VARCHAR(255) NULL AFTER `mfa_enabled`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `mfa_enrolled_at` DATETIME(6) NULL AFTER `mfa_secret_reference`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `last_password_change_at` DATETIME(6) NULL AFTER `mfa_enrolled_at`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()) AFTER `last_password_change_at`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `preferred_locale` VARCHAR(20) NOT NULL DEFAULT 'en-AU' AFTER `mfa_secret_reference`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `timezone` VARCHAR(80) NOT NULL DEFAULT 'Australia/Melbourne' AFTER `preferred_locale`;
-- @@STATEMENT_END@@

ALTER TABLE `users` ADD COLUMN `deleted` DATETIME(6) NULL AFTER `modified`;
-- @@STATEMENT_END@@

UPDATE `users` SET `role` = 'admin' WHERE `role` IS NULL;
-- @@STATEMENT_END@@

ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(30) NOT NULL DEFAULT 'customer';
-- @@STATEMENT_END@@

CREATE INDEX `idx_users_role_status` ON `users` (`role`, `status`);
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `reference_number` VARCHAR(40) NULL AFTER `id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `customer_id` BIGINT UNSIGNED NULL AFTER `reference_number`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `user_id` INT NULL AFTER `customer_id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `source` VARCHAR(30) NOT NULL DEFAULT 'web_form' AFTER `user_id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'new' AFTER `source`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `inquiry_type` VARCHAR(50) NULL AFTER `status`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `product_id` BIGINT UNSIGNED NULL AFTER `inquiry_type`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `service_type_id` BIGINT UNSIGNED NULL AFTER `product_id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `preferred_reply_channel` VARCHAR(20) NULL AFTER `service_type_id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `consent_to_contact` TINYINT(1) NOT NULL DEFAULT 1 AFTER `preferred_reply_channel`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `captcha_provider` VARCHAR(50) NULL AFTER `consent_to_contact`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `captcha_verified_at` DATETIME(6) NULL AFTER `captcha_provider`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `ip_hash` CHAR(64) NULL AFTER `captcha_verified_at`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `user_agent` VARCHAR(512) NULL AFTER `ip_hash`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `assigned_to_user_id` INT NULL AFTER `user_agent`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `last_response_at` DATETIME(6) NULL AFTER `assigned_to_user_id`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `resolved_at` DATETIME(6) NULL AFTER `last_response_at`;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD COLUMN `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) AFTER `created`;
-- @@STATEMENT_END@@

UPDATE `contact_messages` SET `reference_number` = CONCAT('MSG-', LPAD(`id`, 8, '0')), `status` = CASE WHEN `is_read` = 1 THEN 'resolved' ELSE 'new' END, `resolved_at` = CASE WHEN `is_read` = 1 THEN COALESCE(`resolved_at`, `created`) ELSE `resolved_at` END WHERE `reference_number` IS NULL;
-- @@STATEMENT_END@@

CREATE UNIQUE INDEX `uq_contact_messages_reference` ON `contact_messages` (`reference_number`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_contact_messages_status_created` ON `contact_messages` (`status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_contact_messages_assigned_status` ON `contact_messages` (`assigned_to_user_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_contact_messages_email` ON `contact_messages` (`email`);
-- @@STATEMENT_END@@
