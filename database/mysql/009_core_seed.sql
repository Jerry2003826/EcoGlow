-- Core MySQL seed: idempotent roles, permissions, settings and feature flags.
-- @@STATEMENT_END@@
INSERT INTO `businesses` (`name`, `legal_name`, `trading_name`, `default_currency`, `timezone`, `locale`, `is_active`) SELECT 'Eco Glow Lighting', 'Eco Glow Lighting', 'Eco Glow Lighting', 'AUD', 'Australia/Melbourne', 'en-AU', 1 WHERE NOT EXISTS (SELECT 1 FROM `businesses` WHERE `name`='Eco Glow Lighting');
-- @@STATEMENT_END@@
INSERT INTO `roles` (`business_id`,`role_key`,`name`,`description`,`is_system`,`is_active`) SELECT NULL,'master','Master access','Product Owner full access',1,1 WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `business_id` IS NULL AND `role_key`='master');
-- @@STATEMENT_END@@
INSERT INTO `roles` (`business_id`,`role_key`,`name`,`description`,`is_system`,`is_active`) SELECT NULL,'elevated_staff','Elevated staff','Nominated staff with near/PO-equivalent access',1,1 WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `business_id` IS NULL AND `role_key`='elevated_staff');
-- @@STATEMENT_END@@
INSERT INTO `roles` (`business_id`,`role_key`,`name`,`description`,`is_system`,`is_active`) SELECT NULL,'standard_staff','Standard staff','Restricted operational access',1,1 WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `business_id` IS NULL AND `role_key`='standard_staff');
-- @@STATEMENT_END@@
INSERT INTO `roles` (`business_id`,`role_key`,`name`,`description`,`is_system`,`is_active`) SELECT NULL,'customer','Customer','Customer portal access',1,1 WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `business_id` IS NULL AND `role_key`='customer');
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('access.manage','access','Manage staff access and roles','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('customers.view','customers','View customer records','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('customers.edit','customers','Edit customer records','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('customers.sensitive.view','customers','View age/date-of-birth fields','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('customers.export','customers','Export customer data','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('messages.manage','messages','Read, assign, reply to and close enquiries','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('catalogue.manage','catalogue','Manage categories, products, variants and media','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('pricing.manage','pricing','Manage prices, promotions and trade pricing','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('orders.create','orders','Create web/manual-channel orders','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('orders.manage','orders','Update orders and delivery dates','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('orders.dispatch','orders','Pack and dispatch orders','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('orders.view','orders','View sales orders','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('inventory.view','inventory','View inventory','low') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('inventory.adjust','inventory','Adjust stock','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('purchasing.manage','purchasing','Manage suppliers and purchase orders','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('quotations.manage','quotations','Create and send quotations','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('quotations.approve','quotations','Approve quotations','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('invoices.issue','invoices','Issue and send invoices','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('invoices.void','invoices','Void invoices and credit notes','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('payments.record','payments','Record payments and deposits','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('refunds.process','payments','Process refunds','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('services.manage','services','Manage installation/repair requests','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('services.dispatch','services','Assign and schedule technicians','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('reports.view','reports','View operating reports','normal') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('reports.financial','reports','View financial and profit reports','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('ai.review_actions','ai','Approve/reject AI action requests','critical') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT INTO `permissions` (`permission_key`,`module`,`name`,`risk_level`) VALUES ('audit.view','audit','View audit logs','high') ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`name`=VALUES(`name`),`risk_level`=VALUES(`risk_level`);
-- @@STATEMENT_END@@
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`,`created`) SELECT r.`id`,p.`id`,UTC_TIMESTAMP(6) FROM `roles` r CROSS JOIN `permissions` p WHERE r.`role_key` IN ('master','elevated_staff');
-- @@STATEMENT_END@@
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`,`created`) SELECT r.`id`,p.`id`,UTC_TIMESTAMP(6) FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('refunds.process','invoices.issue','orders.dispatch','payments.record','orders.view','customers.view') WHERE r.`role_key`='standard_staff';
-- @@STATEMENT_END@@
DELETE rp FROM `role_permissions` rp INNER JOIN `roles` r ON r.`id` = rp.`role_id` INNER JOIN `permissions` p ON p.`id` = rp.`permission_id` WHERE r.`role_key` = 'standard_staff' AND p.`permission_key` NOT IN ('refunds.process','invoices.issue','orders.dispatch','payments.record','orders.view','customers.view');
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('website.customer_accounts',1,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('commerce.manual_channel_orders',1,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('inventory.low_stock_alerts',1,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('commerce.quotations',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('commerce.online_payments',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('commerce.shipping_rates',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('commerce.trade_accounts',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('services.installation_repairs',1,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('customer.save_for_later',1,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('customer.loyalty',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('ai.lighting_advisor',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('ai.internal_assistant',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`) VALUES ('ai.allow_actions',0,100,JSON_OBJECT(),'Merged-requirements default',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `enabled`=VALUES(`enabled`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('commerce.currency',JSON_QUOTE('AUD'),'Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('tax.gst_rate','0.1','Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('tax.prices_include_gst','true','Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('shipping.free_threshold_cents','15000','Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('shipping.standard_flat_rate_cents','1495','Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`description`,`modified`) VALUES ('business.timezone',JSON_QUOTE('Australia/Melbourne'),'Migrated from current template/business rules',UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('contact_message','MSG',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('sales_order','ORD',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('quotation','QUO',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('invoice','INV',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('credit_note','CN',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('return','RET',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('purchase_order','PO',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('goods_receipt','GR',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('shipment','SHP',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('service_request','SRV',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('outbound_message','OUT',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('fulfilment_task','FUL',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('stock_count','CNT',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('stock_transfer','TRF',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('supplier_invoice','SIN',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('import_job','IMP',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
INSERT INTO `document_sequences` (`document_type`,`prefix`,`next_value`,`padding`,`include_year`,`reset_annually`,`modified`) VALUES ('privacy_request','PRV',1000,6,1,0,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE `prefix`=VALUES(`prefix`),`modified`=UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
