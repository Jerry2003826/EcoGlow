-- config/db/seed.sql

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
(1,1,'Ashby Twin Sconce Brass','Decorative wall light',189.00,'ashby-twin-sconce-brass.jpg'),
(1,1,'Halden Pendant Oak','Timber pendant light',249.00,'halden-pendant-oak.jpg'),
(2,2,'Rowan Rotary Dimmer White','Dimmer control',49.00,'rowan-rotary-dimmer-white.jpg'),
(2,2,'Edison Wall Light Black','Industrial wall light',129.00,'edison-wall-light-black.jpg'),
(1,1,'Luna Pendant Gold','Modern pendant',219.00,'luna-pendant-gold.jpg'),
(2,2,'Arlo Wall Light White','Contemporary wall light',99.00,'arlo-wall-light-white.jpg');

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
('admin','admin@ecoglow.local','$2y$12$Ez0yPQMwTX7TLVzLGuKoselJ3WA6a/abmBBFyYGIiyaDVjFIvFakm','admin','active'),
('staff403','staff403@ecoglow.local','$2y$12$m/DzozQcfgYpOgc5cX7UtuC/3E0GBJtvGkoF24NjCl5Av46LtDNvC','staff','active');

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
    200.00
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
    'pending'
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
