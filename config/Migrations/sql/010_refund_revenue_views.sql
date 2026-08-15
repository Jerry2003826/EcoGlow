DROP VIEW IF EXISTS `v_order_profitability`;
-- @@STATEMENT_END@@
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
-- @@STATEMENT_END@@

DROP VIEW IF EXISTS `v_customer_360_summary`;
-- @@STATEMENT_END@@
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
-- @@STATEMENT_END@@

DROP VIEW IF EXISTS `v_business_dashboard_daily`;
-- @@STATEMENT_END@@
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
-- @@STATEMENT_END@@
