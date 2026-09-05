-- database/patches/20260905_10_seed_standard_staff.sql
-- Demo standard staff. Password hash matches staff403@ecoglow.local / ChangeMe-EcoGlow-2026-Staff!

INSERT INTO users (
    email,
    password,
    first_name,
    role,
    status
)
VALUES (
    'staff403@ecoglow.local',
    '$2y$12$Z.a1xPmzJaKeSeWxpHVJNuH5bz5Vcdl8bxHblKzVAO1Yj9N5APzKu',
    'Staff',
    'staff',
    'active'
)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    first_name = VALUES(first_name),
    role = VALUES(role),
    status = VALUES(status);

DELETE ur
FROM user_roles ur
INNER JOIN users u
    ON u.id = ur.user_id
WHERE u.email = 'staff403@ecoglow.local';

INSERT INTO user_roles (
    user_id,
    role_id,
    starts_at
)
SELECT
    u.id,
    r.id,
    UTC_TIMESTAMP(6)
FROM users u
INNER JOIN roles r
    ON r.role_key = 'standard_staff'
WHERE u.email = 'staff403@ecoglow.local';

INSERT INTO role_permissions (
    role_id,
    permission_id
)
SELECT
    r.id,
    p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'dashboard.view'
WHERE r.role_key = 'standard_staff'
ON DUPLICATE KEY UPDATE
    permission_id = VALUES(permission_id);

DELETE rpi
FROM role_permissions rpi
INNER JOIN roles r
    ON r.id = rpi.role_id
INNER JOIN permissions p
    ON p.id = rpi.permission_id
WHERE r.role_key = 'standard_staff'
  AND p.permission_key IN (
      'inventory.view',
      'inventory.adjust',
      'purchasing.manage',
      'customers.edit',
      'customers.sensitive.view'
  );
