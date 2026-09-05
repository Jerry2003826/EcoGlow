-- database/patches/20260905_08_alter_customers_address.sql

ALTER TABLE customers
    ADD COLUMN address_line_1 VARCHAR(255) NULL AFTER last_name,
    ADD COLUMN address_line_2 VARCHAR(255) NULL AFTER address_line_1,
    ADD COLUMN suburb VARCHAR(120) NULL AFTER address_line_2,
    ADD COLUMN state VARCHAR(120) NULL AFTER suburb,
    ADD COLUMN postcode VARCHAR(20) NULL AFTER state,
    ADD COLUMN country VARCHAR(120) NULL AFTER postcode,
    ADD COLUMN age INT NULL AFTER country;
