-- config/db/patch_staff_access.sql
CREATE TABLE IF NOT EXISTS StaffAccess (
    AccessId INT AUTO_INCREMENT PRIMARY KEY,
    PermissionKey VARCHAR(100) NOT NULL UNIQUE,
    ModuleLabel VARCHAR(150) NOT NULL,
    Allowed TINYINT(1) NOT NULL DEFAULT 0
);

INSERT INTO StaffAccess (PermissionKey, ModuleLabel, Allowed) VALUES
('dashboard.view','Dashboard',1),
('orders.view','Orders',1),
('customers.view','Customers',1),
('invoices.issue','Invoices',1),
('inventory.view','Inventory',1),
('inventory.adjust','Edit stock',1),
('messages.manage','Messages',1),
('quotations.manage','Quotations',1),
('shipments.view','Shipments',1),
('payments.view','Payments',1),
('products.view','Products',1),
('refunds.process','Refunds',1),
('suppliers.view','Suppliers',0),
('reports.view','Reports',0)
ON DUPLICATE KEY UPDATE ModuleLabel = VALUES(ModuleLabel);
