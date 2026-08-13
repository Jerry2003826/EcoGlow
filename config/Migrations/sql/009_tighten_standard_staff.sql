-- Tighten standard_staff role_permissions to the six keys agreed for batch 2.
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('orders.view','orders','View sales orders','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`,`created`) SELECT r.`id`, p.`id`, UTC_TIMESTAMP(6) FROM `roles` r CROSS JOIN `permissions` p WHERE r.`role_key` IN ('master','elevated_staff') AND p.`permission_key` = 'orders.view';
-- @@STATEMENT_END@@
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`,`created`) SELECT r.`id`, p.`id`, UTC_TIMESTAMP(6) FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('refunds.process','invoices.issue','orders.dispatch','payments.record','orders.view','customers.view') WHERE r.`role_key` = 'standard_staff';
-- @@STATEMENT_END@@
DELETE rp FROM `role_permissions` rp INNER JOIN `roles` r ON r.`id` = rp.`role_id` INNER JOIN `permissions` p ON p.`id` = rp.`permission_id` WHERE r.`role_key` = 'standard_staff' AND p.`permission_key` NOT IN ('refunds.process','invoices.issue','orders.dispatch','payments.record','orders.view','customers.view');
