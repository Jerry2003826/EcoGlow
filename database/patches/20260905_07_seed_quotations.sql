-- database/patches/20260905_07_seed_quotations.sql

INSERT IGNORE INTO quotations
(
    quotation_number,
    customer_id,
    status,
    valid_until,
    subtotal_cents,
    tax_cents,
    grand_total_cents
)
SELECT
    'QT-3001',
    c.id,
    'issued',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    18900,
    1890,
    20790
FROM customers c
WHERE c.email = 'mira.holt@example.com';

INSERT IGNORE INTO quotations
(
    quotation_number,
    customer_id,
    status,
    valid_until,
    subtotal_cents,
    tax_cents,
    grand_total_cents
)
SELECT
    'QT-3002',
    c.id,
    'draft',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    29800,
    2980,
    32780
FROM customers c
WHERE c.email = 'dev.raman@example.com';

INSERT IGNORE INTO quotation_items
(
    quotation_id,
    product_variant_id,
    product_name,
    sku,
    quantity,
    unit_price_cents,
    line_total_cents
)
SELECT
    q.id,
    pv.id,
    pv.name,
    pv.sku,
    1,
    18900,
    18900
FROM quotations q
INNER JOIN product_variants pv
    ON pv.sku = 'ASH-TWN-BR'
WHERE q.quotation_number = 'QT-3001';

INSERT IGNORE INTO quotation_items
(
    quotation_id,
    product_variant_id,
    product_name,
    sku,
    quantity,
    unit_price_cents,
    line_total_cents
)
SELECT
    q.id,
    pv.id,
    pv.name,
    pv.sku,
    1,
    4900,
    4900
FROM quotations q
INNER JOIN product_variants pv
    ON pv.sku = 'ROW-DIM-WH'
WHERE q.quotation_number = 'QT-3002';

INSERT IGNORE INTO quotation_items
(
    quotation_id,
    product_variant_id,
    product_name,
    sku,
    quantity,
    unit_price_cents,
    line_total_cents
)
SELECT
    q.id,
    pv.id,
    pv.name,
    pv.sku,
    1,
    24900,
    24900
FROM quotations q
INNER JOIN product_variants pv
    ON pv.sku = 'HAL-PND-OK'
WHERE q.quotation_number = 'QT-3002';
