INSERT INTO `users` (`email`, `password`, `role`, `status`, `auth_version`, `first_name`)
VALUES ('admin@ecoglow.local', '$2y$12$HoOfpO72o6tbX./qb2o8VewJECc802/ICRdeKpcY0ehloLlc4NQbK', 'owner', 'active', 1, 'Owner')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `status` = VALUES(`status`);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `starts_at`)
SELECT u.`id`, r.`id`, UTC_TIMESTAMP(6)
  FROM `users` u JOIN `roles` r ON r.`role_key` = 'master'
 WHERE u.`email` = 'admin@ecoglow.local';

