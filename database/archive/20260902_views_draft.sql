-- Archived draft. Do not run. Live views are in database/views.sql.

DROP VIEW IF EXISTS v_recent_transactions;
DROP VIEW IF EXISTS v_low_stock_items;
DROP VIEW IF EXISTS v_business_dashboard_daily;

CREATE VIEW v_business_dashboard_daily AS
SELECT
    business_day,
    COUNT(*) AS orders_count,
    SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
    SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
    SUM(net_sales_cents) AS net_sales_cents,
    SUM(gst_cents) AS gst_cents
FROM (
    SELECT
        DATE(
            COALESCE(
                CONVERT_TZ(
                    so.created,
                    '+00:00',
                    'Australia/Melbourne'
                ),
                so.created
            )
        ) AS business_day,
        so.status AS order_status,
        so.total_cents AS net_sales_cents,
        so.gst_cents AS gst_cents
    FROM sales_orders so
    WHERE so.status <> 'internal_reversal'

    UNION ALL

    SELECT
        DATE(
            COALESCE(
                CONVERT_TZ(
                    r.created,
                    '+00:00',
                    'Australia/Melbourne'
                ),
                r.created
            )
        ) AS business_day,
        'refund' AS order_status,
        (-1 * r.amount_cents) AS net_sales_cents,
        (
            -1 * ROUND(
                r.amount_cents *
                (
                    COALESCE(so.gst_cents, 0)
                    / NULLIF(so.total_cents, 0)
                )
            )
        ) AS gst_cents
    FROM refunds r
    INNER JOIN sales_orders so
        ON so.id = r.order_id
    WHERE r.status = 'processed'
) dashboard_source
GROUP BY business_day;

CREATE VIEW v_low_stock_items AS
SELECT
    ib.id AS inventory_balance_id,
    pv.id AS product_variant_id,
    pv.sku,
    pv.name,
    ib.warehouse_id,
    ib.available_quantity,
    pv.reorder_point
FROM inventory_balances ib
INNER JOIN product_variants pv
    ON pv.id = ib.product_variant_id
WHERE
    pv.is_active = 1
    AND ib.available_quantity < pv.reorder_point;

CREATE VIEW v_recent_transactions AS
SELECT
    CONCAT('order-', so.id) AS transaction_key,
    'order' AS transaction_type,
    so.id AS source_id,
    so.created AS occurred_at,
    so.order_number AS reference_number,
    so.total_cents AS amount_cents
FROM sales_orders so

UNION ALL

SELECT
    CONCAT('payment-', p.id) AS transaction_key,
    'payment' AS transaction_type,
    p.id AS source_id,
    p.created AS occurred_at,
    p.reference_number,
    p.amount_cents
FROM payments p

UNION ALL

SELECT
    CONCAT('refund-', r.id) AS transaction_key,
    'refund' AS transaction_type,
    r.id AS source_id,
    r.created AS occurred_at,
    r.reference_number,
    (-1 * r.amount_cents) AS amount_cents
FROM refunds r;

