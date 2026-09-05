-- Demo owner account. Password hash matches admin@ecoglow.local / ChangeMe-EcoGlow-2026-Admin!

INSERT INTO `users` (`email`, `password`, `role`, `status`, `auth_version`, `first_name`)
VALUES ('admin@ecoglow.local', '$2y$12$HoOfpO72o6tbX./qb2o8VewJECc802/ICRdeKpcY0ehloLlc4NQbK', 'owner', 'active', 1, 'Owner')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `status` = VALUES(`status`);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `starts_at`)
SELECT u.`id`, r.`id`, UTC_TIMESTAMP(6)
  FROM `users` u JOIN `roles` r ON r.`role_key` = 'master'
 WHERE u.`email` = 'admin@ecoglow.local';

-- Demo standard staff. Password hash matches staff403@ecoglow.local / ChangeMe-EcoGlow-2026-Staff!
INSERT INTO `users` (`email`, `password`, `role`, `status`, `auth_version`, `first_name`)
VALUES ('staff403@ecoglow.local', '$2y$12$Z.a1xPmzJaKeSeWxpHVJNuH5bz5Vcdl8bxHblKzVAO1Yj9N5APzKu', 'staff', 'active', 1, 'Staff')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `status` = VALUES(`status`), `password` = VALUES(`password`), `first_name` = VALUES(`first_name`);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `starts_at`)
SELECT u.`id`, r.`id`, UTC_TIMESTAMP(6)
  FROM `users` u JOIN `roles` r ON r.`role_key` = 'standard_staff'
 WHERE u.`email` = 'staff403@ecoglow.local';

