-- Eco Glow Lighting MySQL 8 migration module generated from 004_commerce_documents.sql.
-- PostgreSQL-only constructs were replaced or documented; all identifiers use CakePHP-compatible timestamps.

CREATE TABLE IF NOT EXISTS `quotations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `quotation_number` VARCHAR(190) NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED,
    `service_request_id` BIGINT UNSIGNED,
    `source_channel` VARCHAR(30) NOT NULL DEFAULT 'admin',
    `external_reference` VARCHAR(160),
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `current_version_number` INT NOT NULL DEFAULT 1,
    `valid_until` DATETIME(6),
    `approval_request_id` BIGINT UNSIGNED,
    `converted_order_id` BIGINT UNSIGNED,
    `accepted_at` DATETIME(6),
    `rejected_at` DATETIME(6),
    `expired_at` DATETIME(6),
    `created_by_user_id` INT,
    `assigned_to_user_id` INT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6),
    CONSTRAINT `fk_quotations_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_converted_order_id_sales_orders` FOREIGN KEY (`converted_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_assigned_to_user_id_users` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `quotation_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `quotation_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT NOT NULL,
    `version_status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `shipping_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `grand_total_cents` BIGINT NOT NULL DEFAULT 0,
    `customer_message` TEXT,
    `internal_notes` TEXT,
    `terms_and_conditions` TEXT,
    `valid_until` DATETIME(6),
    `pdf_file_asset_id` BIGINT UNSIGNED,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`quotation_id`, `version_number`),
    CONSTRAINT `fk_quotation_versions_quotation_id_quotations` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_versions_pdf_file_asset_id_file_assets` FOREIGN KEY (`pdf_file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_versions_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `quotation_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `quotation_version_id` BIGINT UNSIGNED NOT NULL,
    `line_number` INT NOT NULL,
    `item_type` VARCHAR(20) NOT NULL,
    `product_id` BIGINT UNSIGNED,
    `product_variant_id` BIGINT UNSIGNED,
    `service_type_id` BIGINT UNSIGNED,
    `sku_snapshot` VARCHAR(120),
    `item_name_snapshot` VARCHAR(250) NOT NULL,
    `variant_name_snapshot` VARCHAR(200),
    `description_snapshot` TEXT,
    `quantity` INT NOT NULL,
    `unit_price_cents` BIGINT NOT NULL,
    `cost_snapshot_cents` BIGINT,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_rate_snapshot` DECIMAL(7,6) NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`quotation_version_id`, `line_number`),
    CONSTRAINT `fk_quotation_items_quotation_version_id_quotation_versions` FOREIGN KEY (`quotation_version_id`) REFERENCES `quotation_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_items_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_items_service_type_id_service_types` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `quotation_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `quotation_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(30),
    `to_status` VARCHAR(30) NOT NULL,
    `changed_by_user_id` INT,
    `note` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_quotation_status_history_quotation_id_quotations` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_status_history_changed_by_user_id_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(190) NOT NULL UNIQUE,
    `invoice_type` VARCHAR(20) NOT NULL DEFAULT 'invoice',
    `sales_order_id` BIGINT UNSIGNED,
    `quotation_id` BIGINT UNSIGNED,
    `customer_id` BIGINT UNSIGNED,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `issue_date` DATE,
    `due_date` DATE,
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `shipping_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `grand_total_cents` BIGINT NOT NULL DEFAULT 0,
    `amount_paid_cents` BIGINT NOT NULL DEFAULT 0,
    `credit_applied_cents` BIGINT NOT NULL DEFAULT 0,
    `balance_due_cents` BIGINT GENERATED ALWAYS AS ( grand_total_cents - amount_paid_cents - credit_applied_cents ) STORED,
    `business_snapshot` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `customer_snapshot` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `billing_address_snapshot` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `delivery_channel` VARCHAR(20),
    `pdf_file_asset_id` BIGINT UNSIGNED,
    `external_accounting_reference` VARCHAR(255),
    `issued_by_user_id` INT,
    `approved_by_user_id` INT,
    `issued_at` DATETIME(6),
    `paid_at` DATETIME(6),
    `voided_at` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_invoices_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoices_quotation_id_quotations` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoices_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoices_pdf_file_asset_id_file_assets` FOREIGN KEY (`pdf_file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoices_issued_by_user_id_users` FOREIGN KEY (`issued_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoices_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_item_id` BIGINT UNSIGNED,
    `quotation_item_id` BIGINT UNSIGNED,
    `line_number` INT NOT NULL,
    `item_type` VARCHAR(20) NOT NULL DEFAULT 'product',
    `sku_snapshot` VARCHAR(120),
    `item_name_snapshot` VARCHAR(250) NOT NULL,
    `description_snapshot` TEXT,
    `category_snapshot` VARCHAR(160),
    `quantity` INT NOT NULL,
    `unit_price_cents` BIGINT NOT NULL,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_rate_snapshot` DECIMAL(7,6) NOT NULL DEFAULT 0,
    `tax_name_snapshot` VARCHAR(80),
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`invoice_id`, `line_number`),
    CONSTRAINT `fk_invoice_items_invoice_id_invoices` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_invoice_items_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_invoice_items_quotation_item_id_quotation_items` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `invoice_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(30),
    `to_status` VARCHAR(30) NOT NULL,
    `changed_by_user_id` INT,
    `note` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_invoice_status_history_invoice_id_invoices` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_invoice_status_history_changed_by_user_id_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `credit_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `credit_note_number` VARCHAR(190) NOT NULL UNIQUE,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_id` BIGINT UNSIGNED,
    `customer_id` BIGINT UNSIGNED,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `reason_code` VARCHAR(80),
    `reason` TEXT,
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `total_cents` BIGINT NOT NULL DEFAULT 0,
    `applied_cents` BIGINT NOT NULL DEFAULT 0,
    `issue_date` DATE,
    `issued_at` DATETIME(6),
    `pdf_file_asset_id` BIGINT UNSIGNED,
    `created_by_user_id` INT,
    `approved_by_user_id` INT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_credit_notes_invoice_id_invoices` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_notes_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_notes_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_notes_pdf_file_asset_id_file_assets` FOREIGN KEY (`pdf_file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_notes_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_notes_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `credit_note_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `credit_note_id` BIGINT UNSIGNED NOT NULL,
    `invoice_item_id` BIGINT UNSIGNED,
    `line_number` INT NOT NULL,
    `description_snapshot` TEXT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_amount_cents` BIGINT NOT NULL,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`credit_note_id`, `line_number`),
    CONSTRAINT `fk_credit_note_items_credit_note_id_credit_notes` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_credit_note_items_invoice_item_id_invoice_items` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `payment_allocations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `amount_cents` BIGINT NOT NULL,
    `allocated_by_user_id` INT,
    `allocated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `reversed_at` DATETIME(6),
    `reversal_reason` TEXT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_payment_allocations_payment_id_payments` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_allocations_invoice_id_invoices` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_allocations_allocated_by_user_id_users` FOREIGN KEY (`allocated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `order_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `author_user_id` INT,
    `note_type` VARCHAR(30) NOT NULL DEFAULT 'internal',
    `body` TEXT NOT NULL,
    `visible_to_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_order_notes_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_notes_author_user_id_users` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `order_adjustments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_item_id` BIGINT UNSIGNED,
    `adjustment_type` VARCHAR(30) NOT NULL,
    `source_type` VARCHAR(50),
    `source_id` BIGINT UNSIGNED,
    `description` VARCHAR(250) NOT NULL,
    `amount_cents` BIGINT NOT NULL,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_order_adjustments_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_adjustments_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_adjustments_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `sales_returns` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `return_number` VARCHAR(190) NOT NULL UNIQUE,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED,
    `status` VARCHAR(30) NOT NULL DEFAULT 'requested',
    `return_type` VARCHAR(30) NOT NULL DEFAULT 'return',
    `requested_resolution` VARCHAR(30),
    `reason_code` VARCHAR(80),
    `customer_reason` TEXT,
    `internal_notes` TEXT,
    `refund_total_cents` BIGINT NOT NULL DEFAULT 0,
    `restocking_fee_cents` BIGINT NOT NULL DEFAULT 0,
    `requested_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `approved_at` DATETIME(6),
    `received_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `approved_by_user_id` INT,
    `processed_by_user_id` INT,
    `approval_request_id` BIGINT UNSIGNED,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_sales_returns_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_returns_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_returns_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_returns_processed_by_user_id_users` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_returns_approval_request_id_approval_requests` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `sales_return_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_return_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_item_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED,
    `quantity_requested` INT NOT NULL,
    `quantity_received` INT NOT NULL DEFAULT 0,
    `condition_code` VARCHAR(30),
    `restock` TINYINT(1) NOT NULL DEFAULT 0,
    `refund_amount_cents` BIGINT NOT NULL DEFAULT 0,
    `inspection_notes` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`sales_return_id`, `sales_order_item_id`),
    CONSTRAINT `fk_sales_return_items_sales_return_id_sales_returns` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_return_items_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_return_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipping_zones` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `zone_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `country_code` CHAR(2) NOT NULL DEFAULT 'AU',
    `priority` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipping_zone_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shipping_zone_id` BIGINT UNSIGNED NOT NULL,
    `state_code` VARCHAR(20),
    `postcode_from` VARCHAR(20),
    `postcode_to` VARCHAR(20),
    `postcode_pattern` VARCHAR(120),
    `suburb_pattern` VARCHAR(160),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_shipping_zone_rules_shipping_zone_id_shipping_zones` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipping_methods` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `method_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `method_type` VARCHAR(30) NOT NULL DEFAULT 'carrier',
    `carrier` VARCHAR(120),
    `estimated_min_days` INT,
    `estimated_max_days` INT,
    `supports_tracking` TINYINT(1) NOT NULL DEFAULT 1,
    `supports_bulk_orders` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipping_rates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shipping_zone_id` BIGINT UNSIGNED NOT NULL,
    `shipping_method_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `base_amount_cents` BIGINT NOT NULL DEFAULT 0,
    `per_kg_amount_cents` BIGINT NOT NULL DEFAULT 0,
    `minimum_order_cents` BIGINT,
    `maximum_order_cents` BIGINT,
    `free_above_cents` BIGINT,
    `minimum_weight_grams` INT,
    `maximum_weight_grams` INT,
    `conditions` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `valid_from` DATETIME(6),
    `valid_until` DATETIME(6),
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_shipping_rates_shipping_zone_id_shipping_zones` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_shipping_rates_shipping_method_id_shipping_methods` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipment_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `shipment_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(80) NOT NULL,
    `status_code` VARCHAR(80),
    `location_text` VARCHAR(250),
    `description` TEXT,
    `provider_event_id` VARCHAR(255),
    `payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `occurred_at` DATETIME(6) NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_shipment_events_shipment_id_shipments` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `fulfilment_tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `task_number` VARCHAR(190) NOT NULL UNIQUE,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `shipment_id` BIGINT UNSIGNED,
    `inventory_location_id` BIGINT UNSIGNED,
    `task_type` VARCHAR(30) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
    `assigned_to_user_id` INT,
    `due_at` DATETIME(6),
    `started_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `instructions` TEXT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_fulfilment_tasks_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_fulfilment_tasks_shipment_id_shipments` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fulfilment_tasks_inventory_location_i_cabd5c91` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fulfilment_tasks_assigned_to_user_id_users` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

ALTER TABLE `sales_orders` ADD COLUMN `source_channel` VARCHAR(30) NOT NULL DEFAULT 'web', ADD COLUMN `external_source_reference` VARCHAR(160) NULL, ADD COLUMN `order_type` VARCHAR(30) NOT NULL DEFAULT 'retail', ADD COLUMN `promised_delivery_date` DATE NULL, ADD COLUMN `confirmed_at` DATETIME(6) NULL, ADD COLUMN `assigned_to_user_id` INT NULL, ADD COLUMN `price_list_id` BIGINT UNSIGNED NULL, ADD COLUMN `source_quotation_id` BIGINT UNSIGNED NULL, ADD COLUMN `approval_request_id` BIGINT UNSIGNED NULL, ADD COLUMN `version_number` INT NOT NULL DEFAULT 1, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_sales_orders_assigned_user` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_sales_orders_price_list` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_sales_orders_source_quote` FOREIGN KEY (`source_quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_sales_orders_approval` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `sales_order_items` ADD COLUMN `quotation_item_id` BIGINT UNSIGNED NULL, ADD COLUMN `cost_snapshot_cents` BIGINT NULL, ADD COLUMN `tax_rate_snapshot` DECIMAL(7,6) NULL, ADD COLUMN `fulfilled_quantity` INT NOT NULL DEFAULT 0, ADD COLUMN `returned_quantity` INT NOT NULL DEFAULT 0, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_order_items_quote_item` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `payment_refunds` ADD COLUMN `sales_return_id` BIGINT UNSIGNED NULL, ADD COLUMN `approval_request_id` BIGINT UNSIGNED NULL, ADD CONSTRAINT `fk_payment_refunds_sales_return` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_payment_refunds_approval` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `shipments` ADD COLUMN `shipping_zone_id` BIGINT UNSIGNED NULL, ADD COLUMN `shipping_method_id` BIGINT UNSIGNED NULL, ADD COLUMN `dispatch_inventory_location_id` BIGINT UNSIGNED NULL, ADD COLUMN `estimated_delivery_at` DATETIME(6) NULL, ADD COLUMN `promised_delivery_at` DATETIME(6) NULL, ADD COLUMN `label_file_asset_id` BIGINT UNSIGNED NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_shipments_shipping_zone` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_shipments_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_shipments_dispatch_location` FOREIGN KEY (`dispatch_inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_shipments_label_file` FOREIGN KEY (`label_file_asset_id`) REFERENCES `file_assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `service_requests` ADD COLUMN `quotation_id` BIGINT UNSIGNED NULL, ADD COLUMN `invoice_id` BIGINT UNSIGNED NULL, ADD COLUMN `requires_site_survey` TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN `site_survey_completed_at` DATETIME(6) NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_service_requests_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_service_requests_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@
