-- Enable web checkout payments and installation bookings.
UPDATE `feature_flags`
   SET `enabled` = 1,
       `description` = 'Full-amount Stripe payments on web checkout',
       `modified` = UTC_TIMESTAMP(6)
 WHERE `flag_key` = 'commerce.online_payments';
-- @@STATEMENT_END@@
UPDATE `feature_flags`
   SET `enabled` = 1,
       `description` = 'Customer booking and staff scheduling',
       `modified` = UTC_TIMESTAMP(6)
 WHERE `flag_key` = 'services.installation_repairs';
-- @@STATEMENT_END@@
INSERT INTO `feature_flags` (`flag_key`,`enabled`,`rollout_percentage`,`rules`,`description`,`modified`)
VALUES ('commerce.customer_account_required', 1, 100, JSON_OBJECT(), 'Web checkout requires a signed-in customer', UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE `enabled` = 1, `modified` = UTC_TIMESTAMP(6);
-- @@STATEMENT_END@@
