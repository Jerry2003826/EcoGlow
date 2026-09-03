INSERT INTO permissions
(
    permission_key,
    module,
    name,
    risk_level
)
SELECT
    'dashboard.view',
    'dashboard',
    'Dashboard Access',
    'normal'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE permission_key = 'dashboard.view'
);

INSERT INTO role_permissions
(
    role_id,
    permission_id
)
SELECT
    ur.role_id,
    p.id
FROM user_roles ur
INNER JOIN users u
    ON u.id = ur.user_id
INNER JOIN permissions p
    ON p.permission_key = 'dashboard.view'
WHERE u.email = 'admin@ecoglow.local'
AND NOT EXISTS (
    SELECT 1
    FROM role_permissions rp
    WHERE rp.role_id = ur.role_id
      AND rp.permission_id = p.id
);

INSERT INTO role_permissions
(
    role_id,
    permission_id
)
SELECT
    ur.role_id,
    p.id
FROM user_roles ur
INNER JOIN users u
    ON u.id = ur.user_id
INNER JOIN permissions p
    ON p.permission_key IN (
        'orders.view',
        'inventory.view',
        'inventory.adjust',
        'messages.manage',
        'reports.financial'
    )
WHERE u.email = 'admin@ecoglow.local'
AND NOT EXISTS (
    SELECT 1
    FROM role_permissions rp
    WHERE rp.role_id = ur.role_id
      AND rp.permission_id = p.id
);

INSERT INTO users
(
    email,
    password,
    role,
    status,
    deleted
)
SELECT
    'staff403@ecoglow.local',
    '$2y$10$5QY4m7aJ7Q3F5Q87D0W4L.gXxgJgk5m8Bk8xKl9KOVcL8CS1B8m5u',
    'admin',
    'active',
    NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM users
    WHERE email = 'staff403@ecoglow.local'
);

