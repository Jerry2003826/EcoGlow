-- Archived draft. Do not run. Live demo seed is in database/seed_demo.sql.

INSERT INTO contact_messages
(id, name, email, subject, is_read, created)
VALUES
(1, 'Customer A', 'a@example.com', 'Stock enquiry', 0, UTC_TIMESTAMP()),
(2, 'Customer B', 'b@example.com', 'Warranty request', 0, UTC_TIMESTAMP()),
(3, 'Customer C', 'c@example.com', 'Shipping delay', 0, UTC_TIMESTAMP()),
(4, 'Customer D', 'd@example.com', 'Invoice copy', 1, UTC_TIMESTAMP());

INSERT INTO product_variants
(
    id,
    sku,
    name,
    reorder_point,
    is_active
)
VALUES
(1, 'EGL-BULB-001', 'Eco Bulb 9W', 10, 1),
(2, 'EGL-DOWN-002', 'Down Light', 5, 1);

INSERT INTO warehouses
(
    id,
    code,
    name
)
VALUES
(1, 'MAIN', 'Main Warehouse');

INSERT INTO inventory_balances
(
    id,
    product_variant_id,
    warehouse_id,
    on_hand,
    reserved
)
VALUES
(1, 1, 1, 7, 1),
(2, 2, 1, 3, 0);

INSERT INTO sales_orders
(
    id,
    order_number,
    status,
    total_cents,
    gst_cents,
    created
)
VALUES
(1, 'SO-10001', 'completed', 125500, 11409, UTC_TIMESTAMP()),
(2, 'SO-10002', 'pending_shipment', 87500, 7955, UTC_TIMESTAMP()),
(3, 'SO-10003', 'completed', 159900, 14536, UTC_TIMESTAMP());

INSERT INTO payments
(
    id,
    order_id,
    reference_number,
    amount_cents,
    created
)
VALUES
(1, 1, 'PAY-10001', 125500, UTC_TIMESTAMP()),
(2, 2, 'PAY-10002', 87500, UTC_TIMESTAMP());

INSERT INTO refunds
(
    id,
    order_id,
    reference_number,
    amount_cents,
    status,
    created
)
VALUES
(1, 1, 'REF-10001', 1500, 'processed', UTC_TIMESTAMP());

