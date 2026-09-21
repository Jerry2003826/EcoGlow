-- config/db/patch_chatbot.sql
-- Creates ChatbotFaq table and seeds editable chatbot FAQ content.

CREATE TABLE IF NOT EXISTS ChatbotFaq (
    FaqId INT AUTO_INCREMENT PRIMARY KEY,
    Question VARCHAR(255) NOT NULL,
    Answer TEXT NOT NULL,
    SortOrder INT NOT NULL DEFAULT 0,
    IsActive TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO ChatbotFaq (
    FaqId,
    Question,
    Answer,
    SortOrder,
    IsActive
) VALUES
(
    1,
    'How much does installation cost?',
    'Professional in-home installation is available for AUD $80 per item.',
    10,
    1
),
(
    2,
    'How long does delivery take?',
    'Orders are scheduled for delivery approximately 7 days after successful checkout.',
    20,
    1
),
(
    3,
    'What payment method is currently accepted?',
    'The storefront currently uses a test card checkout flow. The required test card number is 4242424242424242 with expiry 12/31 and CVV 123.',
    30,
    1
),
(
    4,
    'How do I request returns, repairs, or after-sales support?',
    'Please contact our team through the Contact Us page at /enquiries/add and we will assist you with returns, repairs, and after-sales enquiries.',
    40,
    1
),
(
    5,
    'Do you work on residential and commercial projects?',
    'Yes. Eco Glow Lighting supports both residential and commercial lighting projects.',
    50,
    1
),
(
    6,
    'How can I contact customer service?',
    'You can reach our customer support team through the Contact Us page at /enquiries/add.',
    60,
    1
)
ON DUPLICATE KEY UPDATE
    Question = VALUES(Question),
    Answer = VALUES(Answer),
    SortOrder = VALUES(SortOrder),
    IsActive = VALUES(IsActive);
