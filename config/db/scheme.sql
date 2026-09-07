-- config/db/scheme.sql

CREATE TABLE `User` (
    `UserId` INT AUTO_INCREMENT PRIMARY KEY,
    `Username` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL UNIQUE,
    `Password` VARCHAR(255) NOT NULL,
    `UserRole` VARCHAR(50) NOT NULL,
    `Status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `CreatedDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `Customer` (
    `CustomerId` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerName` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL,
    `Address` VARCHAR(255),
    `Phone` VARCHAR(20)
);

CREATE TABLE `Guest` (
    `GuestId` INT AUTO_INCREMENT PRIMARY KEY,
    `GuestName` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL,
    `Address` VARCHAR(255),
    `Phone` VARCHAR(20)
);

CREATE TABLE `Staff` (
    `StaffId` INT AUTO_INCREMENT PRIMARY KEY,
    `UserId` INT NOT NULL,
    `StaffName` VARCHAR(100) NOT NULL,
    CONSTRAINT `fk_staff_user`
        FOREIGN KEY (`UserId`) REFERENCES `User`(`UserId`)
);

CREATE TABLE `Supplier` (
    `SupplierId` INT AUTO_INCREMENT PRIMARY KEY,
    `SupplierName` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100),
    `Phone` VARCHAR(20),
    `Address` VARCHAR(255)
);

CREATE TABLE `Product_Category` (
    `CategoryId` INT AUTO_INCREMENT PRIMARY KEY,
    `CategoryName` VARCHAR(100) NOT NULL
);

CREATE TABLE `Product` (
    `ProductId` INT AUTO_INCREMENT PRIMARY KEY,
    `CategoryId` INT NOT NULL,
    `SupplierId` INT,
    `ProductName` VARCHAR(150) NOT NULL,
    `Description` TEXT,
    `Price` DECIMAL(10,2) NOT NULL,
    `Image` VARCHAR(255),
    CONSTRAINT `fk_product_category`
        FOREIGN KEY (`CategoryId`) REFERENCES `Product_Category`(`CategoryId`),
    CONSTRAINT `fk_product_supplier`
        FOREIGN KEY (`SupplierId`) REFERENCES `Supplier`(`SupplierId`)
);

CREATE TABLE `Inventory` (
    `InventoryId` INT AUTO_INCREMENT PRIMARY KEY,
    `ProductId` INT NOT NULL,
    `Quantity` INT NOT NULL,
    `LowStockThreshold` INT NOT NULL DEFAULT 5,
    CONSTRAINT `fk_inventory_product`
        FOREIGN KEY (`ProductId`) REFERENCES `Product`(`ProductId`)
);

CREATE TABLE `Shopping_Cart` (
    `CartId` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerId` INT NULL,
    `GuestId` INT NULL,
    `InstallOptIn` TINYINT(1) NOT NULL DEFAULT 0,
    `InstallFee` DECIMAL(10,2) NOT NULL DEFAULT 200.00,
    `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cart_customer`
        FOREIGN KEY (`CustomerId`) REFERENCES `Customer`(`CustomerId`),
    CONSTRAINT `fk_cart_guest`
        FOREIGN KEY (`GuestId`) REFERENCES `Guest`(`GuestId`)
);

CREATE TABLE `Cart_Item` (
    `CartItemId` INT AUTO_INCREMENT PRIMARY KEY,
    `CartId` INT NOT NULL,
    `ProductId` INT NOT NULL,
    `Quantity` INT NOT NULL,
    CONSTRAINT `fk_cartitem_cart`
        FOREIGN KEY (`CartId`) REFERENCES `Shopping_Cart`(`CartId`),
    CONSTRAINT `fk_cartitem_product`
        FOREIGN KEY (`ProductId`) REFERENCES `Product`(`ProductId`)
);

CREATE TABLE `Orders` (
    `OrderId` INT AUTO_INCREMENT PRIMARY KEY,
    `CartId` INT NOT NULL UNIQUE,
    `CustomerId` INT NULL,
    `GuestId` INT NULL,
    `OrderDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Status` VARCHAR(50) NOT NULL,
    CONSTRAINT `fk_orders_cart`
        FOREIGN KEY (`CartId`) REFERENCES `Shopping_Cart`(`CartId`),
    CONSTRAINT `fk_orders_customer`
        FOREIGN KEY (`CustomerId`) REFERENCES `Customer`(`CustomerId`),
    CONSTRAINT `fk_orders_guest`
        FOREIGN KEY (`GuestId`) REFERENCES `Guest`(`GuestId`)
);

CREATE TABLE `Order_Item` (
    `OrderItemId` INT AUTO_INCREMENT PRIMARY KEY,
    `OrderId` INT NOT NULL,
    `ProductId` INT NOT NULL,
    `Quantity` INT NOT NULL,
    `UnitPrice` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_orderitem_order`
        FOREIGN KEY (`OrderId`) REFERENCES `Orders`(`OrderId`),
    CONSTRAINT `fk_orderitem_product`
        FOREIGN KEY (`ProductId`) REFERENCES `Product`(`ProductId`)
);

CREATE TABLE `Payment` (
    `PaymentId` INT AUTO_INCREMENT PRIMARY KEY,
    `OrderId` INT NOT NULL,
    `Amount` DECIMAL(10,2) NOT NULL,
    `PaymentMethod` VARCHAR(50),
    `PaymentDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payment_order`
        FOREIGN KEY (`OrderId`) REFERENCES `Orders`(`OrderId`)
);

CREATE TABLE `Receipt` (
    `ReceiptId` INT AUTO_INCREMENT PRIMARY KEY,
    `PaymentId` INT NOT NULL,
    `ReceiptNumber` VARCHAR(100) NOT NULL,
    `IssuedDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_receipt_payment`
        FOREIGN KEY (`PaymentId`) REFERENCES `Payment`(`PaymentId`)
);

CREATE TABLE `Service_Request` (
    `ServiceRequestId` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerId` INT,
    `RequestType` VARCHAR(100),
    `Description` TEXT,
    `RequestedDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_service_customer`
        FOREIGN KEY (`CustomerId`) REFERENCES `Customer`(`CustomerId`)
);

CREATE TABLE `Quotation` (
    `QuotationId` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerId` INT,
    `QuotationDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `TotalAmount` DECIMAL(10,2),
    CONSTRAINT `fk_quotation_customer`
        FOREIGN KEY (`CustomerId`) REFERENCES `Customer`(`CustomerId`)
);

CREATE TABLE `Invoice` (
    `InvoiceId` INT AUTO_INCREMENT PRIMARY KEY,
    `OrderId` INT NOT NULL,
    `InvoiceDate` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `TotalAmount` DECIMAL(10,2),
    CONSTRAINT `fk_invoice_order`
        FOREIGN KEY (`OrderId`) REFERENCES `Orders`(`OrderId`)
);

CREATE TABLE `Service_Payment` (
    `ServicePaymentId` INT AUTO_INCREMENT PRIMARY KEY,
    `ServiceRequestId` INT NOT NULL,
    `Amount` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_servicepayment_request`
        FOREIGN KEY (`ServiceRequestId`) REFERENCES `Service_Request`(`ServiceRequestId`)
);

CREATE TABLE `Service_Receipt` (
    `ServiceReceiptId` INT AUTO_INCREMENT PRIMARY KEY,
    `ServicePaymentId` INT NOT NULL,
    `ReceiptNumber` VARCHAR(100) NOT NULL,
    CONSTRAINT `fk_servicereceipt_payment`
        FOREIGN KEY (`ServicePaymentId`) REFERENCES `Service_Payment`(`ServicePaymentId`)
);

CREATE TABLE `Enquiry` (
    `EnquiryId` INT AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(100) NOT NULL,
    `Subject` VARCHAR(200),
    `Message` TEXT,
    `CreatedDate` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `Delivery` (
    `DeliveryId` INT AUTO_INCREMENT PRIMARY KEY,
    `OrderId` INT NOT NULL,
    `DeliveryAddress` VARCHAR(255) NOT NULL,
    `DeliveryStatus` VARCHAR(50) NOT NULL,
    `DeliveryDate` DATETIME NULL,
    CONSTRAINT `fk_delivery_order`
        FOREIGN KEY (`OrderId`) REFERENCES `Orders`(`OrderId`)
);
