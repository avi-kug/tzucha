-- OPTIONAL: Attempt to repair Hebrew mojibake already stored as garbled text
-- Run AFTER convert_to_utf8mb4.sql. BACKUP FIRST!
-- Pattern targets common mojibake bytes like '×' which appear when UTF-8 is misread as latin1.

START TRANSACTION;

-- Reference function: convert latin1-misread text back to utf8mb4
-- Usage: CONVERT(CAST(CONVERT(col USING latin1) AS BINARY) USING utf8mb4)

-- Lookup tables
UPDATE `departments`     SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';
UPDATE `categories`      SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';
UPDATE `expense_types`   SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';
UPDATE `paid_by_options` SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';
UPDATE `from_accounts`   SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';
UPDATE `stores`          SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%×%';

-- Expense text fields
UPDATE `fixed_expenses`   SET `for_what`     = CONVERT(CAST(CONVERT(`for_what`     USING latin1) AS BINARY) USING utf8mb4) WHERE `for_what`     LIKE '%×%';
UPDATE `fixed_expenses`   SET `store`        = CONVERT(CAST(CONVERT(`store`        USING latin1) AS BINARY) USING utf8mb4) WHERE `store`        LIKE '%×%';
UPDATE `fixed_expenses`   SET `department`   = CONVERT(CAST(CONVERT(`department`   USING latin1) AS BINARY) USING utf8mb4) WHERE `department`   LIKE '%×%';
UPDATE `fixed_expenses`   SET `category`     = CONVERT(CAST(CONVERT(`category`     USING latin1) AS BINARY) USING utf8mb4) WHERE `category`     LIKE '%×%';
UPDATE `fixed_expenses`   SET `expense_type` = CONVERT(CAST(CONVERT(`expense_type` USING latin1) AS BINARY) USING utf8mb4) WHERE `expense_type` LIKE '%×%';
UPDATE `fixed_expenses`   SET `paid_by`      = CONVERT(CAST(CONVERT(`paid_by`      USING latin1) AS BINARY) USING utf8mb4) WHERE `paid_by`      LIKE '%×%';
UPDATE `fixed_expenses`   SET `from_account` = CONVERT(CAST(CONVERT(`from_account` USING latin1) AS BINARY) USING utf8mb4) WHERE `from_account` LIKE '%×%';

UPDATE `regular_expenses` SET `for_what`     = CONVERT(CAST(CONVERT(`for_what`     USING latin1) AS BINARY) USING utf8mb4) WHERE `for_what`     LIKE '%×%';
UPDATE `regular_expenses` SET `store`        = CONVERT(CAST(CONVERT(`store`        USING latin1) AS BINARY) USING utf8mb4) WHERE `store`        LIKE '%×%';
UPDATE `regular_expenses` SET `department`   = CONVERT(CAST(CONVERT(`department`   USING latin1) AS BINARY) USING utf8mb4) WHERE `department`   LIKE '%×%';
UPDATE `regular_expenses` SET `category`     = CONVERT(CAST(CONVERT(`category`     USING latin1) AS BINARY) USING utf8mb4) WHERE `category`     LIKE '%×%';
UPDATE `regular_expenses` SET `expense_type` = CONVERT(CAST(CONVERT(`expense_type` USING latin1) AS BINARY) USING utf8mb4) WHERE `expense_type` LIKE '%×%';
UPDATE `regular_expenses` SET `paid_by`      = CONVERT(CAST(CONVERT(`paid_by`      USING latin1) AS BINARY) USING utf8mb4) WHERE `paid_by`      LIKE '%×%';
UPDATE `regular_expenses` SET `from_account` = CONVERT(CAST(CONVERT(`from_account` USING latin1) AS BINARY) USING utf8mb4) WHERE `from_account` LIKE '%×%';

UPDATE `summary_expenses` SET `for_what`     = CONVERT(CAST(CONVERT(`for_what`     USING latin1) AS BINARY) USING utf8mb4) WHERE `for_what`     LIKE '%×%';
UPDATE `summary_expenses` SET `store`        = CONVERT(CAST(CONVERT(`store`        USING latin1) AS BINARY) USING utf8mb4) WHERE `store`        LIKE '%×%';
UPDATE `summary_expenses` SET `department`   = CONVERT(CAST(CONVERT(`department`   USING latin1) AS BINARY) USING utf8mb4) WHERE `department`   LIKE '%×%';
UPDATE `summary_expenses` SET `category`     = CONVERT(CAST(CONVERT(`category`     USING latin1) AS BINARY) USING utf8mb4) WHERE `category`     LIKE '%×%';
UPDATE `summary_expenses` SET `expense_type` = CONVERT(CAST(CONVERT(`expense_type` USING latin1) AS BINARY) USING utf8mb4) WHERE `expense_type` LIKE '%×%';
UPDATE `summary_expenses` SET `paid_by`      = CONVERT(CAST(CONVERT(`paid_by`      USING latin1) AS BINARY) USING utf8mb4) WHERE `paid_by`      LIKE '%×%';
UPDATE `summary_expenses` SET `from_account` = CONVERT(CAST(CONVERT(`from_account` USING latin1) AS BINARY) USING utf8mb4) WHERE `from_account` LIKE '%×%';

COMMIT;
