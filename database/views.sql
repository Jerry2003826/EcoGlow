-- Reporting views used by the dest dashboard. Run after schema.sql.

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
           SUM(CASE WHEN pr.`status` IN ('succeeded', 'completed')
                     AND COALESCE(pr.`refund_kind`, 'customer_refund')
                         IN ('customer_refund', 'partial_customer_refund')
                    THEN pr.`amount_cents` ELSE 0 END) AS `refunded_cents`
      FROM `payments` p
      INNER JOIN `payment_refunds` pr ON pr.`payment_id` = p.`id`
     GROUP BY p.`sales_order_id`
) rf ON rf.`sales_order_id` = so.`id`
WHERE so.`placed_at` IS NOT NULL
GROUP BY DATE(COALESCE(CONVERT_TZ(so.`placed_at`, 'UTC', 'Australia/Melbourne'), so.`placed_at`));

DROP VIEW IF EXISTS `v_recent_transactions`;
CREATE VIEW `v_recent_transactions` AS
SELECT 'order' AS `transaction_type`, so.`id` AS `transaction_id`, so.`customer_id`,
       so.`order_number` AS `reference_number`, so.`grand_total_cents` AS `amount_cents`,
       so.`status`, COALESCE(so.`placed_at`, so.`created`) AS `occurred_at`
FROM `sales_orders` so
UNION ALL
SELECT 'payment', p.`id`, so.`customer_id`,
       COALESCE(p.`transaction_reference`, p.`provider_payment_id`),
       p.`amount_cents`, p.`status`, p.`created`
FROM `payments` p JOIN `sales_orders` so ON so.`id` = p.`sales_order_id`
UNION ALL
SELECT 'refund', pr.`id`, so.`customer_id`,
       COALESCE(pr.`provider_refund_id`, pr.`idempotency_key`),
       -pr.`amount_cents`, pr.`status`, pr.`created`
FROM `payment_refunds` pr
JOIN `payments` p ON p.`id` = pr.`payment_id`
JOIN `sales_orders` so ON so.`id` = p.`sales_order_id`;

