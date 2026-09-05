-- database/patches/20260905_11_schema_invoices.sql

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(190) NOT NULL,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'issued',
    source_channel VARCHAR(50) NULL,
    subtotal_cents INT NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    tax_cents INT NOT NULL DEFAULT 0,
    grand_total_cents INT NOT NULL DEFAULT 0,
    issued_by_user_id INT NULL,
    created DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    modified DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_invoice_number (invoice_number),
    KEY idx_invoice_sales_order (sales_order_id),
    CONSTRAINT fk_invoice_sales_order
        FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
    CONSTRAINT fk_invoice_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_invoice_issued_by
        FOREIGN KEY (issued_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);
