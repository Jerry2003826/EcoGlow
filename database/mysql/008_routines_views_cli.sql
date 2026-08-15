-- mysql CLI version

-- MySQL 8 routines, triggers and read-only views.
-- The CakePHP migration runner executes each marker-delimited statement as one PDO statement.

DROP PROCEDURE IF EXISTS `sp_next_document_number`;

DELIMITER $$

CREATE PROCEDURE `sp_next_document_number`(
    IN p_document_type VARCHAR(80),
    IN p_prefix VARCHAR(20),
    OUT p_document_number VARCHAR(80)
)
BEGIN
    DECLARE v_value BIGINT;
    DECLARE v_padding INT;
    DECLARE v_include_year TINYINT;
    DECLARE v_year INT;

    SET v_year = YEAR(COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), 'UTC', 'Australia/Melbourne'), UTC_TIMESTAMP()));
    INSERT INTO `document_sequences` (`document_type`, `prefix`, `next_value`, `padding`, `include_year`, `reset_annually`, `last_reset_year`, `modified`)
    VALUES (p_document_type, p_prefix, LAST_INSERT_ID(1001), 6, 1, 0, v_year, UTC_TIMESTAMP(6))
    ON DUPLICATE KEY UPDATE
        `prefix` = p_prefix,
        `next_value` = LAST_INSERT_ID(
            CASE
                WHEN `reset_annually` = 1 AND COALESCE(`last_reset_year`, v_year) <> v_year THEN 2
                ELSE `next_value` + 1
            END
        ),
        `last_reset_year` = v_year,
        `modified` = UTC_TIMESTAMP(6);

    SET v_value = LAST_INSERT_ID() - 1;
    SELECT `padding`, `include_year` INTO v_padding, v_include_year
      FROM `document_sequences` WHERE `document_type` = p_document_type;
    SET p_document_number = CONCAT(
        p_prefix,
        CASE WHEN v_include_year = 1 THEN CONCAT('-', v_year) ELSE '' END,
        '-', LPAD(v_value, v_padding, '0')
    );
END$$

DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_apply_inventory_change_in_transaction`;

DELIMITER $$

CREATE PROCEDURE `sp_apply_inventory_change_in_transaction`(
    IN p_product_variant_id BIGINT UNSIGNED,
    IN p_inventory_location_id BIGINT UNSIGNED,
    IN p_movement_type VARCHAR(40),
    IN p_on_hand_delta INT,
    IN p_reserved_delta INT,
    IN p_reference_type VARCHAR(80),
    IN p_reference_id BIGINT UNSIGNED,
    IN p_note TEXT,
    IN p_actor_user_id INT
)
BEGIN
    DECLARE v_on_hand INT;
    DECLARE v_reserved INT;

    -- TRANSACTION CONTRACT: the caller must already own an explicit transaction.
    -- CakePHP order/refund/receiving workflows should call this procedure from
    -- an application-owned transaction so the business record and stock ledger commit together.
    INSERT INTO `inventory_balances` (`product_variant_id`, `inventory_location_id`, `quantity_on_hand`, `quantity_reserved`, `reorder_point`, `reorder_quantity`, `modified`)
    VALUES (p_product_variant_id, p_inventory_location_id, 0, 0, 0, 0, UTC_TIMESTAMP(6))
    ON DUPLICATE KEY UPDATE `modified` = `modified`;

    SELECT `quantity_on_hand`, `quantity_reserved`
      INTO v_on_hand, v_reserved
      FROM `inventory_balances`
     WHERE `product_variant_id` = p_product_variant_id
       AND `inventory_location_id` = p_inventory_location_id
     FOR UPDATE;

    IF v_on_hand + p_on_hand_delta < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory on-hand would become negative';
    END IF;
    IF v_reserved + p_reserved_delta < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory reserved would become negative';
    END IF;
    IF v_reserved + p_reserved_delta > v_on_hand + p_on_hand_delta THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reserved inventory cannot exceed on-hand inventory';
    END IF;
    IF p_on_hand_delta = 0 AND p_reserved_delta = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory movement must change on-hand or reserved quantity';
    END IF;

    UPDATE `inventory_balances`
       SET `quantity_on_hand` = `quantity_on_hand` + p_on_hand_delta,
           `quantity_reserved` = `quantity_reserved` + p_reserved_delta,
           `modified` = UTC_TIMESTAMP(6)
     WHERE `product_variant_id` = p_product_variant_id
       AND `inventory_location_id` = p_inventory_location_id;

    INSERT INTO `inventory_movements` (
        `product_variant_id`, `inventory_location_id`, `movement_type`,
        `on_hand_delta`, `reserved_delta`, `reference_type`, `reference_id`,
        `note`, `created_by_user_id`, `created`
    ) VALUES (
        p_product_variant_id, p_inventory_location_id, p_movement_type,
        p_on_hand_delta, p_reserved_delta, p_reference_type, p_reference_id,
        p_note, p_actor_user_id, UTC_TIMESTAMP(6)
    );
END$$

DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_apply_inventory_change`;

