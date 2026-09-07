-- database/patches/20260907_14_alter_suppliers_address.sql

ALTER TABLE suppliers
    ADD COLUMN address_line_1 VARCHAR(255) NULL AFTER phone,
    ADD COLUMN suburb VARCHAR(100) NULL AFTER address_line_1,
    ADD COLUMN state VARCHAR(50) NULL AFTER suburb,
    ADD COLUMN postcode VARCHAR(20) NULL AFTER state;
