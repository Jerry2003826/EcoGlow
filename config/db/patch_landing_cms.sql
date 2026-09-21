-- config/db/patch_landing_cms.sql
CREATE TABLE IF NOT EXISTS LandingText (
    TextId INT AUTO_INCREMENT PRIMARY KEY,
    TextKey VARCHAR(100) NOT NULL UNIQUE,
    TextValue TEXT NOT NULL,
    FieldLabel VARCHAR(150) NOT NULL,
    FieldType VARCHAR(20) NOT NULL DEFAULT 'text',
    SortOrder INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS LandingBlock (
    BlockId INT AUTO_INCREMENT PRIMARY KEY,
    BlockType VARCHAR(20) NOT NULL,
    SortOrder INT NOT NULL DEFAULT 0,
    Title VARCHAR(200) NOT NULL,
    Description TEXT NOT NULL,
    ButtonLabel VARCHAR(100) NULL,
    ButtonUrl VARCHAR(255) NULL,
    ImagePath VARCHAR(255) NOT NULL,
    AltText VARCHAR(255) NOT NULL,
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_type_order (BlockType, SortOrder)
);

CREATE TABLE IF NOT EXISTS StaffUserAccess (
    UserAccessId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    PermissionKey VARCHAR(100) NOT NULL,
    Allowed TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_user_permission (UserId, PermissionKey),
    CONSTRAINT fk_staff_user_access_user FOREIGN KEY (UserId)
        REFERENCES `User`(`UserId`) ON DELETE CASCADE
);

INSERT INTO LandingText (TextKey, TextValue, FieldLabel, FieldType, SortOrder) VALUES
('carousel.eyebrow', 'Featured collection', 'Carousel eyebrow', 'text', 1),
('hero.eyebrow', 'Lighting for everyday living', 'Hero eyebrow', 'text', 2),
('hero.title', 'All things Lighting.', 'Hero title', 'text', 3),
('hero.body1', 'Eco Glow Lighting offers modern lighting fixtures and smart home solutions designed to make your home feel brighter and better.', 'Hero first paragraph', 'textarea', 4),
('hero.body2', 'Enquire about our installation and repair services, or explore our featured lighting range below.', 'Hero second paragraph', 'textarea', 5),
('hero.button_primary_label', 'Shop all Lighting', 'Hero primary button label', 'text', 6),
('hero.button_primary_url', '/products', 'Hero primary button URL', 'url', 7),
('hero.button_secondary_label', 'Make an Enquiry', 'Hero secondary button label', 'text', 8),
('hero.button_secondary_url', '/enquiries/add', 'Hero secondary button URL', 'url', 9),
('hero.image', 'landing/pendants.jpg', 'Hero image path', 'text', 10),
('hero.image_alt', 'Pendant lights in a modern interior', 'Hero image alt text', 'text', 11),
('products.eyebrow', 'Our Product Range', 'Products eyebrow', 'text', 12),
('products.title', 'Lighting for every space', 'Products title', 'text', 13),
('products.description', 'Explore warm, practical and energy-efficient lighting selected for modern Australian homes.', 'Products description', 'textarea', 14),
('service.eyebrow', 'Installation and repairs', 'Service eyebrow', 'text', 15),
('service.title', 'Need help with your lighting?', 'Service title', 'text', 16),
('service.body', 'Talk to us about product selection, installation, replacements and lighting repairs for your home.', 'Service body', 'textarea', 17),
('service.button_label', 'Contact us', 'Service button label', 'text', 18),
('service.button_url', '/enquiries/add', 'Service button URL', 'url', 19)
ON DUPLICATE KEY UPDATE
    TextValue = VALUES(TextValue),
    FieldLabel = VALUES(FieldLabel),
    FieldType = VALUES(FieldType),
    SortOrder = VALUES(SortOrder);

INSERT INTO LandingBlock (BlockId, BlockType, SortOrder, Title, Description, ButtonLabel, ButtonUrl, ImagePath, AltText, IsActive) VALUES
(1, 'carousel', 1, 'A brighter space. A warmer welcome.', 'Eco Glow Lighting offers a range of modern lighting solutions for your home, including pendant, wall, table and floor lighting.', 'Explore lighting', '#shop', 'landing/interior.jpg', 'Clear pendant lights hanging in a row', 1),
(2, 'carousel', 2, 'Great lighting starts with a conversation', 'Get in touch with our team to discuss your lighting needs.', 'Talk to our team', '/enquiries/add', 'landing/opal-lamp.jpg', 'Opal table lamp on a side table in a living room', 1),
(3, 'carousel', 3, 'Lighting for every space', 'Explore our range of pendant, wall, table and floor lighting for your home.', 'View our products', '/products', 'landing/pendants.jpg', 'Pendant lights in a modern interior', 1),
(4, 'feature', 1, 'Pendant Lights', 'Ceiling-mounted and pendant lighting for indoor spaces.', 'Ask about this light', '/enquiries/add', 'landing/interior.jpg', 'Oak pendant light above a dining table', 1),
(5, 'feature', 2, 'Table Lamps', 'A simple table lamp with a natural linen shade, ideal for bedside tables and reading corners.', 'Ask about this light', '/enquiries/add', 'landing/table-lamp.jpg', 'Linen table lamp on a side table', 1),
(6, 'feature', 3, 'Wall Lights', 'Wall-mounted lighting for indoor and decorative use.', 'Ask about this light', '/enquiries/add', 'landing/wall-light.jpg', 'Brass wall light mounted on an interior wall', 1),
(7, 'feature', 4, 'Floor Lamps', 'Freestanding lighting for living and reading areas.', 'Ask about this light', '/enquiries/add', 'landing/floor-lamp.jpg', 'Dome floor lamp beside an armchair', 1),
(8, 'feature', 5, 'Outdoor Lights', 'Weather-resistant lighting for gardens and outdoor areas.', 'Ask about this light', '/enquiries/add', 'landing/outdoor-lighting.jpg', 'Outdoor lights in a garden setting', 1),
(9, 'feature', 6, 'Smart Lights', 'An energy-efficient smart globe with adjustable brightness for a flexible home lighting setup.', 'Ask about this light', '/enquiries/add', 'landing/globe.jpg', 'Smart LED light globe', 1)
ON DUPLICATE KEY UPDATE
    BlockType = VALUES(BlockType),
    SortOrder = VALUES(SortOrder),
    Title = VALUES(Title),
    Description = VALUES(Description),
    ButtonLabel = VALUES(ButtonLabel),
    ButtonUrl = VALUES(ButtonUrl),
    ImagePath = VALUES(ImagePath),
    AltText = VALUES(AltText),
    IsActive = VALUES(IsActive);
