-- MySQL-specific hardening indexes and generated columns.
-- These replace selected PostgreSQL partial indexes without blocking multiple non-default rows.

ALTER TABLE `addresses`
    ADD COLUMN `default_shipping_customer_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_default_shipping` = 1 THEN `customer_id` ELSE NULL END) VIRTUAL,
    ADD COLUMN `default_billing_customer_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_default_billing` = 1 THEN `customer_id` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_addresses_default_shipping` (`default_shipping_customer_key`),
    ADD UNIQUE INDEX `uq_addresses_default_billing` (`default_billing_customer_key`);
-- @@STATEMENT_END@@

ALTER TABLE `product_images`
    ADD COLUMN `primary_product_image_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_primary` = 1 THEN `product_id` ELSE NULL END) VIRTUAL,
    ADD COLUMN `exclusive_product_image_role_key` VARCHAR(320) GENERATED ALWAYS AS (
        CASE WHEN `image_role` IN ('listing_primary', 'detail_hero') THEN CONCAT(`product_id`, ':', `image_role`) ELSE NULL END
    ) VIRTUAL,
    ADD UNIQUE INDEX `uq_product_primary_image` (`primary_product_image_key`),
    ADD UNIQUE INDEX `uq_product_exclusive_image_role` (`exclusive_product_image_role_key`),
    ADD UNIQUE INDEX `uq_product_image_url` (`product_id`, `image_url`);
-- @@STATEMENT_END@@

ALTER TABLE `product_variants`
    ADD COLUMN `default_product_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_default` = 1 THEN `product_id` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_product_default_variant` (`default_product_key`);
-- @@STATEMENT_END@@

ALTER TABLE `supplier_products`
    ADD COLUMN `preferred_variant_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_preferred` = 1 THEN `product_variant_id` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_supplier_preferred_variant` (`preferred_variant_key`);
-- @@STATEMENT_END@@

ALTER TABLE `wishlists`
    ADD COLUMN `default_customer_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_default` = 1 THEN `customer_id` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_wishlist_default_customer` (`default_customer_key`);
-- @@STATEMENT_END@@

ALTER TABLE `roles`
    ADD COLUMN `business_scope_key` BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(`business_id`, 0)) VIRTUAL,
    ADD UNIQUE INDEX `uq_roles_scope_key` (`business_scope_key`, `role_key`);
-- @@STATEMENT_END@@

ALTER TABLE `user_roles`
    ADD COLUMN `active_scope_key` VARCHAR(255) GENERATED ALWAYS AS (
        CASE WHEN `revoked_at` IS NULL THEN CONCAT(`user_id`, ':', `role_id`, ':', COALESCE(`business_id`, 0), ':', COALESCE(`business_location_id`, 0)) ELSE NULL END
    ) VIRTUAL,
    ADD UNIQUE INDEX `uq_user_roles_active_scope` (`active_scope_key`);
-- @@STATEMENT_END@@

ALTER TABLE `customer_contacts`
    ADD COLUMN `primary_contact_key` VARCHAR(255) GENERATED ALWAYS AS (
        CASE WHEN `is_primary` = 1 AND `deleted` IS NULL THEN CONCAT(`customer_id`, ':', `contact_type`) ELSE NULL END
    ) VIRTUAL,
    ADD UNIQUE INDEX `uq_customer_primary_contact_type` (`primary_contact_key`);
-- @@STATEMENT_END@@

