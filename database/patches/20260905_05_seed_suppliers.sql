-- Already-running dest only. Fresh installs get this data from seed_demo.sql.

INSERT IGNORE INTO suppliers (
    code,
    name,
    email,
    phone,
    notes
) VALUES
(
    'SUP-MEL-01',
    'Brightline Wholesale',
    'orders@brightline.example',
    '0390001001',
    'Primary lighting supplier'
),
(
    'SUP-MEL-02',
    'Harbor Electrical',
    'sales@harborelec.example',
    '0390002002',
    'Controls and dimmers'
);

INSERT IGNORE INTO product_suppliers (
    product_id,
    supplier_id
)
SELECT
    p.id,
    s.id
FROM products p
INNER JOIN suppliers s
    ON s.code = 'SUP-MEL-01'
WHERE p.slug = 'ashby-twin-sconce';

INSERT IGNORE INTO product_suppliers (
    product_id,
    supplier_id
)
SELECT
    p.id,
    s.id
FROM products p
INNER JOIN suppliers s
    ON s.code = 'SUP-MEL-02'
WHERE p.slug = 'rowan-rotary-dimmer';
