-- database/seed_service_bookings.sql

INSERT IGNORE INTO service_bookings (
    booking_number,
    customer_id,
    guest_name,
    guest_email,
    guest_phone,
    service_type,
    status,
    scheduled_at,
    notes
)
VALUES
(
    'SB-2001',
    (
        SELECT customer_id
        FROM sales_orders
        WHERE order_number = 'SO-1001'
        LIMIT 1
    ),
    NULL,
    NULL,
    NULL,
    'installation',
    'scheduled',
    UTC_TIMESTAMP() + INTERVAL 1 DAY,
    'Installation booking'
),
(
    'SB-2002',
    NULL,
    'Walk-in — Tess Nguyen',
    NULL,
    NULL,
    'repair',
    'scheduled',
    UTC_TIMESTAMP() + INTERVAL 2 DAY,
    'Repair assessment'
);

UPDATE sales_orders
SET
    promised_delivery_on = CURRENT_DATE + INTERVAL 5 DAY,
    delivery_notes = 'Leave at side gate'
WHERE order_number = 'SO-1001';

UPDATE sales_orders
SET
    promised_delivery_on = CURRENT_DATE + INTERVAL 3 DAY,
    delivery_notes = 'Call before delivery'
WHERE order_number = 'SO-1002';
