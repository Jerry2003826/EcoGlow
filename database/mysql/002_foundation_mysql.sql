-- Eco Glow Lighting MySQL 8 migration module generated from 001_foundation.sql.
-- PostgreSQL-only constructs were replaced or documented; all identifiers use CakePHP-compatible timestamps.

CREATE TABLE IF NOT EXISTS `customers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE,
    `email` VARCHAR(255) UNIQUE,
    `phone` VARCHAR(30),
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100),
    `company` VARCHAR(200),
    `status` VARCHAR(30) NOT NULL DEFAULT 'lead',
    `source` VARCHAR(30) NOT NULL DEFAULT 'web',
    `preferred_contact_channel` VARCHAR(20),
    `marketing_opt_in` TINYINT(1) NOT NULL DEFAULT 0,
    `marketing_consent_at` DATETIME(6),
    `tags` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted` DATETIME(6),
    CONSTRAINT `fk_customers_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `author_user_id` INT,
    `note` TEXT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_customer_notes_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_customer_notes_author_user_id_users` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `auth_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `refresh_token_hash` VARCHAR(255) NOT NULL UNIQUE,
    `ip_hash` TEXT,
    `user_agent` TEXT,
    `expires_at` DATETIME(6) NOT NULL,
    `revoked_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_auth_sessions_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_type` VARCHAR(30) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` DATETIME(6) NOT NULL,
    `used_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_auth_tokens_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(80),
    `recipient_name` VARCHAR(200) NOT NULL,
    `company` VARCHAR(200),
    `line1` VARCHAR(200) NOT NULL,
    `line2` VARCHAR(200),
    `suburb` VARCHAR(120) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `postcode` VARCHAR(20) NOT NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'AU',
    `phone` VARCHAR(30),
    `is_default_shipping` TINYINT(1) NOT NULL DEFAULT 0,
    `is_default_billing` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_addresses_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(120) PRIMARY KEY,
    `setting_value` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `description` TEXT,
    `updated_by_user_id` INT,
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_site_settings_updated_by_user_id_users` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `content_pages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `title` VARCHAR(200) NOT NULL,
    `body` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `meta_title` VARCHAR(200),
    `meta_description` VARCHAR(320),
    `published_at` DATETIME(6),
    `created_by_user_id` INT,
    `updated_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_content_pages_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_content_pages_updated_by_user_id_users` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `contact_message_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `contact_message_id` INT NOT NULL,
    `actor_user_id` INT,
    `channel` VARCHAR(30) NOT NULL,
    `direction` VARCHAR(30) NOT NULL,
    `body` TEXT NOT NULL,
    `external_message_id` VARCHAR(255),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_contact_message_events_contact_message_id_f72bb774` FOREIGN KEY (`contact_message_id`) REFERENCES `contact_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_contact_message_events_actor_user_id_users` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `image_url` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_categories_parent_id_categories` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `brands` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `website_url` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_id` BIGINT UNSIGNED,
    `brand_id` BIGINT UNSIGNED,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(250) NOT NULL,
    `product_type` VARCHAR(80) NOT NULL,
    `short_description` TEXT,
    `description` TEXT,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `installation_available` TINYINT(1) NOT NULL DEFAULT 0,
    `smart_compatible` TINYINT(1) NOT NULL DEFAULT 0,
    `warranty_months` INT,
    `specifications` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `tags` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `seo_title` VARCHAR(200),
    `seo_description` VARCHAR(320),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_products_category_id_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_products_brand_id_brands` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_products_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `sku` VARCHAR(255) NOT NULL UNIQUE,
    `barcode` VARCHAR(100),
    `name` VARCHAR(200) NOT NULL,
    `attributes` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `price_cents` BIGINT NOT NULL,
    `compare_at_price_cents` BIGINT,
    `cost_cents` BIGINT,
    `tax_rate` DECIMAL(6,5) NOT NULL DEFAULT 0.10000,
    `weight_grams` INT,
    `dimensions_mm` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_product_variants_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `product_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED,
    `image_url` TEXT NOT NULL,
    `alt_text` VARCHAR(250),
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_product_images_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_product_images_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(160) NOT NULL UNIQUE,
    `description` TEXT,
    `base_price_cents` BIGINT,
    `default_duration_minutes` INT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `inventory_locations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `address` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `inventory_balances` (
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `quantity_on_hand` INT NOT NULL DEFAULT 0,
    `quantity_reserved` INT NOT NULL DEFAULT 0,
    `quantity_available` INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
    `reorder_point` INT NOT NULL DEFAULT 0,
    `reorder_quantity` INT NOT NULL DEFAULT 0,
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`product_variant_id`, `inventory_location_id`),
    CONSTRAINT `fk_inventory_balances_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inventory_balances_inventory_location_i_7852c9c3` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `movement_type` VARCHAR(31) NOT NULL,
    `on_hand_delta` INT NOT NULL DEFAULT 0,
    `reserved_delta` INT NOT NULL DEFAULT 0,
    `reference_type` VARCHAR(80),
    `reference_id` BIGINT UNSIGNED,
    `note` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_inventory_movements_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inventory_movements_inventory_location_i_e0e7cefa` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inventory_movements_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(220) NOT NULL,
    `abn` VARCHAR(20) UNIQUE,
    `contact_name` VARCHAR(200),
    `email` VARCHAR(255),
    `phone` VARCHAR(30),
    `website_url` TEXT,
    `address` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `default_lead_time_days` INT,
    `payment_terms_days` INT,
    `notes` TEXT,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `supplier_products` (
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `supplier_sku` VARCHAR(120),
    `unit_cost_cents` BIGINT NOT NULL,
    `minimum_order_quantity` INT NOT NULL DEFAULT 1,
    `lead_time_days` INT,
    `is_preferred` TINYINT(1) NOT NULL DEFAULT 0,
    `last_cost_updated_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`supplier_id`, `product_variant_id`),
    CONSTRAINT `fk_supplier_products_supplier_id_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_supplier_products_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `purchase_orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(190) NOT NULL UNIQUE,
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `total_cents` BIGINT NOT NULL DEFAULT 0,
    `ordered_at` DATETIME(6),
    `expected_at` DATETIME(6),
    `notes` TEXT,
    `created_by_user_id` INT,
    `approved_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_purchase_orders_supplier_id_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_orders_inventory_location_id_inventory_locations` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_orders_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_orders_approved_by_user_id_users` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `purchase_order_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `supplier_sku_snapshot` VARCHAR(120),
    `product_name_snapshot` VARCHAR(250) NOT NULL,
    `variant_name_snapshot` VARCHAR(200) NOT NULL,
    `quantity_ordered` INT NOT NULL,
    `quantity_received` INT NOT NULL DEFAULT 0,
    `unit_cost_cents` BIGINT NOT NULL,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_purchase_order_items_purchase_order_id_purchase_orders` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_purchase_order_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `goods_receipts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(190) NOT NULL UNIQUE,
    `purchase_order_id` BIGINT UNSIGNED NOT NULL,
    `received_by_user_id` INT,
    `received_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `supplier_document_reference` VARCHAR(160),
    `notes` TEXT,
    CONSTRAINT `fk_goods_receipts_purchase_order_id_purchase_orders` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_goods_receipts_received_by_user_id_users` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `goods_receipt_id` BIGINT UNSIGNED NOT NULL,
    `purchase_order_item_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `quantity_received` INT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`goods_receipt_id`, `purchase_order_item_id`, `inventory_location_id`),
    CONSTRAINT `fk_goods_receipt_items_goods_receipt_id_goods_receipts` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_goods_receipt_items_purchase_order_item__b5f11486` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_goods_receipt_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_goods_receipt_items_inventory_location_i_67faeffd` FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `carts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `anonymous_token_hash` TEXT,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `expires_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_carts_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `cart_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price_snapshot_cents` BIGINT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`cart_id`, `product_variant_id`),
    CONSTRAINT `fk_cart_items_cart_id_carts` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cart_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `sales_orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(190) NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED,
    `guest_name` VARCHAR(200),
    `guest_email` VARCHAR(255),
    `guest_phone` VARCHAR(30),
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `payment_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `fulfilment_method` VARCHAR(30) NOT NULL DEFAULT 'shipping',
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `subtotal_cents` BIGINT NOT NULL DEFAULT 0,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `shipping_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `grand_total_cents` BIGINT NOT NULL DEFAULT 0,
    `customer_notes` TEXT,
    `internal_notes` TEXT,
    `placed_at` DATETIME(6),
    `cancelled_at` DATETIME(6),
    `completed_at` DATETIME(6),
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_sales_orders_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_orders_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `order_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `address_type` VARCHAR(20) NOT NULL,
    `recipient_name` VARCHAR(200) NOT NULL,
    `company` VARCHAR(200),
    `line1` VARCHAR(200) NOT NULL,
    `line2` VARCHAR(200),
    `suburb` VARCHAR(120) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `postcode` VARCHAR(20) NOT NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'AU',
    `phone` VARCHAR(30),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`sales_order_id`, `address_type`),
    CONSTRAINT `fk_order_addresses_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `sales_order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `item_type` VARCHAR(30) NOT NULL,
    `product_id` BIGINT UNSIGNED,
    `product_variant_id` BIGINT UNSIGNED,
    `service_type_id` BIGINT UNSIGNED,
    `sku_snapshot` VARCHAR(120),
    `item_name_snapshot` VARCHAR(250) NOT NULL,
    `variant_name_snapshot` VARCHAR(200),
    `quantity` INT NOT NULL,
    `unit_price_cents` BIGINT NOT NULL,
    `discount_cents` BIGINT NOT NULL DEFAULT 0,
    `tax_cents` BIGINT NOT NULL DEFAULT 0,
    `line_total_cents` BIGINT NOT NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_sales_order_items_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_order_items_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_order_items_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sales_order_items_service_type_id_service_types` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(30),
    `to_status` VARCHAR(30) NOT NULL,
    `changed_by_user_id` INT,
    `note` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_order_status_history_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_status_history_changed_by_user_id_users` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `provider` VARCHAR(80) NOT NULL,
    `provider_payment_id` VARCHAR(255),
    `method` VARCHAR(30) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `amount_cents` BIGINT NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'AUD',
    `transaction_reference` VARCHAR(255),
    `provider_metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `authorised_at` DATETIME(6),
    `captured_at` DATETIME(6),
    `failed_at` DATETIME(6),
    `failure_reason` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`provider`, `provider_payment_id`),
    CONSTRAINT `fk_payments_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `payment_refunds` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `provider_refund_id` VARCHAR(255),
    `idempotency_key` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `refund_kind` VARCHAR(40) NOT NULL DEFAULT 'customer_refund',
    `amount_cents` BIGINT NOT NULL,
    `reason` TEXT,
    `provider_metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `requested_by_user_id` INT,
    `completed_at` DATETIME(6),
    `failure_reason` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`payment_id`, `provider_refund_id`),
    CONSTRAINT `fk_payment_refunds_payment_id_payments` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_refunds_requested_by_user_id_users` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sales_order_id` BIGINT UNSIGNED NOT NULL,
    `shipment_number` VARCHAR(190) NOT NULL UNIQUE,
    `carrier` VARCHAR(120),
    `tracking_number` VARCHAR(255),
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `shipped_at` DATETIME(6),
    `delivered_at` DATETIME(6),
    `shipping_cost_cents` BIGINT NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_shipments_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `shipment_items` (
    `shipment_id` BIGINT UNSIGNED NOT NULL,
    `sales_order_item_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    PRIMARY KEY (`shipment_id`, `sales_order_item_id`),
    CONSTRAINT `fk_shipment_items_shipment_id_shipments` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_shipment_items_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(190) NOT NULL UNIQUE,
    `customer_id` BIGINT UNSIGNED,
    `sales_order_id` BIGINT UNSIGNED,
    `sales_order_item_id` BIGINT UNSIGNED,
    `service_type_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED,
    `contact_name` VARCHAR(200) NOT NULL,
    `contact_email` VARCHAR(255),
    `contact_phone` VARCHAR(30),
    `address_line1` VARCHAR(200) NOT NULL,
    `address_line2` VARCHAR(200),
    `suburb` VARCHAR(120) NOT NULL,
    `state` VARCHAR(80) NOT NULL,
    `postcode` VARCHAR(20) NOT NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'AU',
    `preferred_date` DATE,
    `issue_description` TEXT NOT NULL,
    `attachment_urls` JSON NOT NULL DEFAULT (JSON_ARRAY()),
    `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
    `status` VARCHAR(30) NOT NULL DEFAULT 'new',
    `estimated_price_cents` BIGINT,
    `final_price_cents` BIGINT,
    `assigned_staff_user_id` INT,
    `completed_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_requests_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_requests_sales_order_id_sales_orders` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_requests_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_requests_service_type_id_service_types` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_service_requests_product_variant_id_product_variants` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_service_requests_assigned_staff_user_id_users` FOREIGN KEY (`assigned_staff_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_appointments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `assigned_staff_user_id` INT NOT NULL,
    `starts_at` DATETIME(6) NOT NULL,
    `ends_at` DATETIME(6) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'tentative',
    `customer_instructions` TEXT,
    `internal_notes` TEXT,
    `created_by_user_id` INT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_appointments_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_appointments_assigned_staff_user_id_users` FOREIGN KEY (`assigned_staff_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_service_appointments_created_by_user_id_users` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `service_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `service_request_id` BIGINT UNSIGNED NOT NULL,
    `author_user_id` INT,
    `note` TEXT NOT NULL,
    `visible_to_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_service_notes_service_request_id_service_requests` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_service_notes_author_user_id_users` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `product_reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED,
    `sales_order_item_id` BIGINT UNSIGNED,
    `reviewer_name` VARCHAR(200) NOT NULL,
    `reviewer_email` VARCHAR(255),
    `rating` SMALLINT NOT NULL,
    `title` VARCHAR(200),
    `body` TEXT,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `verified_purchase` TINYINT(1) NOT NULL DEFAULT 0,
    `moderated_by_user_id` INT,
    `moderated_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    UNIQUE (`customer_id`, `product_id`),
    CONSTRAINT `fk_product_reviews_product_id_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_product_reviews_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_product_reviews_sales_order_item_id_sales_order_items` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_product_reviews_moderated_by_user_id_users` FOREIGN KEY (`moderated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `customer_id` BIGINT UNSIGNED,
    `anonymous_session_hash` TEXT,
    `purpose` VARCHAR(30) NOT NULL,
    `consent_given` TINYINT(1) NOT NULL DEFAULT 0,
    `started_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ended_at` DATETIME(6),
    `retention_expires_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_conversations_user_id_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_conversations_customer_id_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ai_conversation_id` BIGINT UNSIGNED NOT NULL,
    `role` VARCHAR(30) NOT NULL,
    `content` TEXT NOT NULL,
    `model_name` VARCHAR(160),
    `input_tokens` INT,
    `output_tokens` INT,
    `latency_ms` INT,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_messages_ai_conversation_id_ai_conversations` FOREIGN KEY (`ai_conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_product_recommendations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ai_conversation_id` BIGINT UNSIGNED NOT NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `score` DECIMAL(6,5),
    `rationale` TEXT,
    `accepted_at` DATETIME(6),
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE (`ai_conversation_id`, `product_variant_id`),
    CONSTRAINT `fk_ai_product_recommendat_ai_conversation_id_c755e255` FOREIGN KEY (`ai_conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_product_recommendat_product_variant_id_9b448023` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `ai_action_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ai_conversation_id` BIGINT UNSIGNED,
    `requested_by_message_id` BIGINT UNSIGNED,
    `action_type` VARCHAR(120) NOT NULL,
    `action_payload` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `risk_level` VARCHAR(20) NOT NULL DEFAULT 'medium',
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `reviewed_by_user_id` INT,
    `reviewed_at` DATETIME(6),
    `executed_at` DATETIME(6),
    `execution_result` JSON,
    `error_message` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_ai_action_requests_ai_conversation_id_ai_conversations` FOREIGN KEY (`ai_conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_action_requests_requested_by_message_id_ai_messages` FOREIGN KEY (`requested_by_message_id`) REFERENCES `ai_messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ai_action_requests_reviewed_by_user_id_users` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `actor_user_id` INT,
    `action` VARCHAR(120) NOT NULL,
    `entity_type` VARCHAR(120) NOT NULL,
    `entity_id` BIGINT UNSIGNED,
    `before_data` JSON,
    `after_data` JSON,
    `ip_hash` TEXT,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT `fk_audit_logs_actor_user_id_users` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `customer_sensitive_profiles` (
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `date_of_birth` DATE NULL,
    `age_at_registration` SMALLINT NULL,
    `age_source` VARCHAR(30) NULL,
    `access_note` VARCHAR(255) NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`customer_id`),
    CONSTRAINT `fk_customer_sensitive_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `content_sections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_page_id` BIGINT UNSIGNED NOT NULL,
    `section_key` VARCHAR(120) NOT NULL,
    `section_type` VARCHAR(60) NOT NULL DEFAULT 'rich_text',
    `heading` VARCHAR(255) NULL,
    `body` TEXT NULL,
    `image_url` VARCHAR(512) NULL,
    `image_alt_text` VARCHAR(255) NULL,
    `settings` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(30) NOT NULL DEFAULT 'published',
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_content_section_key` (`content_page_id`, `section_key`),
    CONSTRAINT `fk_content_sections_page` FOREIGN KEY (`content_page_id`) REFERENCES `content_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `content_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_section_id` BIGINT UNSIGNED NOT NULL,
    `item_key` VARCHAR(120) NULL,
    `title` VARCHAR(255) NULL,
    `body` TEXT NULL,
    `image_url` VARCHAR(512) NULL,
    `image_alt_text` VARCHAR(255) NULL,
    `link_url` VARCHAR(512) NULL,
    `metadata` JSON NOT NULL DEFAULT (JSON_OBJECT()),
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    KEY `idx_content_items_section_sort` (`content_section_id`, `sort_order`),
    CONSTRAINT `fk_content_items_section` FOREIGN KEY (`content_section_id`) REFERENCES `content_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `materials` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(190) NOT NULL,
    `description` TEXT NULL,
    `image_url` VARCHAR(512) NULL,
    `image_alt_text` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_materials_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `product_materials` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`product_id`, `material_id`),
    CONSTRAINT `fk_product_materials_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_product_materials_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

CREATE TABLE IF NOT EXISTS `saved_cart_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NULL,
    `user_id` INT NULL,
    `anonymous_token_hash` VARCHAR(255) NULL,
    `product_variant_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `note` VARCHAR(500) NULL,
    `saved_from_cart_id` BIGINT UNSIGNED NULL,
    `created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_saved_cart_customer_variant` (`customer_id`, `product_variant_id`),
    KEY `idx_saved_cart_anonymous` (`anonymous_token_hash`),
    CONSTRAINT `fk_saved_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_saved_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_saved_cart_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_saved_cart_cart` FOREIGN KEY (`saved_from_cart_id`) REFERENCES `carts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD CONSTRAINT `fk_contact_messages_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD CONSTRAINT `fk_contact_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD CONSTRAINT `fk_contact_messages_assigned_user` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD CONSTRAINT `fk_contact_messages_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

ALTER TABLE `contact_messages` ADD CONSTRAINT `fk_contact_messages_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
-- @@STATEMENT_END@@

CREATE INDEX `idx_customers_status_created` ON `customers` (`status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_customer_notes_customer_created` ON `customer_notes` (`customer_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_auth_sessions_user_expires` ON `auth_sessions` (`user_id`, `expires_at`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_auth_sessions_active` ON `auth_sessions` (`user_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_auth_tokens_user_type` ON `auth_tokens` (`user_id`, `token_type`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_contact_message_events_message_created` ON `contact_message_events` (`contact_message_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_categories_parent` ON `categories` (`parent_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_products_category_status` ON `products` (`category_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_products_brand_status` ON `products` (`brand_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_product_variants_product_active` ON `product_variants` (`product_id`, `is_active`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_product_images_product_sort` ON `product_images` (`product_id`, `sort_order`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_inventory_balances_location` ON `inventory_balances` (`inventory_location_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_inventory_low_stock` ON `inventory_balances` (`inventory_location_id`, `quantity_available`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_inventory_movements_variant_created` ON `inventory_movements` (`product_variant_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_inventory_movements_reference` ON `inventory_movements` (`reference_type`, `reference_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_suppliers_active` ON `suppliers` (`is_active`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_supplier_products_variant` ON `supplier_products` (`product_variant_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_purchase_orders_supplier_status` ON `purchase_orders` (`supplier_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_purchase_orders_location_status` ON `purchase_orders` (`inventory_location_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_purchase_order_items_po` ON `purchase_order_items` (`purchase_order_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_goods_receipts_po` ON `goods_receipts` (`purchase_order_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_carts_user_status` ON `carts` (`user_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_cart_items_variant` ON `cart_items` (`product_variant_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_sales_orders_customer_created` ON `sales_orders` (`customer_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_sales_orders_status_created` ON `sales_orders` (`status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_sales_orders_payment_status` ON `sales_orders` (`payment_status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_sales_order_items_order` ON `sales_order_items` (`sales_order_id`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_order_status_history_order_created` ON `order_status_history` (`sales_order_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_payments_order_status` ON `payments` (`sales_order_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_payment_refunds_payment_status` ON `payment_refunds` (`payment_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_shipments_order_status` ON `shipments` (`sales_order_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_shipments_tracking` ON `shipments` (`tracking_number`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_service_requests_status_created` ON `service_requests` (`status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_service_requests_assigned_status` ON `service_requests` (`assigned_staff_user_id`, `status`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_service_notes_request_created` ON `service_notes` (`service_request_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_product_reviews_product_status` ON `product_reviews` (`product_id`, `status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_ai_conversations_user_created` ON `ai_conversations` (`user_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_ai_conversations_customer_created` ON `ai_conversations` (`customer_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_ai_messages_conversation_created` ON `ai_messages` (`ai_conversation_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_ai_action_requests_status_created` ON `ai_action_requests` (`status`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_audit_logs_entity` ON `audit_logs` (`entity_type`, `entity_id`, `created`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_audit_logs_actor` ON `audit_logs` (`actor_user_id`, `created`);
-- @@STATEMENT_END@@
