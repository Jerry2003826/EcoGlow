-- config/db/seed.sql
-- account and password for demo owner and staff users
-- Owner: admin@ecoglow.local / admin1234
-- Staff: staff@ecoglow.local / staff1234
INSERT INTO `Product_Category`
(`CategoryName`)
VALUES
('Pendant Lights'),
('Wall Lights');

INSERT INTO `Supplier`
(`SupplierName`,`Email`,`Phone`,`Address`)
VALUES
('Brightline Wholesale','orders@brightline.example','0390001001','Melbourne VIC'),
('Harbor Electrical','sales@harborelec.example','0390002002','Melbourne VIC');

INSERT INTO `Product`
(`CategoryId`,`SupplierId`,`ProductName`,`Description`,`Price`,`Image`)
VALUES
(2,1,'Ashby Twin Sconce Brass','Decorative wall light',189.00,'product/ashby-twin-sconce-brass.jpg'),
(1,1,'Halden Pendant Oak','Timber pendant light',249.00,'product/halden-pendant-oak.jpg'),
(2,2,'Linen table lamp','table lamp',49.00,'product/table-lamp.jpg'),
(2,2,'Edison Wall Light Black','Industrial wall light',129.00,'product/edison-wall-light-black.jpg'),
(1,1,'Luna Pendant Gold','Modern pendant',219.00,'product/luna-pendant-gold.jpg'),
(2,2,'Arlo Wall Light White','Contemporary wall light',99.00,'product/arlo-wall-light-white.jpg');

INSERT INTO `Inventory`
(`ProductId`,`Quantity`,`LowStockThreshold`)
VALUES
(1,12,5),
(2,2,5),
(3,1,5),
(4,15,5),
(5,9,5),
(6,20,5);

INSERT INTO `User`
(`Username`,`Email`,`Password`,`UserRole`,`Status`)
VALUES
('admin','admin@ecoglow.local','$2y$10$3qfNIS3T8eUhc4VZQizawO/E7ATUE6pSRx5FUNqKzAnUGqw157vtG','admin','active'),
('staff','staff@ecoglow.local','$2y$10$AJDWcSl6Ls1gQy1paGznZuVoqwYWvFJ31yKIrNpGWTUds/ucmS3IS','staff','active');

INSERT INTO `Guest`
(`GuestName`,`Email`,`Address`,`Phone`)
VALUES
(
    'Mia Walker',
    'mia.walker@example.com',
    '15 Collins Street, Melbourne VIC 3000',
    '0400123456'
);

INSERT INTO `Shopping_Cart`
(`CustomerId`,`GuestId`,`InstallOptIn`,`InstallFee`)
VALUES
(
    NULL,
    1,
    1,
    80.00
);

INSERT INTO `Cart_Item`
(`CartId`,`ProductId`,`Quantity`)
VALUES
(1,1,1);

INSERT INTO `Orders`
(`CartId`,`CustomerId`,`GuestId`,`Status`)
VALUES
(
    1,
    NULL,
    1,
    'paid'
);

INSERT INTO `Order_Item`
(`OrderId`,`ProductId`,`Quantity`,`UnitPrice`)
VALUES
(
    1,
    1,
    1,
    189.00
);

INSERT INTO `Delivery`
(`OrderId`,`DeliveryAddress`,`DeliveryStatus`,`DeliveryDate`)
VALUES
(
    1,
    '15 Collins Street, Melbourne VIC 3000',
    'scheduled',
    NOW()
);

INSERT INTO `Payment`
(`OrderId`,`Amount`,`PaymentMethod`)
VALUES
(
    1,
    389.00,
    'card'
);

INSERT INTO `Receipt`
(`PaymentId`,`ReceiptNumber`)
VALUES
(
    1,
    'RCT-1001'
);

INSERT INTO `Service_Request`
(`CustomerId`,`RequestType`,`Description`,`RequestedDate`)
VALUES
(
    NULL,
    'repair',
    'Guest Mia Walker, 15 Collins Street: pendant flickers after dusk. Book electrician.',
    DATE_ADD(NOW(), INTERVAL 3 DAY)
);

INSERT INTO `Enquiry`
(`Name`,`Email`,`Subject`,`Message`)
VALUES
(
    'Priya Chen',
    'priya.chen@example.com',
    'Wall light for hallway',
    'Please recommend a warm wall light for a narrow Clayton hallway.'
);

UPDATE Product
SET CategoryId = 2
WHERE ProductName = 'Ashby Twin Sconce Brass';

UPDATE Orders o
INNER JOIN Payment p
    ON p.OrderId = o.OrderId
SET o.Status = 'paid'
WHERE o.Status = 'pending';

ALTER TABLE `Shopping_Cart`
    MODIFY `InstallFee` DECIMAL(10,2) NOT NULL DEFAULT 80.00;