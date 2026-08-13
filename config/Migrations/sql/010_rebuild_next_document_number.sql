DROP PROCEDURE IF EXISTS `sp_next_document_number`;
-- @@STATEMENT_END@@
CREATE PROCEDURE `sp_next_document_number`(
    IN p_document_type VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_prefix VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    OUT p_document_number VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
BEGIN
    DECLARE v_value BIGINT;
    DECLARE v_padding INT;
    DECLARE v_include_year TINYINT;
    DECLARE v_year INT;

    SET v_year = YEAR(COALESCE(CONVERT_TZ(UTC_TIMESTAMP(), 'UTC', 'Australia/Melbourne'), UTC_TIMESTAMP()));
    INSERT INTO `document_sequences` (`document_type`, `prefix`, `next_value`, `padding`, `include_year`, `reset_annually`, `last_reset_year`, `modified`)
    VALUES (p_document_type, p_prefix, LAST_INSERT_ID(1001), 6, 1, 0, v_year, UTC_TIMESTAMP(6))
    ON DUPLICATE KEY UPDATE
        `prefix` = p_prefix,
        `next_value` = LAST_INSERT_ID(
            CASE
                WHEN `reset_annually` = 1 AND COALESCE(`last_reset_year`, v_year) <> v_year THEN 2
                ELSE `next_value` + 1
            END
        ),
        `last_reset_year` = v_year,
        `modified` = UTC_TIMESTAMP(6);

    SET v_value = LAST_INSERT_ID() - 1;
    SELECT `padding`, `include_year` INTO v_padding, v_include_year
      FROM `document_sequences` WHERE `document_type` = p_document_type;
    SET p_document_number = CONCAT(
        p_prefix,
        CASE WHEN v_include_year = 1 THEN CONCAT('-', v_year) ELSE '' END,
        '-', LPAD(v_value, v_padding, '0')
    );
END;
