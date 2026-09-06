-- dest leftover onboarding columns. Fresh schema.sql does not have these.
-- ALTER is safe to re-run. Skip this file on a new schema.sql install.

ALTER TABLE users
    MODIFY username VARCHAR(100) NULL,
    MODIFY user_password VARCHAR(250) NULL;

UPDATE users
SET
    username = COALESCE(NULLIF(TRIM(username), ''), email),
    user_password = COALESCE(NULLIF(user_password, ''), password),
    user_role = 'staff',
    role = 'staff',
    status = 'active'
WHERE email = 'staff403@ecoglow.local';
