-- Already-running dest only. Fresh installs already have these columns in schema.sql.

ALTER TABLE sales_orders
    ADD COLUMN promised_delivery_on DATE NULL AFTER source_channel,
    ADD COLUMN delivery_notes VARCHAR(255) NULL AFTER promised_delivery_on;
