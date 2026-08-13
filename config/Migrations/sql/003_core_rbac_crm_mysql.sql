-- Eco Glow Lighting MySQL 8 migration module generated from 002_core_rbac_crm.sql.
-- PostgreSQL-only constructs were replaced or documented; all identifiers use CakePHP-compatible timestamps.

CREATE TABLE IF NOT EXISTS `businesses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `legal_name` VARCHAR(250),
    `trading_name` VARCHAR(250),
    `abn` VARCHAR(20) UNIQUE,
    `acn` VARCHAR(20),
    `gst_registered` TINYINT(1) NOT NULL DEFAULT 0,
    `default_currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `timezone` VARCHAR(80) NOT NULL DEFAULT 'Australia/Melbourne',
    `locale` VARCHAR(20) NOT NULL DEFAULT 'en-AU',
    `email` VARCHAR(255),
    `phone` VARCHAR(30),
    `website_url` TEXT,
    `address` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `invoice_profile` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `business_locations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `location_type` VARCHAR(30) NOT NULL DEFAULT 'office',
    `email` VARCHAR(255),
    `phone` VARCHAR(30),
    `address` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `timezone` VARCHAR(80) NOT NULL DEFAULT 'Australia/Melbourne',
    `opening_hours` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `supports_pickup` TINYINT(1) NOT NULL DEFAULT 0,
    `supports_service_dispatch` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6),
    UNIQUE (`business_id`, `code`),
    CONSTRAINT `fk_business_locations_business_id_businesses` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `document_sequences` (
    `document_type` VARCHAR(255) PRIMARY KEY,
    `prefix` VARCHAR(20) NOT NULL,
    `next_value` BIGINT NOT NULL DEFAULT 1,
    `padding` SMALLINT NOT NULL DEFAULT 6,
    `include_year` TINYINT(1) NOT NULL DEFAULT 1,
    `reset_annually` TINYINT(1) NOT NULL DEFAULT 0,
    `last_reset_year` INT,
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `feature_flags` (
    `flag_key` VARCHAR(255) PRIMARY KEY,
    `enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `rollout_percentage` SMALLINT NOT NULL DEFAULT 100,
    `rules` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `description` TEXT,
    `updated_by_user_id` INT,
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_feature_flags_updated_by_user_id_users` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `file_assets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `storage_provider` VARCHAR(50) NOT NULL DEFAULT 'local',
    `storage_key` VARCHAR(512) NOT NULL UNIQUE,
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(160) NOT NULL,
    `size_bytes` BIGINT NOT NULL,
    `checksum_sha256` CHAR(64),
    `visibility` VARCHAR(20) NOT NULL DEFAULT 'private',
    `uploaded_by_user_id` INT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6),
    CONSTRAINT `fk_file_assets_uploaded_by_user_id_users` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `entity_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `file_asset_id` BIGINT UNSIGNED NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `purpose` VARCHAR(80) NOT NULL DEFAULT 'attachment',
    `title` VARCHAR(250),
    `sort_order` INT NOT NULL DEFAULT 0,
    `visible_to_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`file_asset_id`, `entity_type`, `entity_id`, `purpose`),
    CONSTRAINT `fk_entity_attachments_file_asset_id_file_assets` FOREIGN KEY (`file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_entity_attachments_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED,
    `role_key` VARCHAR(255) NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_roles_business_id_businesses` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `permission_key` VARCHAR(255) NOT NULL UNIQUE,
    `module` VARCHAR(80) NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `description` TEXT,
    `risk_level` VARCHAR(20) NOT NULL DEFAULT 'normal',
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `granted_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_permissions_role_id_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_role_permissions_permission_id_permissions` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_role_permissions_granted_by_user_id_users` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `business_id` BIGINT UNSIGNED,
    `business_location_id` BIGINT UNSIGNED,
    `granted_by_user_id` INT,
    `starts_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ends_at` DATETIME(6),
    `revoked_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_user_roles_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_role_id_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_business_id_businesses` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_business_location_id_business_locations` FOREIGN KEY (`business_location_id`) REFERENCES `business_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_granted_by_user_id_users` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `user_permission_overrides` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `effect` VARCHAR(10) NOT NULL,
    `business_id` BIGINT UNSIGNED,
    `business_location_id` BIGINT UNSIGNED,
    `reason` TEXT,
    `granted_by_user_id` INT,
    `starts_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ends_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_user_permission_overrides_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permission_overrides_permission_id_permissions` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permission_overrides_business_id_businesses` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permission_overri_business_location_id_b48010a6` FOREIGN KEY (`business_location_id`) REFERENCES `business_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_permission_overrides_granted_by_user_id_users` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `staff_profiles` (
    `user_id` INT PRIMARY KEY,
    `employee_number` VARCHAR(255) UNIQUE,
    `business_location_id` BIGINT UNSIGNED,
    `job_title` VARCHAR(160),
    `department` VARCHAR(120),
    `employment_status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `can_be_assigned_orders` TINYINT(1) NOT NULL DEFAULT 0,
    `can_be_assigned_services` TINYINT(1) NOT NULL DEFAULT 0,
    `supervisor_user_id` INT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_staff_profiles_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_staff_profiles_business_location_id_business_locations` FOREIGN KEY (`business_location_id`) REFERENCES `business_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_staff_profiles_supervisor_user_id_users` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `attempted_email` VARCHAR(255),
    `succeeded` TINYINT(1) NOT NULL,
    `failure_reason` VARCHAR(100),
    `ip_hash` TEXT,
    `user_agent` TEXT,
    `attempted_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_login_attempts_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `approval_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(190) NOT NULL UNIQUE,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED,
    `action_key` VARCHAR(120) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `required_permission_key` VARCHAR(255),
    `required_approvals` SMALLINT NOT NULL DEFAULT 1,
    `requested_by_user_id` INT,
    `assigned_role_id` BIGINT UNSIGNED,
    `request_payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `risk_level` VARCHAR(20) NOT NULL DEFAULT 'normal',
    `expires_at` DATETIME(6),
    `resolved_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_approval_requests_requested_by_user_id_users` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_approval_requests_assigned_role_id_roles` FOREIGN KEY (`assigned_role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `approval_decisions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `approval_request_id` BIGINT UNSIGNED NOT NULL,
    `decided_by_user_id` INT NOT NULL,
    `decision` VARCHAR(20) NOT NULL,
    `note` TEXT,
    `decision_payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`approval_request_id`, `decided_by_user_id`),
    CONSTRAINT `fk_approval_decisions_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_approval_decisions_decided_by_user_id_users` FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `idempotency_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `scope` VARCHAR(100) NOT NULL,
    `idempotency_key` VARCHAR(255) NOT NULL,
    `request_hash` CHAR(64),
    `response_status` INT,
    `response_body` JSON,
    `resource_type` VARCHAR(100),
    `resource_id` BIGINT UNSIGNED,
    `locked_until` DATETIME(6),
    `expires_at` DATETIME(6) NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `completed_at` DATETIME(6),
    UNIQUE (`scope`, `idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `outbox_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `aggregate_type` VARCHAR(100) NOT NULL,
    `aggregate_id` BIGINT UNSIGNED,
    `event_type` VARCHAR(160) NOT NULL,
    `payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `available_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `attempt_count` INT NOT NULL DEFAULT 0,
    `processed_at` DATETIME(6),
    `last_error` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_contacts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `contact_type` VARCHAR(20) NOT NULL,
    `label` VARCHAR(80),
    `value` TEXT NOT NULL,
    `normalized_value` TEXT,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `verified_at` DATETIME(6),
    `can_receive_marketing` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6),
    CONSTRAINT `fk_customer_contacts_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_consents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `consent_type` VARCHAR(80) NOT NULL,
    `status` VARCHAR(20) NOT NULL,
    `channel` VARCHAR(30),
    `source` VARCHAR(80),
    `policy_version` VARCHAR(80),
    `evidence` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `captured_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `withdrawn_at` DATETIME(6),
    `expires_at` DATETIME(6),
    `captured_by_user_id` INT,
    CONSTRAINT `fk_customer_consents_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_consents_captured_by_user_id_users` FOREIGN KEY (`captured_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `tags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `tag_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(120) NOT NULL,
    `description` TEXT,
    `colour_token` VARCHAR(50),
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_tag_assignments` (
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `tag_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`customer_id`, `tag_id`),
    CONSTRAINT `fk_customer_tag_assignments_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_tag_assignments_tag_id_tags` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_tag_assignments_assigned_by_user_id_users` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_interactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `actor_user_id` INT,
    `channel` VARCHAR(30) NOT NULL,
    `direction` VARCHAR(20) NOT NULL,
    `interaction_type` VARCHAR(80) NOT NULL,
    `subject` VARCHAR(250),
    `body` TEXT,
    `contact_message_id` INT,
    `sales_order_id` BIGINT UNSIGNED,
    `external_reference` VARCHAR(255),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `occurred_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_customer_interactions_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_interactions_actor_user_id_users` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_interactions_contact_message_id_contact_messages` FOREIGN KEY (`contact_message_id`) REFERENCES `contact_messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_interactions_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_segments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `segment_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `segment_type` VARCHAR(20) NOT NULL DEFAULT 'manual',
    `rule_definition` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_customer_segments_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_segment_memberships` (
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `customer_segment_id` BIGINT UNSIGNED NOT NULL,
    `membership_source` VARCHAR(20) NOT NULL DEFAULT 'manual',
    `assigned_by_user_id` INT,
    `valid_from` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `valid_until` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    PRIMARY KEY (`customer_id`, `customer_segment_id`),
    CONSTRAINT `fk_customer_segment_memberships_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_segment_membe_customer_segment_id_dedd270e` FOREIGN KEY (`customer_segment_id`) REFERENCES `customer_segments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_segment_memberships_assigned_by_user_id_users` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `customer_id` BIGINT UNSIGNED PRIMARY KEY,
    `order_updates_email` TINYINT(1) NOT NULL DEFAULT 1,
    `order_updates_sms` TINYINT(1) NOT NULL DEFAULT 0,
    `invoice_email` TINYINT(1) NOT NULL DEFAULT 1,
    `invoice_sms` TINYINT(1) NOT NULL DEFAULT 0,
    `marketing_email` TINYINT(1) NOT NULL DEFAULT 0,
    `marketing_sms` TINYINT(1) NOT NULL DEFAULT 0,
    `service_reminders_email` TINYINT(1) NOT NULL DEFAULT 1,
    `service_reminders_sms` TINYINT(1) NOT NULL DEFAULT 0,
    `quiet_hours` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_notification_preferences_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `outbound_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `reference_number` VARCHAR(190) NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED,
    `channel` VARCHAR(20) NOT NULL,
    `recipient` TEXT NOT NULL,
    `template_key` VARCHAR(120),
    `subject` VARCHAR(250),
    `body_text` TEXT,
    `body_html` TEXT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
    `provider` VARCHAR(80),
    `provider_message_id` VARCHAR(255),
    `related_entity_type` VARCHAR(100),
    `related_entity_id` BIGINT UNSIGNED,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `scheduled_at` DATETIME(6),
    `sent_at` DATETIME(6),
    `failed_at` DATETIME(6),
    `failure_reason` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_outbound_messages_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_outbound_messages_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `outbound_message_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `outbound_message_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `provider_event_id` VARCHAR(255),
    `payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `occurred_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_outbound_message_event_outbound_message_id_feb05605` FOREIGN KEY (`outbound_message_id`) REFERENCES `outbound_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `wishlists` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL DEFAULT 'Saved items',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `share_token_hash` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_wishlists_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `wishlist_items` (
    `wishlist_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `customer_note` TEXT,
    `desired_quantity` INT NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`wishlist_id`, `product_variant_id`),
    CONSTRAINT `fk_wishlist_items_wishlist_id_wishlists` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_wishlist_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `loyalty_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `membership_number` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `tier` VARCHAR(50) NOT NULL DEFAULT 'standard',
    `points_balance` BIGINT NOT NULL DEFAULT 0,
    `joined_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `expires_at` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_loyalty_accounts_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `loyalty_account_id` BIGINT UNSIGNED NOT NULL,
    `transaction_type` VARCHAR(30) NOT NULL,
    `points_delta` BIGINT NOT NULL,
    `balance_after` BIGINT NOT NULL,
    `sales_order_id` BIGINT UNSIGNED,
    `reference_type` VARCHAR(80),
    `reference_id` BIGINT UNSIGNED,
    `reason` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_loyalty_transactions_loyalty_account_id_loyalty_accounts` FOREIGN KEY (`loyalty_account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_loyalty_transactions_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_loyalty_transactions_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `contact_message_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_message_id` INT NOT NULL,
    `file_asset_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_by_user_id` INT NULL,
    `visible_to_customer` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contact_message_attachment` (`contact_message_id`, `file_asset_id`),
    CONSTRAINT `fk_contact_attachment_message` FOREIGN KEY (`contact_message_id`) REFERENCES `contact_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_contact_attachment_file` FOREIGN KEY (`file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_contact_attachment_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

ALTER TABLE `customers` ADD COLUMN `customer_type` VARCHAR(20) NOT NULL DEFAULT 'individual', ADD COLUMN `display_name` VARCHAR(250) NULL, ADD COLUMN `external_reference` VARCHAR(120) NULL, ADD COLUMN `preferred_locale` VARCHAR(20) NOT NULL DEFAULT 'en-AU', ADD COLUMN `default_currency` CHAR(3) NOT NULL DEFAULT 'AUD', ADD COLUMN `tax_exempt` TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN `merged_into_customer_id` BIGINT UNSIGNED NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_customers_merged_into` FOREIGN KEY (`merged_into_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `addresses` ADD COLUMN `validation_status` VARCHAR(20) NOT NULL DEFAULT 'unverified', ADD COLUMN `validated_at` DATETIME(6) NULL, ADD COLUMN `delivery_instructions` TEXT NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT());
-- @@STATEMENT_END@@

ALTER TABLE `inventory_locations` ADD COLUMN `business_location_id` BIGINT UNSIGNED NULL, ADD CONSTRAINT `fk_inventory_locations_business_location` FOREIGN KEY (`business_location_id`) REFERENCES `business_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

CREATE INDEX `idx_customer_contact_normalized` ON `customer_contacts` (`contact_type`, `normalized_value`(255));
-- @@STATEMENT_END@@
