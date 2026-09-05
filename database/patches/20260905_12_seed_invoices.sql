-- database/patches/20260905_12_seed_invoices.sql

INSERT IGNORE INTO invoices (
    invoice_number,
    sales_order_id,
    customer_id,
    status,
    source_channel,
    subtotal_cents,
    discount_cents,
    tax_cents,
    grand_total_cents
)
SELECT
    'INV-4001',
    so.id,
    so.customer_id,
    'issued',
    so.source_channel,
    so.subtotal_cents,
    so.discount_cents,
    so.tax_cents,
    18900
FROM sales_orders so
WHERE so.order_number = 'SO-1001';

INSERT IGNORE INTO invoices (
    invoice_number,
    sales_order_id,
    customer_id,
    status,
    source_channel,
    subtotal_cents,
    discount_cents,
    tax_cents,
    grand_total_cents
)
SELECT
    'INV-4002',
    so.id,
    so.customer_id,
    'issued',
    so.source_channel,
    so.subtotal_cents,
    so.discount_cents,
    so.tax_cents,
    4900
FROM sales_orders so
WHERE so.order_number = 'SO-1002';
