INSERT INTO roles
(
    id,
    code,
    name,
    created,
    modified
)
VALUES
(
    1,
    'master',
    'Master',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    2,
    'elevated_staff',
    'Elevated Staff',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    3,
    'standard_staff',
    'Standard Staff',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    4,
    'customer',
    'Customer',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
);

INSERT INTO permissions
(
    permission_key,
    description,
    created,
    modified
)
VALUES
(
    'dashboard.view',
    'Dashboard access',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'orders.view',
    'View orders',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'orders.manage',
    'Manage orders',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'shipments.view',
    'View shipments',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'shipments.manage',
    'Manage shipments',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'products.view',
    'View products',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'products.manage',
    'Manage products',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'inventory.view',
    'View inventory',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'inventory.adjust',
    'Adjust inventory',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'customers.view',
    'View customers',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'customers.manage',
    'Manage customers',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'invoices.view',
    'View invoices',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'invoices.issue',
    'Issue invoices',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'payments.record',
    'Record payments',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'refunds.process',
    'Process refunds',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'messages.manage',
    'Manage messages',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'reports.financial',
    'Financial reports',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'reports.sales',
    'Sales reports',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
),
(
    'settings.manage',
    'Manage settings',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
);

INSERT INTO role_permissions
(
    role_id,
    permission_id,
    created
)
SELECT
    1,
    p.id,
    UTC_TIMESTAMP()
FROM permissions p;

INSERT INTO role_permissions
(
    role_id,
    permission_id,
    created
)
SELECT
    2,
    p.id,
    UTC_TIMESTAMP()
FROM permissions p;

INSERT INTO role_permissions
(
    role_id,
    permission_id,
    created
)
SELECT
    3,
    p.id,
    UTC_TIMESTAMP()
FROM permissions p
WHERE p.permission_key IN (
    'dashboard.view',
    'orders.view',
    'shipments.manage',
    'invoices.issue',
    'payments.record',
    'refunds.process',
    'customers.view'
);

INSERT INTO user_roles
(
    user_id,
    role_id,
    starts_at,
    ends_at,
    revoked_at,
    created,
    modified
)
SELECT
    u.id,
    1,
    UTC_TIMESTAMP(),
    NULL,
    NULL,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM users u
WHERE u.email = 'master@example.com';

INSERT INTO user_roles
(
    user_id,
    role_id,
    starts_at,
    ends_at,
    revoked_at,
    created,
    modified
)
SELECT
    u.id,
    2,
    UTC_TIMESTAMP(),
    NULL,
    NULL,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM users u
WHERE u.email = 'manager@example.com';

INSERT INTO user_roles
(
    user_id,
    role_id,
    starts_at,
    ends_at,
    revoked_at,
    created,
    modified
)
SELECT
    u.id,
    3,
    UTC_TIMESTAMP(),
    NULL,
    NULL,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM users u
WHERE u.email = 'staff@example.com';

