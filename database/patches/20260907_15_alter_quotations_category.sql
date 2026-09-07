-- database/patches/20260907_15_alter_quotations_category.sql

ALTER TABLE quotations
    ADD COLUMN category VARCHAR(30) NULL AFTER customer_id,
    ADD COLUMN emailed_at DATETIME(6) NULL AFTER category;
