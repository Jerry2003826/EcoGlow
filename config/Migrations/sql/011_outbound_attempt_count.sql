ALTER TABLE `outbound_messages`
    ADD COLUMN `attempt_count` INT NOT NULL DEFAULT 0 AFTER `failure_reason`;
-- @@STATEMENT_END@@
