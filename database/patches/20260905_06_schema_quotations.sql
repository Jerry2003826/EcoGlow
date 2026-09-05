-- database/patches/20260905_06_schema_quotations.sql

CREATE TABLE IF NOT EXISTS quotations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quotation_number VARCHAR(190) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    valid_until DATE NULL,
    notes VARCHAR(255) NULL,
    subtotal_cents INT NOT NULL DEFAULT 0,
    tax_cents INT NOT NULL DEFAULT 0,
    grand_total_cents INT NOT NULL DEFAULT 0,
    created_by_user_id INT NULL,
    created DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    modified DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_quotations_number (quotation_number),
    KEY idx_quotations_customer (customer_id),
    CONSTRAINT fk_quotations_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_quotations_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS quotation_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(190) NOT NULL,
    quantity INT NOT NULL,
    unit_price_cents INT NOT NULL,
    line_total_cents INT NOT NULL,
    created DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    modified DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_qi_quotation (quotation_id),
    CONSTRAINT fk_qi_quotation
        FOREIGN KEY (quotation_id) REFERENCES quotations(id)
        ON DELETE CASCADE
);
