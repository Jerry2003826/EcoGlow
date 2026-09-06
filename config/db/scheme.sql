CREATE TABLE User (
    UserId INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    UserRole ENUM('admin', 'staff', 'customer') NOT NULL,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Customer (
    CustomerId INT PRIMARY KEY AUTO_INCREMENT,
    CustomerName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Address VARCHAR(255),
    Phone VARCHAR(20),
    UserId INT NOT NULL UNIQUE,
    FOREIGN KEY (UserId) REFERENCES User(UserId)
);

CREATE TABLE Staff (
    StaffId INT PRIMARY KEY AUTO_INCREMENT,
    StaffName VARCHAR(100) NOT NULL,
    UserId INT NOT NULL UNIQUE,
    FOREIGN KEY (UserId) REFERENCES User(UserId)
);

CREATE TABLE Supplier (
    SupplierId INT PRIMARY KEY AUTO_INCREMENT,
    SupplierName VARCHAR(100) NOT NULL,
    Address VARCHAR(255),
    Phone VARCHAR(20),
    Email VARCHAR(100)
);

CREATE TABLE Product_Category (
    CategoryId INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Description VARCHAR(255)
);

CREATE TABLE Product (
    ProductId INT PRIMARY KEY AUTO_INCREMENT,
    CategoryId INT NOT NULL,
    SupplierId INT,
    Name VARCHAR(150) NOT NULL,
    Description TEXT,
    Price DECIMAL(10,2) NOT NULL,
    Image VARCHAR(255),
    FOREIGN KEY (CategoryId) REFERENCES Product_Category(CategoryId),
    FOREIGN KEY (SupplierId) REFERENCES Supplier(SupplierId)
);

CREATE TABLE Inventory (
    InventoryId INT PRIMARY KEY AUTO_INCREMENT,
    ProductId INT NOT NULL UNIQUE,
    Quantity INT NOT NULL DEFAULT 0,
    LowStockThreshold INT NOT NULL DEFAULT 5,
    RecordedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ProductId) REFERENCES Product(ProductId)
);

CREATE TABLE Shopping_Cart (
    CartId INT PRIMARY KEY AUTO_INCREMENT,
    CustomerId INT NOT NULL UNIQUE,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerId) REFERENCES Customer(CustomerId)
);

CREATE TABLE Cart_Item (
    CartItemId INT PRIMARY KEY AUTO_INCREMENT,
    CartId INT NOT NULL,
    ProductId INT NOT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    UNIQUE (CartId, ProductId),
    FOREIGN KEY (CartId) REFERENCES Shopping_Cart(CartId),
    FOREIGN KEY (ProductId) REFERENCES Product(ProductId)
);

CREATE TABLE Orders (
    OrderNo INT PRIMARY KEY AUTO_INCREMENT,
    CustomerId INT NOT NULL,
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10,2) NOT NULL DEFAULT 0,
    OrderStatus ENUM(
        'pending',
        'paid',
        'processing',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    FOREIGN KEY (CustomerId) REFERENCES Customer(CustomerId)
);

CREATE TABLE Order_Item (
    OrderItemId INT PRIMARY KEY AUTO_INCREMENT,
    OrderNo INT NOT NULL,
    ProductId INT NOT NULL,
    Quantity INT NOT NULL,
    UnitPrice DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (OrderNo) REFERENCES Orders(OrderNo),
    FOREIGN KEY (ProductId) REFERENCES Product(ProductId)
);

CREATE TABLE Payment (
    PaymentNo INT PRIMARY KEY AUTO_INCREMENT,
    OrderNo INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    PaymentStatus ENUM(
        'pending',
        'paid',
        'failed'
    ) DEFAULT 'pending',
    StripePaymentIntentId VARCHAR(255) UNIQUE,
    PaymentDate DATETIME,
    FOREIGN KEY (OrderNo) REFERENCES Orders(OrderNo)
);

