-- Already-running dest only. Fresh installs already have this table in schema.sql.

CREATE TABLE IF NOT EXISTS service_bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_number VARCHAR(190) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(200) NULL,
    guest_email VARCHAR(255) NULL,
    guest_phone VARCHAR(30) NULL,
    service_type VARCHAR(30) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
    scheduled_at DATETIME(6) NOT NULL,
    notes VARCHAR(255) NULL,
    created_by_user_id INT NULL,
    created DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    modified DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_bookings_booking_number (booking_number),
    KEY idx_service_bookings_customer_id (customer_id),
    KEY idx_service_bookings_created_by_user_id (created_by_user_id),
    CONSTRAINT fk_service_bookings_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_service_bookings_created_by_user
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
