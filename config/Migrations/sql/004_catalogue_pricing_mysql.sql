-- Eco Glow Lighting MySQL 8 migration module generated from 003_catalogue_pricing.sql.
-- PostgreSQL-only constructs were replaced or documented; all identifiers use CakePHP-compatible timestamps.

CREATE TABLE IF NOT EXISTS `product_category_assignments` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`product_id`, `category_id`),
    CONSTRAINT `fk_product_category_assignments_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_product_category_assignments_category_id_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `attribute_definitions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `attribute_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `data_type` VARCHAR(20) NOT NULL,
    `unit_label` VARCHAR(40),
    `is_filterable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_searchable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_variant_defining` TINYINT(1) NOT NULL DEFAULT 0,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `validation_rules` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `attribute_options` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `attribute_definition_id` BIGINT UNSIGNED NOT NULL,
    `option_key` VARCHAR(255) NOT NULL,
    `label` VARCHAR(160) NOT NULL,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`attribute_definition_id`, `option_key`),
    CONSTRAINT `fk_attribute_options_attribute_definition_1e60ab0c` FOREIGN KEY (`attribute_definition_id`) REFERENCES `attribute_definitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `catalog_attribute_values` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `attribute_definition_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED,
    `product_variant_id` BIGINT UNSIGNED,
    `attribute_option_id` BIGINT UNSIGNED,
    `value_text` TEXT,
    `value_number` DECIMAL(18,6),
    `value_boolean` TINYINT(1),
    `value_json` JSON,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_catalog_attribute_valu_attribute_definition_fa19d872` FOREIGN KEY (`attribute_definition_id`) REFERENCES `attribute_definitions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_catalog_attribute_values_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_catalog_attribute_valu_product_variant_id_fd0afe9f` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_catalog_attribute_valu_attribute_option_id_4cb98d36` FOREIGN KEY (`attribute_option_id`) REFERENCES `attribute_options` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `tax_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `tax_category_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `description` TEXT,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `tax_rates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `tax_category_id` BIGINT UNSIGNED NOT NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'AU',
    `region_code` VARCHAR(20),
    `rate` DECIMAL(7,6) NOT NULL,
    `tax_name` VARCHAR(80) NOT NULL DEFAULT 'GST',
    `price_includes_tax` TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from` DATE NOT NULL DEFAULT CURRENT_DATE,
    `valid_to` DATE,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_tax_rates_tax_category_id_tax_categories` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `price_lists` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `price_list_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(180) NOT NULL,
    `description` TEXT,
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `customer_type` VARCHAR(30),
    `tax_inclusive` TINYINT(1) NOT NULL DEFAULT 1,
    `priority` INT NOT NULL DEFAULT 0,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from` DATETIME(6),
    `valid_until` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `price_list_entries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `price_list_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `minimum_quantity` INT NOT NULL DEFAULT 1,
    `unit_price_cents` BIGINT NOT NULL,
    `compare_at_price_cents` BIGINT,
    `valid_from` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `valid_until` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`price_list_id`, `product_variant_id`, `minimum_quantity`, `valid_from`),
    CONSTRAINT `fk_price_list_entries_price_list_id_price_lists` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_price_list_entries_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_price_list_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `price_list_id` BIGINT UNSIGNED NOT NULL,
    `priority` INT NOT NULL DEFAULT 0,
    `valid_from` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `valid_until` DATETIME(6),
    `assigned_by_user_id` INT,
    `reason` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`customer_id`, `price_list_id`, `valid_from`),
    CONSTRAINT `fk_customer_price_list_assignments_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_price_list_assignments_price_list_id_price_lists` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_price_list_assignments_assigned_by_user_id_users` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `trade_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `account_number` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `business_name` VARCHAR(250),
    `abn` VARCHAR(20),
    `price_list_id` BIGINT UNSIGNED,
    `credit_limit_cents` BIGINT NOT NULL DEFAULT 0,
    `current_credit_used_cents` BIGINT NOT NULL DEFAULT 0,
    `payment_terms_days` INT NOT NULL DEFAULT 0,
    `default_discount_rate` DECIMAL(7,6) NOT NULL DEFAULT 0,
    `requires_order_approval` TINYINT(1) NOT NULL DEFAULT 0,
    `approved_by_user_id` INT,
    `approved_at` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_trade_accounts_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_trade_accounts_price_list_id_price_lists` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_trade_accounts_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `product_cost_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `supplier_id` BIGINT UNSIGNED,
    `cost_cents` BIGINT NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `source_type` VARCHAR(50) NOT NULL DEFAULT 'manual',
    `source_reference_id` BIGINT UNSIGNED,
    `effective_from` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `effective_until` DATETIME(6),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_product_cost_history_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_product_cost_history_supplier_id_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_product_cost_history_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `promotions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `promotion_key` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `priority` INT NOT NULL DEFAULT 0,
    `combinable` TINYINT(1) NOT NULL DEFAULT 0,
    `automatic` TINYINT(1) NOT NULL DEFAULT 0,
    `conditions` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `actions` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `usage_limit` INT,
    `per_customer_limit` INT,
    `starts_at` DATETIME(6),
    `ends_at` DATETIME(6),
    `created_by_user_id` INT,
    `approved_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_promotions_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_promotions_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `promotion_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `promotion_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `usage_limit` INT,
    `usage_count` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `starts_at` DATETIME(6),
    `ends_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_promotion_codes_promotion_id_promotions` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `promotion_redemptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `promotion_id` BIGINT UNSIGNED NOT NULL,
    `promotion_code_id` BIGINT UNSIGNED,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED,
    `discount_cents` BIGINT NOT NULL,
    `redeemed_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `reversed_at` DATETIME(6),
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    UNIQUE (`promotion_id`, `sales_order_id`),
    CONSTRAINT `fk_promotion_redemptions_promotion_id_promotions` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_promotion_redemptions_promotion_code_id_promotion_codes` FOREIGN KEY (`promotion_code_id`) REFERENCES `promotion_codes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_promotion_redemptions_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_promotion_redemptions_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

ALTER TABLE `products` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN `published_at` DATETIME(6) NULL, ADD COLUMN `discontinued_at` DATETIME(6) NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT());
-- @@STATEMENT_END@@

ALTER TABLE `product_variants` ADD COLUMN `tax_category_id` BIGINT UNSIGNED NULL, ADD COLUMN `track_inventory` TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN `allow_backorder` TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN `unit_of_measure` VARCHAR(30) NOT NULL DEFAULT 'each', ADD COLUMN `minimum_order_quantity` INT NOT NULL DEFAULT 1, ADD COLUMN `maximum_order_quantity` INT NULL, ADD COLUMN `lead_time_days` INT NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()), ADD CONSTRAINT `fk_product_variants_tax_category` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `product_images` MODIFY COLUMN `image_url` VARCHAR(512) NOT NULL, ADD COLUMN `image_role` VARCHAR(30) NOT NULL DEFAULT 'gallery', ADD COLUMN `aspect_ratio` VARCHAR(20) NULL, ADD COLUMN `width_px` INT NULL, ADD COLUMN `height_px` INT NULL, ADD COLUMN `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT());
-- @@STATEMENT_END@@
