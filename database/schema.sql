-- Fresh install: run schema.sql, then views.sql, then the seed_*.sql files.
-- Already-running dest: run unread files in patches/ in filename order.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT NOT NULL AUTO_INCREMENT,
    `email`         VARCHAR(255) NOT NULL,
    `password`      VARCHAR(255) NOT NULL,

    `role`          VARCHAR(30)  NOT NULL DEFAULT 'customer',

    `status`        VARCHAR(30)  NOT NULL DEFAULT 'active',
    `deleted`       DATETIME(6)  NULL DEFAULT NULL,

    `auth_version`  INT          NOT NULL DEFAULT 1,
    `first_name`    VARCHAR(100) NULL DEFAULT NULL,
    `last_name`     VARCHAR(100) NULL DEFAULT NULL,
    `last_login_at` DATETIME(6)  NULL DEFAULT NULL,
    `created`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customers` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT          NULL DEFAULT NULL,
    `email`      VARCHAR(255) NULL DEFAULT NULL,
    `phone`      VARCHAR(30)  NULL DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name`  VARCHAR(100) NULL DEFAULT NULL,
    `company`    VARCHAR(200) NULL DEFAULT NULL,
    `status`     VARCHAR(30)  NOT NULL DEFAULT 'lead',
    `source`     VARCHAR(30)  NOT NULL DEFAULT 'web',
    `created`    DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`   DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                              ON UPDATE CURRENT_TIMESTAMP(6),
    `deleted`    DATETIME(6)  NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_customers_user` (`user_id`),
    UNIQUE KEY `uq_customers_email` (`email`),
    KEY `idx_customers_status_created` (`status`, `created`),
    CONSTRAINT `fk_customers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id`                  INT NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(128) NOT NULL,
    `email`               VARCHAR(255) NOT NULL,
    `phone`               VARCHAR(32)  NULL DEFAULT NULL,
    `subject`             VARCHAR(255) NOT NULL,
    `message`             TEXT NOT NULL,

    `is_read`             TINYINT(1)   NOT NULL DEFAULT 0,
    `status`              VARCHAR(30)  NOT NULL DEFAULT 'new',
    `reference_number`    VARCHAR(40)  NULL DEFAULT NULL,
    `customer_id`         BIGINT UNSIGNED NULL DEFAULT NULL,
    `assigned_to_user_id` INT          NULL DEFAULT NULL,
    `created`             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified`            DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                       ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contact_messages_reference` (`reference_number`),
    KEY `idx_contact_messages_read_created` (`is_read`, `created`),
    CONSTRAINT `fk_contact_messages_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_contact_messages_assigned_user`
        FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`         VARCHAR(255) NOT NULL,
    `name`         VARCHAR(250) NOT NULL,
    `product_type` VARCHAR(80)  NOT NULL DEFAULT 'fixture',
    `status`       VARCHAR(30)  NOT NULL DEFAULT 'draft',
    `created`      DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`     DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variants` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`  BIGINT UNSIGNED NOT NULL,
    `sku`         VARCHAR(255) NOT NULL,
    `name`        VARCHAR(200) NOT NULL,
    `price_cents` BIGINT       NOT NULL DEFAULT 0,
    `tax_rate`    DECIMAL(6,5) NOT NULL DEFAULT 0.10000,
    `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,

    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created`     DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`    DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                               ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_variants_sku` (`sku`),
    KEY `idx_product_variants_product` (`product_id`),
    CONSTRAINT `fk_product_variants_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_locations` (
    `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`      VARCHAR(255) NOT NULL,
    `name`      VARCHAR(160) NOT NULL,
    `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
    `created`   DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`  DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                             ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_inventory_locations_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_balances` (
    `product_variant_id`    BIGINT UNSIGNED NOT NULL,
    `inventory_location_id` BIGINT UNSIGNED NOT NULL,
    `quantity_on_hand`      INT NOT NULL DEFAULT 0,
    `quantity_reserved`     INT NOT NULL DEFAULT 0,

    `quantity_available`    INT GENERATED ALWAYS AS
                            (`quantity_on_hand` - `quantity_reserved`) STORED,

    `reorder_point`         INT NOT NULL DEFAULT 0,
    `reorder_quantity`      INT NOT NULL DEFAULT 0,
    `modified`              DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`product_variant_id`, `inventory_location_id`),
    KEY `idx_inventory_low_stock` (`quantity_available`, `reorder_point`),
    KEY `idx_inventory_balances_location` (`inventory_location_id`),
    CONSTRAINT `fk_inventory_balances_variant`
        FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`)
        ON DELETE RESTRICT,
    CONSTRAINT `fk_inventory_balances_location`
        FOREIGN KEY (`inventory_location_id`) REFERENCES `inventory_locations` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_orders` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number`       VARCHAR(190) NOT NULL,
    `customer_id`        BIGINT UNSIGNED NULL DEFAULT NULL,

    `guest_name`         VARCHAR(200) NULL DEFAULT NULL,
    `guest_email`        VARCHAR(255) NULL DEFAULT NULL,
    `guest_phone`        VARCHAR(30)  NULL DEFAULT NULL,

    `status`             VARCHAR(30)  NOT NULL DEFAULT 'draft',
    `payment_status`     VARCHAR(30)  NOT NULL DEFAULT 'pending',
    `currency`           CHAR(3)      NOT NULL DEFAULT 'AUD',
    `subtotal_cents`     BIGINT NOT NULL DEFAULT 0,
    `discount_cents`     BIGINT NOT NULL DEFAULT 0,
    `shipping_cents`     BIGINT NOT NULL DEFAULT 0,
    `tax_cents`          BIGINT NOT NULL DEFAULT 0,
    `grand_total_cents`  BIGINT NOT NULL DEFAULT 0,

    `placed_at`          DATETIME(6) NULL DEFAULT NULL,
    `source_channel`     VARCHAR(30) NOT NULL DEFAULT 'web',
    `promised_delivery_on` DATE NULL DEFAULT NULL,
    `delivery_notes`     VARCHAR(255) NULL DEFAULT NULL,
    `created_by_user_id` INT NULL DEFAULT NULL,
    `created`            DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`           DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                     ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sales_orders_number` (`order_number`),
    KEY `idx_sales_orders_status` (`status`, `payment_status`),
    KEY `idx_sales_orders_placed_at` (`placed_at`),
    KEY `idx_sales_orders_customer` (`customer_id`),
    CONSTRAINT `fk_sales_orders_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sales_orders_created_by`
        FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_bookings` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_number`     VARCHAR(190) NOT NULL,
    `customer_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
    `guest_name`         VARCHAR(200) NULL DEFAULT NULL,
    `guest_email`        VARCHAR(255) NULL DEFAULT NULL,
    `guest_phone`        VARCHAR(30)  NULL DEFAULT NULL,
    `service_type`       VARCHAR(30)  NOT NULL,
    `status`             VARCHAR(30)  NOT NULL DEFAULT 'scheduled',
    `scheduled_at`       DATETIME(6) NOT NULL,
    `notes`              VARCHAR(255) NULL DEFAULT NULL,
    `created_by_user_id` INT NULL DEFAULT NULL,
    `created`            DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`           DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                     ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_service_bookings_booking_number` (`booking_number`),
    KEY `idx_service_bookings_customer_id` (`customer_id`),
    KEY `idx_service_bookings_created_by_user_id` (`created_by_user_id`),
    CONSTRAINT `fk_service_bookings_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_service_bookings_created_by_user`
        FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sales_order_id`        BIGINT UNSIGNED NOT NULL,
    `provider`              VARCHAR(80)  NOT NULL DEFAULT 'manual',
    `provider_payment_id`   VARCHAR(255) NULL DEFAULT NULL,
    `method`                VARCHAR(30)  NOT NULL DEFAULT 'card',
    `status`                VARCHAR(30)  NOT NULL DEFAULT 'pending',
    `amount_cents`          BIGINT       NOT NULL DEFAULT 0,
    `currency`              CHAR(3)      NOT NULL DEFAULT 'AUD',
    `transaction_reference` VARCHAR(255) NULL DEFAULT NULL,
    `created`               DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`              DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                         ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_provider_payment` (`provider`, `provider_payment_id`),
    KEY `idx_payments_order` (`sales_order_id`),
    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_refunds` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id`         BIGINT UNSIGNED NOT NULL,
    `provider_refund_id` VARCHAR(255) NULL DEFAULT NULL,
    `idempotency_key`    VARCHAR(255) NOT NULL,
    `status`             VARCHAR(30)  NOT NULL DEFAULT 'pending',

    `refund_kind`        VARCHAR(40)  NOT NULL DEFAULT 'customer_refund',
    `amount_cents`       BIGINT       NOT NULL DEFAULT 0,
    `created`            DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`           DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                      ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_refunds_idempotency` (`idempotency_key`),
    UNIQUE KEY `uq_payment_refunds_provider` (`payment_id`, `provider_refund_id`),
    CONSTRAINT `fk_payment_refunds_payment`
        FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `business_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `role_key`    VARCHAR(190) NOT NULL,
    `name`        VARCHAR(160) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `is_system`   TINYINT(1) NOT NULL DEFAULT 0,

    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created`     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified`    DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                              ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_key` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `permission_key` VARCHAR(190) NOT NULL,
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module`         VARCHAR(80)  NOT NULL,
    `name`           VARCHAR(180) NOT NULL,
    `description`    TEXT NULL DEFAULT NULL,
    `risk_level`     VARCHAR(20) NOT NULL DEFAULT 'normal',
    `created`        DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`            BIGINT UNSIGNED NOT NULL,
    `permission_id`      BIGINT UNSIGNED NOT NULL,
    `granted_by_user_id` INT NULL DEFAULT NULL,
    `created`            DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permissions_permission` (`permission_id`),
    CONSTRAINT `fk_role_permissions_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_granted_by`
        FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`            INT NOT NULL,
    `role_id`            BIGINT UNSIGNED NOT NULL,
    `business_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
    `granted_by_user_id` INT NULL DEFAULT NULL,

    `starts_at`          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ends_at`            DATETIME(6) NULL DEFAULT NULL,
    `revoked_at`         DATETIME(6) NULL DEFAULT NULL,
    `created`            DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    `active_scope_key`   VARCHAR(190) GENERATED ALWAYS AS (
                             CASE WHEN `revoked_at` IS NULL
                                  THEN CONCAT(`user_id`, ':', `role_id`)
                                  ELSE NULL END
                         ) VIRTUAL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_roles_active` (`active_scope_key`),
    KEY `idx_user_roles_user` (`user_id`),
    KEY `idx_user_roles_role` (`role_id`),
    CONSTRAINT `fk_user_roles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_granted_by`
        FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_permission_overrides` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`            INT NOT NULL,
    `permission_id`      BIGINT UNSIGNED NOT NULL,

    `effect`             VARCHAR(10) NOT NULL,
    `business_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
    `reason`             TEXT NULL DEFAULT NULL,
    `granted_by_user_id` INT NULL DEFAULT NULL,
    `starts_at`          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ends_at`            DATETIME(6) NULL DEFAULT NULL,
    `created`            DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    KEY `idx_user_permission_overrides_user` (`user_id`),
    CONSTRAINT `fk_user_permission_overrides_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_permission_overrides_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_permission_overrides_granted_by`
        FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `suppliers` (
    `id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`     VARCHAR(40)  NOT NULL,
    `name`     VARCHAR(200) NOT NULL,
    `email`    VARCHAR(255) NULL DEFAULT NULL,
    `phone`    VARCHAR(30)  NULL DEFAULT NULL,
    `notes`    VARCHAR(255) NULL DEFAULT NULL,
    `created`  DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                           ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_suppliers_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_suppliers` (
    `product_id`  BIGINT UNSIGNED NOT NULL,
    `supplier_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`product_id`, `supplier_id`),
    CONSTRAINT `fk_product_suppliers_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_product_suppliers_supplier`
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