DELIMITER $$

CREATE PROCEDURE `sp_apply_inventory_change`(
    IN p_product_variant_id BIGINT UNSIGNED,
    IN p_inventory_location_id BIGINT UNSIGNED,
    IN p_movement_type VARCHAR(40),
    IN p_on_hand_delta INT,
    IN p_reserved_delta INT,
    IN p_reference_type VARCHAR(80),
    IN p_reference_id BIGINT UNSIGNED,
    IN p_note TEXT,
    IN p_actor_user_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Standalone wrapper for maintenance jobs, smoke tests and one-step adjustments.
    -- Never call this wrapper inside an application-owned transaction: use the
    -- _in_transaction procedure there, otherwise START TRANSACTION would end the
    -- caller's current transaction because MySQL transactions are not nested.
    START TRANSACTION;
    CALL `sp_apply_inventory_change_in_transaction`(
        p_product_variant_id, p_inventory_location_id, p_movement_type,
        p_on_hand_delta, p_reserved_delta, p_reference_type, p_reference_id,
        p_note, p_actor_user_id
    );
    COMMIT;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `trg_sales_orders_status_history`;

DELIMITER $$

CREATE TRIGGER `trg_sales_orders_status_history`
AFTER UPDATE ON `sales_orders`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`status` <=> NEW.`status`) THEN
        INSERT INTO `order_status_history` (`sales_order_id`, `from_status`, `to_status`, `created`)
        VALUES (NEW.`id`, OLD.`status`, NEW.`status`, UTC_TIMESTAMP(6));
    END IF;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `trg_contact_messages_reference`;

DELIMITER $$

CREATE TRIGGER `trg_contact_messages_reference`
BEFORE INSERT ON `contact_messages`
FOR EACH ROW
BEGIN
    DECLARE v_sequence BIGINT;
    DECLARE v_year INT;
    IF NEW.`reference_number` IS NULL OR NEW.`reference_number` = '' THEN
        SET v_year = YEAR(COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), 'UTC', 'Australia/Melbourne'), UTC_TIMESTAMP()));
        INSERT INTO `document_sequences` (`document_type`, `prefix`, `next_value`, `padding`, `include_year`, `reset_annually`, `last_reset_year`, `modified`)
        VALUES ('contact_message', 'MSG', LAST_INSERT_ID(1001), 6, 1, 0, v_year, UTC_TIMESTAMP(6))
        ON DUPLICATE KEY UPDATE `next_value` = LAST_INSERT_ID(`next_value` + 1), `modified` = UTC_TIMESTAMP(6);
        SET v_sequence = LAST_INSERT_ID() - 1;
        SET NEW.`reference_number` = CONCAT('MSG-', v_year, '-', LPAD(v_sequence, 6, '0'));
    END IF;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `trg_contact_messages_status_sync`;

DELIMITER $$

CREATE TRIGGER `trg_contact_messages_status_sync`
BEFORE UPDATE ON `contact_messages`
FOR EACH ROW
BEGIN
    IF NOT (OLD.`status` <=> NEW.`status`) THEN
        IF NEW.`status` IN ('resolved', 'closed', 'spam') THEN
            SET NEW.`is_read` = 1;
            IF NEW.`resolved_at` IS NULL AND NEW.`status` IN ('resolved', 'closed') THEN
                SET NEW.`resolved_at` = UTC_TIMESTAMP(6);
            END IF;
        ELSEIF NEW.`status` = 'new' THEN
            SET NEW.`is_read` = 0;
            SET NEW.`resolved_at` = NULL;
        ELSE
            SET NEW.`is_read` = 1;
        END IF;
    ELSEIF OLD.`is_read` <> NEW.`is_read` AND NEW.`is_read` = 1 AND NEW.`status` = 'new' THEN
        SET NEW.`status` = 'in_progress';
    END IF;
END$$

DELIMITER ;

DROP VIEW IF EXISTS `v_public_product_catalogue`;

