-- database/patches/20260905_09_seed_customer_addresses.sql

UPDATE customers
SET
    phone = '0400111222',
    address_line_1 = '15 Collins Street',
    suburb = 'Melbourne',
    state = 'VIC',
    postcode = '3000',
    country = 'Australia',
    age = 34
WHERE email = 'mira.holt@example.com';

UPDATE customers
SET
    phone = '0400333444',
    address_line_1 = '88 Bourke Street',
    suburb = 'Melbourne',
    state = 'VIC',
    postcode = '3000',
    country = 'Australia',
    age = 41
WHERE email = 'dev.raman@example.com';
