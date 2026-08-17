CREATE TABLE enquiries (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(10) NOT NULL,
    enquiry_message TEXT NOT NULL,
    created DATETIME NOT NULL
);

INSERT INTO enquiries
(first_name, last_name, email, contact_number, enquiry_message, created)
VALUES
('John', 'doe', 'john.doe@example.com', '0400000000',
 'I would like to know more about your services.',
 '2026-08-14 10:30:00'),

('Jane', 'doe', 'jane.doe@example.com', '0412345678',
 'Could you please provide more information about your pricing?',
 '2026-08-15 14:20:00');


CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    user_password VARCHAR(100) NOT NULL,
    user_role ENUM('admin', 'customer', 'staff') NOT NULL DEFAULT 'customer'
);

INSERT INTO users (username, user_password, user_role)
VALUES
('admin', 'admin1234', 'admin');