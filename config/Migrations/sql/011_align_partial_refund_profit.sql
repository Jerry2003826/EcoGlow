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
        ELSE (
            GREATEST(0, so.`grand_total_cents` - COALESCE(MAX(rf.`refunded_cents`), 0))
            - CASE
                WHEN so.`grand_total_cents` <= 0 THEN 0
                ELSE GREATEST(0, so.`tax_cents` - CAST(
                    so.`tax_cents` * COALESCE(MAX(rf.`refunded_cents`), 0)
                    / so.`grand_total_cents` AS SIGNED
                ))
              END
            - COALESCE(SUM(COALESCE(soi.`cost_snapshot_cents`, 0) * soi.`quantity`), 0)
        )
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
