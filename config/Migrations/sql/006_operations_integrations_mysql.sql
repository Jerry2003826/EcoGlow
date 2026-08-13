-- Eco Glow Lighting MySQL 8 migration module generated from 005_operations_integrations.sql.
-- PostgreSQL-only constructs were replaced or documented; all identifiers use CakePHP-compatible timestamps.

CREATE TABLE IF NOT EXISTS `stock_reservations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_item_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `reservation_movement_id` BIGINT UNSIGNED,
    `release_or_sale_movement_id` BIGINT UNSIGNED,
    `expires_at` DATETIME(6),
    `released_at` DATETIME(6),
    `consumed_at` DATETIME(6),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_stock_reservations_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_inventory_location_i_5008ecc3` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_reservation_movement_225afebe` FOREIGN KEY (`reservation_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_release_or_sale_move_a816f2a7` FOREIGN KEY (`release_or_sale_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_reservations_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `stock_counts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `count_number` VARCHAR(190) NOT NULL UNIQUE,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `count_type` VARCHAR(30) NOT NULL DEFAULT 'cycle',
    `scope_definition` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `scheduled_at` DATETIME(6),
    `started_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `created_by_user_id` INT,
    `approved_by_user_id` INT,
    `approval_request_id` BIGINT UNSIGNED,
    `notes` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_stock_counts_inventory_location_id_inventory_locations` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_counts_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_counts_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_counts_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `stock_count_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_count_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `expected_quantity` INT NOT NULL,
    `counted_quantity` INT,
    `variance_quantity` INT GENERATED ALWAYS AS ( CASE WHEN counted_quantity IS NULL THEN NULL ELSE counted_quantity - expected_quantity END ) STORED,
    `reason_code` VARCHAR(80),
    `note` TEXT,
    `inventory_movement_id` BIGINT UNSIGNED,
    `counted_by_user_id` INT,
    `counted_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`stock_count_id`, `product_variant_id`),
    CONSTRAINT `fk_stock_count_items_stock_count_id_stock_counts` FOREIGN KEY (`stock_count_id`) REFERENCES `stock_counts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_count_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_count_items_inventory_movement_i_e28aafac` FOREIGN KEY (`inventory_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_count_items_counted_by_user_id_users` FOREIGN KEY (`counted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `stock_transfers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `transfer_number` VARCHAR(190) NOT NULL UNIQUE,
    `from_inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `to_inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `requested_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `dispatched_at` DATETIME(6),
    `received_at` DATETIME(6),
    `requested_by_user_id` INT,
    `approved_by_user_id` INT,
    `received_by_user_id` INT,
    `approval_request_id` BIGINT UNSIGNED,
    `notes` TEXT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_stock_transfers_from_inventory_locat_233550db` FOREIGN KEY (`from_inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfers_to_inventory_locatio_677aa5b9` FOREIGN KEY (`to_inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfers_requested_by_user_id_users` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfers_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfers_received_by_user_id_users` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfers_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_transfer_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `quantity_requested` INT NOT NULL,
    `quantity_dispatched` INT NOT NULL DEFAULT 0,
    `quantity_received` INT NOT NULL DEFAULT 0,
    `transfer_out_movement_id` BIGINT UNSIGNED,
    `transfer_in_movement_id` BIGINT UNSIGNED,
    `note` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`stock_transfer_id`, `product_variant_id`),
    CONSTRAINT `fk_stock_transfer_items_stock_transfer_id_stock_transfers` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfer_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfer_items_transfer_out_movemen_19e57aaf` FOREIGN KEY (`transfer_out_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_transfer_items_transfer_in_movement_87d43151` FOREIGN KEY (`transfer_in_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `reorder_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `preferred_supplier_id` BIGINT UNSIGNED,
    `calculation_method` VARCHAR(30) NOT NULL DEFAULT 'min_max',
    `reorder_point` INT NOT NULL DEFAULT 0,
    `reorder_quantity` INT NOT NULL DEFAULT 0,
    `minimum_stock` INT NOT NULL DEFAULT 0,
    `maximum_stock` INT,
    `safety_stock` INT NOT NULL DEFAULT 0,
    `lead_time_days` INT,
    `review_period_days` INT,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`product_variant_id`, `inventory_location_id`),
    CONSTRAINT `fk_reorder_rules_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reorder_rules_inventory_location_id_inventory_locations` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reorder_rules_preferred_supplier_id_suppliers` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `purchase_order_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `purchase_order_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(40),
    `to_status` VARCHAR(40) NOT NULL,
    `changed_by_user_id` INT,
    `note` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_purchase_order_status__purchase_order_id_7e8706db` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_order_status_history_changed_by_user_id_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `supplier_invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `supplier_invoice_number` VARCHAR(190) NOT NULL UNIQUE,
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    `purchase_order_id` BIGINT UNSIGNED,
    `supplier_reference` VARCHAR(160),
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `invoice_date` DATE,
    `due_date` DATE,
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `total_cents` BIGINT NOT NULL DEFAULT 0,
    `amount_paid_cents` BIGINT NOT NULL DEFAULT 0,
    `external_accounting_reference` VARCHAR(255),
    `file_asset_id` BIGINT UNSIGNED,
    `approved_by_user_id` INT,
    `approval_request_id` BIGINT UNSIGNED,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_supplier_invoices_supplier_id_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoices_purchase_order_id_purchase_orders` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoices_file_asset_id_file_assets` FOREIGN KEY (`file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoices_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoices_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `supplier_invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `supplier_invoice_id` BIGINT UNSIGNED NOT NULL,
    `purchase_order_item_id` BIGINT UNSIGNED,
    `goods_receipt_item_id` BIGINT UNSIGNED,
    `line_number` INT NOT NULL,
    `description_snapshot` TEXT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_cost_cents` BIGINT NOT NULL,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`supplier_invoice_id`, `line_number`),
    CONSTRAINT `fk_supplier_invoice_items_supplier_invoice_id_d1cfbcca` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoice_items_purchase_order_item__b8d6aea3` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_invoice_items_goods_receipt_item_i_dc6d2324` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_request_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `item_type` VARCHAR(20) NOT NULL,
    `product_variant_id` BIGINT UNSIGNED,
    `service_type_id` BIGINT UNSIGNED,
    `sales_order_item_id` BIGINT UNSIGNED,
    `description_snapshot` TEXT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `estimated_unit_price_cents` BIGINT,
    `final_unit_price_cents` BIGINT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_request_items_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_request_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_request_items_service_type_id_service_types` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_request_items_sales_order_item_id_d7742ed3` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `site_surveys` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `appointment_id` BIGINT UNSIGNED,
    `performed_by_user_id` INT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    `site_conditions` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `measurements` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `electrical_notes` TEXT,
    `access_notes` TEXT,
    `hazards` TEXT,
    `recommendations` TEXT,
    `customer_acknowledged_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_site_surveys_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_site_surveys_appointment_id_service_appointments` FOREIGN KEY (`appointment_id`) REFERENCES `service_appointments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_site_surveys_performed_by_user_id_users` FOREIGN KEY (`performed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `staff_availability` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `staff_user_id` INT NOT NULL,
    `availability_type` VARCHAR(20) NOT NULL DEFAULT 'available',
    `starts_at` DATETIME(6) NOT NULL,
    `ends_at` DATETIME(6) NOT NULL,
    `business_location_id` BIGINT UNSIGNED,
    `recurrence_rule` TEXT,
    `note` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_staff_availability_staff_user_id_users` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_staff_availability_business_location_id_8b819afb` FOREIGN KEY (`business_location_id`) REFERENCES `business_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_staff_availability_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_work_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `appointment_id` BIGINT UNSIGNED,
    `staff_user_id` INT NOT NULL,
    `started_at` DATETIME(6) NOT NULL,
    `ended_at` DATETIME(6),
    `duration_minutes` INT,
    `work_summary` TEXT NOT NULL,
    `customer_visible_summary` TEXT,
    `billable` TINYINT(1) NOT NULL DEFAULT 1,
    `labour_cost_cents` BIGINT,
    `labour_charge_cents` BIGINT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_work_logs_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_work_logs_appointment_id_service_appointments` FOREIGN KEY (`appointment_id`) REFERENCES `service_appointments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_work_logs_staff_user_id_users` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_parts_used` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `service_work_log_id` BIGINT UNSIGNED,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED,
    `quantity` INT NOT NULL,
    `unit_cost_snapshot_cents` BIGINT,
    `unit_charge_snapshot_cents` BIGINT,
    `inventory_movement_id` BIGINT UNSIGNED,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_parts_used_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_parts_used_service_work_log_id_service_work_logs` FOREIGN KEY (`service_work_log_id`) REFERENCES `service_work_logs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_parts_used_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_service_parts_used_inventory_location_i_983644a1` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_parts_used_inventory_movement_i_95f8b07c` FOREIGN KEY (`inventory_movement_id`) REFERENCES `inventory_movements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_parts_used_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `external_integrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `integration_key` VARCHAR(255) NOT NULL UNIQUE,
    `integration_type` VARCHAR(50) NOT NULL,
    `provider` VARCHAR(100) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'disabled',
    `configuration` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `secret_reference` TEXT,
    `last_connected_at` DATETIME(6),
    `last_success_at` DATETIME(6),
    `last_error_at` DATETIME(6),
    `last_error` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_external_integrations_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `integration_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `external_integration_id` BIGINT UNSIGNED,
    `direction` VARCHAR(20) NOT NULL,
    `event_type` VARCHAR(160) NOT NULL,
    `external_event_id` VARCHAR(255),
    `idempotency_key` VARCHAR(255),
    `related_entity_type` VARCHAR(100),
    `related_entity_id` BIGINT UNSIGNED,
    `status` VARCHAR(20) NOT NULL DEFAULT 'received',
    `request_payload` JSON,
    `response_payload` JSON,
    `attempt_count` INT NOT NULL DEFAULT 0,
    `next_attempt_at` DATETIME(6),
    `processed_at` DATETIME(6),
    `last_error` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`external_integration_id`, `external_event_id`),
    CONSTRAINT `fk_integration_events_external_integration_7fc6597a` FOREIGN KEY (`external_integration_id`) REFERENCES `external_integrations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `accounting_exports` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `external_integration_id` BIGINT UNSIGNED,
    `export_type` VARCHAR(50) NOT NULL,
    `period_start` DATE,
    `period_end` DATE,
    `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
    `file_asset_id` BIGINT UNSIGNED,
    `record_count` INT NOT NULL DEFAULT 0,
    `exported_by_user_id` INT,
    `external_batch_reference` VARCHAR(255),
    `error_summary` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `completed_at` DATETIME(6),
    CONSTRAINT `fk_accounting_exports_external_integration_e75ef6b9` FOREIGN KEY (`external_integration_id`) REFERENCES `external_integrations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_accounting_exports_file_asset_id_file_assets` FOREIGN KEY (`file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_accounting_exports_exported_by_user_id_users` FOREIGN KEY (`exported_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `import_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `import_number` VARCHAR(190) NOT NULL UNIQUE,
    `import_type` VARCHAR(50) NOT NULL,
    `source_channel` VARCHAR(30) NOT NULL DEFAULT 'file',
    `status` VARCHAR(20) NOT NULL DEFAULT 'uploaded',
    `file_asset_id` BIGINT UNSIGNED,
    `options` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `total_rows` INT NOT NULL DEFAULT 0,
    `valid_rows` INT NOT NULL DEFAULT 0,
    `invalid_rows` INT NOT NULL DEFAULT 0,
    `imported_rows` INT NOT NULL DEFAULT 0,
    `started_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_import_jobs_file_asset_id_file_assets` FOREIGN KEY (`file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_import_jobs_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `import_job_rows` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `import_job_id` BIGINT UNSIGNED NOT NULL,
    `row_number` INT NOT NULL,
    `raw_data` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `normalized_data` JSON,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `errors` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `warnings` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `imported_entity_type` VARCHAR(100),
    `imported_entity_id` BIGINT UNSIGNED,
    `processed_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`import_job_id`, `row_number`),
    CONSTRAINT `fk_import_job_rows_import_job_id_import_jobs` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `data_retention_policies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `policy_key` VARCHAR(255) NOT NULL UNIQUE,
    `entity_type` VARCHAR(100) NOT NULL,
    `retention_days` INT,
    `action` VARCHAR(30) NOT NULL DEFAULT 'review',
    `criteria` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `legal_basis` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `privacy_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(190) NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED,
    `requester_email` VARCHAR(255),
    `request_type` VARCHAR(30) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'received',
    `verification_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `request_details` TEXT,
    `due_at` DATETIME(6),
    `assigned_to_user_id` INT,
    `completed_at` DATETIME(6),
    `response_file_asset_id` BIGINT UNSIGNED,
    `internal_notes` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_privacy_requests_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_privacy_requests_assigned_to_user_id_users` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_privacy_requests_response_file_asset_id_file_assets` FOREIGN KEY (`response_file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_configurations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `configuration_key` VARCHAR(255) NOT NULL UNIQUE,
    `purpose` VARCHAR(80) NOT NULL,
    `provider` VARCHAR(100),
    `model_name` VARCHAR(160),
    `status` VARCHAR(20) NOT NULL DEFAULT 'disabled',
    `system_prompt_version` VARCHAR(80),
    `parameters` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `tool_allowlist` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `data_policy` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `requires_human_approval` TINYINT(1) NOT NULL DEFAULT 1,
    `secret_reference` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_configurations_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_feedback` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ai_conversation_id` BIGINT UNSIGNED,
    `ai_message_id` BIGINT UNSIGNED,
    `ai_product_recommendation_id` BIGINT UNSIGNED,
    `submitted_by_user_id` INT,
    `customer_id` BIGINT UNSIGNED,
    `rating` SMALLINT,
    `feedback_type` VARCHAR(30),
    `comment` TEXT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_feedback_ai_conversation_id_ai_conversations` FOREIGN KEY (`ai_conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_feedback_ai_message_id_ai_messages` FOREIGN KEY (`ai_message_id`) REFERENCES `ai_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_feedback_ai_product_recommend_2a28c26f` FOREIGN KEY (`ai_product_recommendation_id`) REFERENCES `ai_product_recommendations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_feedback_submitted_by_user_id_users` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_feedback_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ai_configuration_id` BIGINT UNSIGNED,
    `ai_conversation_id` BIGINT UNSIGNED,
    `ai_message_id` BIGINT UNSIGNED,
    `provider` VARCHAR(100),
    `model_name` VARCHAR(160),
    `request_type` VARCHAR(80),
    `input_tokens` INT NOT NULL DEFAULT 0,
    `output_tokens` INT NOT NULL DEFAULT 0,
    `cached_input_tokens` INT NOT NULL DEFAULT 0,
    `latency_ms` INT,
    `estimated_cost_micros` BIGINT NOT NULL DEFAULT 0,
    `succeeded` TINYINT(1) NOT NULL DEFAULT 1,
    `error_code` VARCHAR(120),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_usage_logs_ai_configuration_id_ai_configurations` FOREIGN KEY (`ai_configuration_id`) REFERENCES `ai_configurations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_usage_logs_ai_conversation_id_ai_conversations` FOREIGN KEY (`ai_conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_usage_logs_ai_message_id_ai_messages` FOREIGN KEY (`ai_message_id`) REFERENCES `ai_messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_appointment_staff` (
    `service_appointment_id` BIGINT UNSIGNED NOT NULL,
    `user_id` INT NOT NULL,
    `assignment_role` VARCHAR(50) NOT NULL DEFAULT 'technician',
    `is_lead` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`service_appointment_id`, `user_id`),
    CONSTRAINT `fk_service_appt_staff_appt` FOREIGN KEY (`service_appointment_id`) REFERENCES `service_appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_appt_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(40) NULL,
    `to_status` VARCHAR(40) NOT NULL,
    `changed_by_user_id` INT NULL,
    `note` TEXT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    KEY `idx_service_status_history` (`service_request_id`, `created`),
    CONSTRAINT `fk_service_status_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_status_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `payment_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(80) NOT NULL,
    `provider_event_id` VARCHAR(255) NULL,
    `payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `occurred_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_provider_event` (`provider_event_id`),
    KEY `idx_payment_events_payment` (`payment_id`, `occurred_at`),
    CONSTRAINT `fk_payment_events_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `delivery_bookings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `shipment_id` BIGINT UNSIGNED NULL,
    `scheduled_date` DATE NOT NULL,
    `window_start` TIME NULL,
    `window_end` TIME NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'tentative',
    `instructions` TEXT NULL,
    `confirmed_by_user_id` INT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    KEY `idx_delivery_bookings_date_status` (`scheduled_date`, `status`),
    CONSTRAINT `fk_delivery_booking_order` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_delivery_booking_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_delivery_booking_user` FOREIGN KEY (`confirmed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@