CREATE TABLE Receipt (
    ReceiptNo INT PRIMARY KEY AUTO_INCREMENT,
    PaymentNo INT NOT NULL UNIQUE,
    ReceiptDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(10,2) NOT NULL,
    EmailSent BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (PaymentNo) REFERENCES Payment(PaymentNo)
);

CREATE TABLE Service_Request (
    ServiceId INT PRIMARY KEY AUTO_INCREMENT,
    CustomerId INT NOT NULL,
    ServiceType VARCHAR(100) NOT NULL,
    Description VARCHAR(255),
    PreferredDate DATE,
    ServiceStatus ENUM(
        'requested',
        'quoted',
        'accepted',
        'completed',
        'cancelled'
    ) DEFAULT 'requested',
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerId) REFERENCES Customer(CustomerId)
);

CREATE TABLE Quotation (
    QuoteId INT PRIMARY KEY AUTO_INCREMENT,
    ServiceId INT NOT NULL UNIQUE,
    Description VARCHAR(255),
    QuoteAmount DECIMAL(10,2) NOT NULL,
    QuoteDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status ENUM(
        'pending',
        'accepted',
        'rejected'
    ) DEFAULT 'pending',
    FOREIGN KEY (ServiceId) REFERENCES Service_Request(ServiceId)
);

CREATE TABLE Invoice (
    InvoiceNo INT PRIMARY KEY AUTO_INCREMENT,
    QuoteId INT NOT NULL UNIQUE,
    InvoiceDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(10,2) NOT NULL,
    Status ENUM(
        'unpaid',
        'paid'
    ) DEFAULT 'unpaid',
    FOREIGN KEY (QuoteId) REFERENCES Quotation(QuoteId)
);

CREATE TABLE Service_Payment (
    ServicePaymentNo INT PRIMARY KEY AUTO_INCREMENT,
    InvoiceNo INT NOT NULL UNIQUE,
    Amount DECIMAL(10,2) NOT NULL,
    PaymentStatus ENUM(
        'pending',
        'paid',
        'failed'
    ) DEFAULT 'pending',
    StripePaymentIntentId VARCHAR(255) UNIQUE,
    PaymentDate DATETIME,
    FOREIGN KEY (InvoiceNo) REFERENCES Invoice(InvoiceNo)
);

CREATE TABLE Service_Receipt (
    ServiceReceiptNo INT PRIMARY KEY AUTO_INCREMENT,
    ServicePaymentNo INT NOT NULL UNIQUE,
    ReceiptDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Amount DECIMAL(10,2) NOT NULL,
    EmailSent BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ServicePaymentNo)
        REFERENCES Service_Payment(ServicePaymentNo)
);

CREATE TABLE Enquiry (
    EnquiryNo INT PRIMARY KEY AUTO_INCREMENT,
    CustomerId INT NOT NULL,
    StaffId INT NULL,
    Subject VARCHAR(150),
    Message TEXT NOT NULL,
    Status ENUM(
        'pending',
        'in_progress',
        'replied'
    ) DEFAULT 'pending',
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Reply TEXT NULL,
    RepliedDate DATETIME NULL,
    FOREIGN KEY (CustomerId) REFERENCES Customer(CustomerId),
    FOREIGN KEY (StaffId) REFERENCES Staff(StaffId)
);

CREATE TABLE Delivery (
    DeliveryId INT PRIMARY KEY AUTO_INCREMENT,
    OrderNo INT NOT NULL UNIQUE,
    RecipientName VARCHAR(100) NOT NULL,
    DeliveryAddress VARCHAR(255) NOT NULL,
    TrackingNumber VARCHAR(100),
    DeliveryStatus ENUM(
        'pending',
        'shipped',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    ShippedDate DATETIME NULL,
    DeliveredDate DATETIME NULL,
    FOREIGN KEY (OrderNo) REFERENCES Orders(OrderNo)
);