ALTER TABLE `carts`
    ADD COLUMN `active_user_key` INT GENERATED ALWAYS AS (CASE WHEN `status` = 'active' THEN `user_id` ELSE NULL END) VIRTUAL,
    ADD COLUMN `active_anonymous_key` VARCHAR(255) GENERATED ALWAYS AS (CASE WHEN `status` = 'active' THEN `anonymous_token_hash` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_carts_active_user` (`active_user_key`),
    ADD UNIQUE INDEX `uq_carts_active_anonymous` (`active_anonymous_key`);
-- @@STATEMENT_END@@

ALTER TABLE `product_category_assignments`
    ADD COLUMN `primary_product_key` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `is_primary` = 1 THEN `product_id` ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_product_primary_category_assignment` (`primary_product_key`);
-- @@STATEMENT_END@@

ALTER TABLE `catalog_attribute_values`
    ADD UNIQUE INDEX `uq_product_attribute_value` (`product_id`, `attribute_definition_id`),
    ADD UNIQUE INDEX `uq_variant_attribute_value` (`product_variant_id`, `attribute_definition_id`);
-- @@STATEMENT_END@@

ALTER TABLE `tax_categories`
    ADD COLUMN `default_tax_category_key` TINYINT GENERATED ALWAYS AS (CASE WHEN `is_default` = 1 THEN 1 ELSE NULL END) VIRTUAL,
    ADD UNIQUE INDEX `uq_default_tax_category` (`default_tax_category_key`);
-- @@STATEMENT_END@@

-- "One default active price list per currency" cannot be a database constraint on
-- MariaDB. The generated-column trick used above works only while the expression
-- yields a number: with a string column MariaDB accepts the VIRTUAL column but
-- refuses a unique index on it, because a unique comparison needs a settled
-- collation and CASE does not produce one, and it refuses the STORED form
-- outright. Enforced in the service layer instead, inside the transaction that
-- promotes a price list to default. This index supports that check.
CREATE INDEX `idx_price_lists_default_active_currency` ON `price_lists` (`currency`, `is_default`, `is_active`);
-- @@STATEMENT_END@@

ALTER TABLE `stock_reservations`
    ADD COLUMN `active_order_item_location_key` VARCHAR(255) GENERATED ALWAYS AS (
        CASE WHEN `status` = 'active' THEN CONCAT(`sales_order_item_id`, ':', `inventory_location_id`) ELSE NULL END
    ) VIRTUAL,
    ADD UNIQUE INDEX `uq_active_stock_reservation_order_item_location` (`active_order_item_location_key`);
-- @@STATEMENT_END@@

ALTER TABLE `integration_events`
    ADD UNIQUE INDEX `uq_integration_event_idempotency` (`external_integration_id`, `idempotency_key`);
-- @@STATEMENT_END@@

ALTER TABLE `product_reviews`
    ADD COLUMN `guest_product_email_key` VARCHAR(512) GENERATED ALWAYS AS (
        CASE WHEN `customer_id` IS NULL AND `reviewer_email` IS NOT NULL THEN CONCAT(`product_id`, ':', LOWER(`reviewer_email`)) ELSE NULL END
    ) VIRTUAL,
    ADD UNIQUE INDEX `uq_guest_review_per_product_email` (`guest_product_email_key`);
-- @@STATEMENT_END@@

CREATE FULLTEXT INDEX `ft_products_search` ON `products` (`name`, `short_description`, `description`);
-- @@STATEMENT_END@@

CREATE INDEX `idx_orders_customer_placed` ON `sales_orders` (`customer_id`, `placed_at`);
-- @@STATEMENT_END@@
CREATE INDEX `idx_orders_promised_status` ON `sales_orders` (`promised_delivery_date`, `status`);
-- @@STATEMENT_END@@
CREATE INDEX `idx_orders_source_reference` ON `sales_orders` (`source_channel`, `external_source_reference`);
-- @@STATEMENT_END@@
CREATE INDEX `idx_inventory_available` ON `inventory_balances` (`inventory_location_id`, `quantity_available`);
-- @@STATEMENT_END@@
CREATE INDEX `idx_invoices_customer_status` ON `invoices` (`customer_id`, `status`, `issue_date`);
-- @@STATEMENT_END@@
CREATE INDEX `idx_service_appointments_staff_time` ON `service_appointments` (`assigned_staff_user_id`, `starts_at`, `ends_at`, `status`);
-- @@STATEMENT_END@@