CREATE VIEW `v_public_product_catalogue` AS
SELECT
    p.`id` AS `product_id`, p.`slug`, p.`name`, p.`short_description`, p.`description`,
    p.`product_type`, p.`installation_available`, p.`smart_compatible`, p.`specifications`,
    pv.`id` AS `product_variant_id`, pv.`sku`, pv.`name` AS `variant_name`, pv.`attributes`,
    pv.`price_cents`, pv.`compare_at_price_cents`, pv.`tax_rate`,
    c.`id` AS `category_id`, c.`name` AS `category_name`, c.`slug` AS `category_slug`,
    pi.`image_url`, pi.`alt_text`,
    COALESCE(SUM(ib.`quantity_available`), 0) AS `quantity_available`
FROM `products` p
JOIN `product_variants` pv ON pv.`product_id` = p.`id` AND pv.`is_active` = 1
LEFT JOIN `categories` c ON c.`id` = p.`category_id`
LEFT JOIN `product_images` pi ON pi.`product_id` = p.`id` AND pi.`is_primary` = 1
LEFT JOIN `inventory_balances` ib ON ib.`product_variant_id` = pv.`id`
WHERE p.`status` = 'active'
GROUP BY p.`id`, pv.`id`, c.`id`, pi.`id`;

DROP VIEW IF EXISTS `v_low_stock_items`;

CREATE VIEW `v_low_stock_items` AS
SELECT
    ib.`product_variant_id`, ib.`inventory_location_id`, p.`name` AS `product_name`,
    pv.`name` AS `variant_name`, pv.`sku`, il.`name` AS `location_name`,
    ib.`quantity_on_hand`, ib.`quantity_reserved`, ib.`quantity_available`,
    ib.`reorder_point`, ib.`reorder_quantity`
FROM `inventory_balances` ib
JOIN `product_variants` pv ON pv.`id` = ib.`product_variant_id`
JOIN `products` p ON p.`id` = pv.`product_id`
JOIN `inventory_locations` il ON il.`id` = ib.`inventory_location_id`
WHERE ib.`quantity_available` <= ib.`reorder_point` AND pv.`is_active` = 1;

DROP VIEW IF EXISTS `v_invoice_balances`;

CREATE VIEW `v_invoice_balances` AS
SELECT
    i.`id`, i.`invoice_number`, i.`customer_id`, i.`status`, i.`issue_date`, i.`due_date`,
    i.`grand_total_cents`, COALESCE(i.`amount_paid_cents`, 0) AS `amount_paid_cents`,
    COALESCE(i.`credit_applied_cents`, 0) AS `credit_applied_cents`,
    GREATEST(i.`balance_due_cents`, 0) AS `balance_due_cents`
FROM `invoices` i;

DROP VIEW IF EXISTS `v_order_profitability`;

CREATE VIEW `v_order_profitability` AS
SELECT
    so.`id` AS `sales_order_id`, so.`order_number`, so.`customer_id`, so.`placed_at`, so.`status`,
    so.`payment_status`, so.`grand_total_cents`, so.`tax_cents`,
    CASE
        WHEN so.`status` = 'cancelled' OR so.`payment_status` = 'refunded' THEN 0
        ELSE COALESCE(SUM(COALESCE(soi.`cost_snapshot_cents`, 0) * soi.`quantity`), 0)
    END AS `cogs_cents`,
    CASE
        WHEN so.`status` = 'cancelled' OR so.`payment_status` = 'refunded' THEN 0
        ELSE (so.`grand_total_cents` - so.`tax_cents`
          - COALESCE(SUM(COALESCE(soi.`cost_snapshot_cents`, 0) * soi.`quantity`), 0)
          - COALESCE(MAX(rf.`refunded_cents`), 0))
    END AS `estimated_gross_profit_cents`
FROM `sales_orders` so
LEFT JOIN `sales_order_items` soi ON soi.`sales_order_id` = so.`id`
LEFT JOIN (
    SELECT p.`sales_order_id`,
           SUM(CASE WHEN pr.`status` IN ('succeeded', 'completed') THEN pr.`amount_cents` ELSE 0 END) AS `refunded_cents`
      FROM `payments` p
      INNER JOIN `payment_refunds` pr ON pr.`payment_id` = p.`id`
     GROUP BY p.`sales_order_id`
) rf ON rf.`sales_order_id` = so.`id`
GROUP BY so.`id`;

DROP VIEW IF EXISTS `v_customer_360_summary`;

CREATE VIEW `v_customer_360_summary` AS
SELECT
    c.`id` AS `customer_id`, c.`first_name`, c.`last_name`, c.`email`, c.`phone`, c.`status`,
    COALESCE(os.`order_count`, 0) AS `order_count`,
    COALESCE(os.`lifetime_order_value_cents`, 0) AS `lifetime_order_value_cents`,
    os.`last_order_at`,
    COALESCE(cs.`open_contact_count`, 0) AS `open_contact_count`
