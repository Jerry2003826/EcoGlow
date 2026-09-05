-- Roles and permission keys. Run after schema.sql.

INSERT INTO `permissions` (`permission_key`, `module`, `name`, `risk_level`) VALUES
  ('dashboard.view','dashboard','View the staff dashboard','normal'),
  ('access.manage','access','Manage staff access and roles','critical'),
  ('customers.view','customers','View customer records','normal'),
  ('customers.edit','customers','Edit customer records','normal'),
  ('customers.sensitive.view','customers','View age/date-of-birth fields','high'),
  ('customers.export','customers','Export customer data','high'),
  ('messages.manage','messages','Read, assign, reply to and close enquiries','normal'),
  ('catalogue.manage','catalogue','Manage categories, products, variants and media','normal'),
  ('pricing.manage','pricing','Manage prices, promotions and trade pricing','high'),
  ('orders.create','orders','Create web/manual-channel orders','normal'),
  ('orders.manage','orders','Update orders and delivery dates','normal'),
  ('orders.dispatch','orders','Pack and dispatch orders','normal'),
  ('orders.view','orders','View sales orders','normal'),
  ('inventory.view','inventory','View inventory','normal'),
  ('inventory.adjust','inventory','Adjust stock','high'),
  ('purchasing.manage','purchasing','Manage suppliers and purchase orders','normal'),
  ('quotations.manage','quotations','Create and send quotations','normal'),
  ('quotations.approve','quotations','Approve quotations','high'),
  ('invoices.issue','invoices','Issue and send invoices','normal'),
  ('invoices.void','invoices','Void invoices and credit notes','high'),
  ('payments.record','payments','Record payments and deposits','high'),
  ('refunds.process','payments','Process refunds','critical'),
  ('services.manage','services','Manage installation/repair requests','normal'),
  ('services.dispatch','services','Assign and schedule technicians','normal'),
  ('reports.view','reports','View operating reports','normal'),
  ('reports.financial','reports','View financial and profit reports','high'),
  ('ai.review_actions','ai','Approve/reject AI action requests','high'),
  ('audit.view','audit','View audit logs','high')
ON DUPLICATE KEY UPDATE
  `module` = VALUES(`module`), `name` = VALUES(`name`), `risk_level` = VALUES(`risk_level`);

INSERT INTO `roles` (`business_id`, `role_key`, `name`, `description`, `is_system`, `is_active`) VALUES
  (NULL,'master','Master access','Product Owner full access',1,1),
  (NULL,'elevated_staff','Elevated staff','Senior staff, all modules',1,1),
  (NULL,'standard_staff','Standard staff','Day-to-day fulfilment and billing',1,1),
  (NULL,'customer','Customer','Storefront account holder, no staff modules',1,1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `is_active` = VALUES(`is_active`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
  FROM `roles` r CROSS JOIN `permissions` p
 WHERE r.`role_key` IN ('master','elevated_staff');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
  FROM `roles` r
  JOIN `permissions` p
    ON p.`permission_key` IN ('refunds.process','invoices.issue','orders.dispatch',
                              'payments.record','orders.view','customers.view','dashboard.view')
 WHERE r.`role_key` = 'standard_staff';

DELETE rp FROM `role_permissions` rp
  JOIN `roles` r ON r.`id` = rp.`role_id`
  JOIN `permissions` p ON p.`id` = rp.`permission_id`
 WHERE r.`role_key` = 'standard_staff'
   AND p.`permission_key` NOT IN ('refunds.process','invoices.issue','orders.dispatch',
                                  'payments.record','orders.view','customers.view','dashboard.view');

