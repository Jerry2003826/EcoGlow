-- Demo catalogue, orders, bookings, and contact messages. Run after seed_admin.sql.

INSERT IGNORE INTO `inventory_locations` (`code`, `name`) VALUES ('MEL-WH','Melbourne warehouse');

INSERT IGNORE INTO `products` (`slug`, `name`, `product_type`, `status`) VALUES
  ('ashby-twin-sconce','Ashby Twin Sconce','fixture','active'),
  ('rowan-rotary-dimmer','Rowan Rotary Dimmer','control','active'),
  ('halden-pendant','Halden Pendant','fixture','active');

INSERT IGNORE INTO `product_variants` (`product_id`, `sku`, `name`, `price_cents`, `is_default`) VALUES
  ((SELECT `id` FROM `products` WHERE `slug`='ashby-twin-sconce'),  'ASH-TWN-BR','Brass',      18900, 1),
  ((SELECT `id` FROM `products` WHERE `slug`='rowan-rotary-dimmer'),'ROW-DIM-WH','White',       4900, 1),
  ((SELECT `id` FROM `products` WHERE `slug`='halden-pendant'),     'HAL-PND-OK','Oak',        24900, 1);

INSERT INTO `inventory_balances`
    (`product_variant_id`, `inventory_location_id`, `quantity_on_hand`, `quantity_reserved`, `reorder_point`, `reorder_quantity`)
SELECT pv.`id`, il.`id`, v.`on_hand`, v.`reserved`, v.`rp`, v.`rq`
  FROM (
      SELECT 'ASH-TWN-BR' AS sku, 2  AS on_hand, 0 AS reserved, 6  AS rp, 12 AS rq
      UNION ALL SELECT 'ROW-DIM-WH', 5,  1, 10, 20
      UNION ALL SELECT 'HAL-PND-OK', 40, 0, 8,  16
  ) v
  JOIN `product_variants` pv ON pv.`sku` = v.sku
  JOIN `inventory_locations` il ON il.`code` = 'MEL-WH'
ON DUPLICATE KEY UPDATE
  `quantity_on_hand` = VALUES(`quantity_on_hand`),
  `quantity_reserved` = VALUES(`quantity_reserved`),
  `reorder_point` = VALUES(`reorder_point`);

INSERT IGNORE INTO `customers` (`email`, `phone`, `first_name`, `last_name`, `status`) VALUES
  ('mira.holt@example.com','0400111222','Mira','Holt','customer'),
  ('dev.raman@example.com','0400333444','Dev','Raman','customer');

INSERT IGNORE INTO `sales_orders`
    (`order_number`, `customer_id`, `status`, `payment_status`,
     `subtotal_cents`, `tax_cents`, `grand_total_cents`, `placed_at`, `source_channel`)
VALUES
  ('SO-1001', (SELECT `id` FROM `customers` WHERE `email`='mira.holt@example.com'),
   'confirmed','paid',   17182, 1718, 18900, UTC_TIMESTAMP(6) - INTERVAL 3 DAY, 'web'),
  ('SO-1002', (SELECT `id` FROM `customers` WHERE `email`='dev.raman@example.com'),
   'processing','paid',   4455,  445,  4900, UTC_TIMESTAMP(6) - INTERVAL 2 DAY, 'phone'),
  ('SO-1003', NULL,
   'confirmed','pending', 22636, 2264, 24900, UTC_TIMESTAMP(6) - INTERVAL 1 DAY, 'in_store');

UPDATE `sales_orders` SET `guest_name` = 'Walk-in — Tess Nguyen' WHERE `order_number` = 'SO-1003';

INSERT IGNORE INTO `payments`
    (`sales_order_id`, `provider`, `provider_payment_id`, `method`, `status`,
     `amount_cents`, `transaction_reference`)
SELECT so.`id`, 'manual', CONCAT('man_', so.`order_number`), 'card', 'succeeded',
       so.`grand_total_cents`, CONCAT('RCPT-', so.`order_number`)
  FROM `sales_orders` so
 WHERE so.`order_number` IN ('SO-1001','SO-1002');

INSERT IGNORE INTO `contact_messages`
    (`reference_number`, `name`, `email`, `phone`, `subject`, `message`, `is_read`, `status`)
VALUES
  ('MSG-00000001','Priya Shah','priya.shah@example.com','0400555666',
   'Pendant height for a 3.2m ceiling',
   'Hi, which drop length do you recommend for a stairwell with a 3.2 metre ceiling?', 0, 'new'),
  ('MSG-00000002','Owen Fitzgerald','owen.f@example.com',NULL,
   'Dimmer compatibility with LED downlights',
   'Is the Rowan rotary dimmer compatible with 8W dimmable LED downlights?', 0, 'new');

INSERT IGNORE INTO `service_bookings` (
    `booking_number`,
    `customer_id`,
    `guest_name`,
    `guest_email`,
    `guest_phone`,
    `service_type`,
    `status`,
    `scheduled_at`,
    `notes`
)
VALUES
(
    'SB-2001',
    (
        SELECT `customer_id`
        FROM `sales_orders`
        WHERE `order_number` = 'SO-1001'
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

UPDATE `sales_orders`
SET
    `promised_delivery_on` = CURRENT_DATE + INTERVAL 5 DAY,
    `delivery_notes` = 'Leave at side gate'
WHERE `order_number` = 'SO-1001';

UPDATE `sales_orders`
SET
    `promised_delivery_on` = CURRENT_DATE + INTERVAL 3 DAY,
    `delivery_notes` = 'Call before delivery'
WHERE `order_number` = 'SO-1002';