FROM `customers` c
LEFT JOIN (
    SELECT so.`customer_id`, COUNT(*) AS `order_count`,
           SUM(CASE
               WHEN so.`status` = 'cancelled' OR so.`payment_status` = 'refunded' THEN 0
               ELSE GREATEST(0, so.`grand_total_cents` - COALESCE(rf.`refunded_cents`, 0))
           END) AS `lifetime_order_value_cents`,
           MAX(so.`placed_at`) AS `last_order_at`
      FROM `sales_orders` so
      LEFT JOIN (
          SELECT p.`sales_order_id`,
                 SUM(CASE WHEN pr.`status` IN ('succeeded', 'completed') THEN pr.`amount_cents` ELSE 0 END) AS `refunded_cents`
            FROM `payments` p
            INNER JOIN `payment_refunds` pr ON pr.`payment_id` = p.`id`
           GROUP BY p.`sales_order_id`
      ) rf ON rf.`sales_order_id` = so.`id`
     WHERE so.`customer_id` IS NOT NULL
     GROUP BY so.`customer_id`
) os ON os.`customer_id` = c.`id`
LEFT JOIN (
    SELECT `customer_id`, SUM(CASE WHEN `status` IN ('new', 'in_progress') THEN 1 ELSE 0 END) AS `open_contact_count`
      FROM `contact_messages` WHERE `customer_id` IS NOT NULL GROUP BY `customer_id`
) cs ON cs.`customer_id` = c.`id`
WHERE c.`deleted` IS NULL;

DROP VIEW IF EXISTS `v_business_dashboard_daily`;

CREATE VIEW `v_business_dashboard_daily` AS
SELECT
    DATE(COALESCE(CONVERT_TZ(so.`placed_at`, 'UTC', 'Australia/Melbourne'), so.`placed_at`)) AS `business_date`,
    COUNT(*) AS `orders_total`,
    SUM(CASE WHEN so.`status` = 'completed' THEN 1 ELSE 0 END) AS `orders_completed`,
    SUM(CASE WHEN so.`status` = 'cancelled' THEN 1 ELSE 0 END) AS `orders_cancelled`,
    COALESCE(SUM(CASE
        WHEN so.`status` = 'cancelled' OR so.`payment_status` = 'refunded' THEN 0
        ELSE GREATEST(0, so.`grand_total_cents` - COALESCE(rf.`refunded_cents`, 0))
    END), 0) AS `gross_sales_cents`,
    COALESCE(SUM(CASE
        WHEN so.`status` = 'cancelled' OR so.`payment_status` = 'refunded' THEN 0
        WHEN so.`grand_total_cents` <= 0 THEN 0
        ELSE GREATEST(0, so.`tax_cents` - CAST(
            so.`tax_cents` * COALESCE(rf.`refunded_cents`, 0) / so.`grand_total_cents` AS SIGNED
        ))
    END), 0) AS `tax_cents`
FROM `sales_orders` so
LEFT JOIN (
    SELECT p.`sales_order_id`,
           SUM(CASE WHEN pr.`status` IN ('succeeded', 'completed') THEN pr.`amount_cents` ELSE 0 END) AS `refunded_cents`
      FROM `payments` p
      INNER JOIN `payment_refunds` pr ON pr.`payment_id` = p.`id`
     GROUP BY p.`sales_order_id`
) rf ON rf.`sales_order_id` = so.`id`
WHERE so.`placed_at` IS NOT NULL
GROUP BY DATE(COALESCE(CONVERT_TZ(so.`placed_at`, 'UTC', 'Australia/Melbourne'), so.`placed_at`));

DROP VIEW IF EXISTS `v_recent_transactions`;

CREATE VIEW `v_recent_transactions` AS
SELECT 'order' AS `transaction_type`, so.`id` AS `transaction_id`, so.`customer_id`, so.`order_number` AS `reference_number`, so.`grand_total_cents` AS `amount_cents`, so.`status`, COALESCE(so.`placed_at`, so.`created`) AS `occurred_at`
FROM `sales_orders` so
UNION ALL
SELECT 'payment', p.`id`, so.`customer_id`, COALESCE(p.`transaction_reference`, p.`provider_payment_id`), p.`amount_cents`, p.`status`, p.`created`
FROM `payments` p JOIN `sales_orders` so ON so.`id` = p.`sales_order_id`
UNION ALL
SELECT 'refund', pr.`id`, so.`customer_id`, COALESCE(pr.`provider_refund_id`, pr.`idempotency_key`), -pr.`amount_cents`, pr.`status`, pr.`created`
FROM `payment_refunds` pr JOIN `payments` p ON p.`id` = pr.`payment_id` JOIN `sales_orders` so ON so.`id` = p.`sales_order_id`;
