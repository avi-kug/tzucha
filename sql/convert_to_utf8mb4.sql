-- Convert database and tables to utf8mb4 (full Unicode support)
-- Safe to run multiple times. Always back up your DB first.

SET NAMES utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;

START TRANSACTION;

-- Database default
ALTER DATABASE `tzucha` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Tables used by the app
ALTER TABLE `departments`      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `categories`       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `expense_types`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `paid_by_options`  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `from_accounts`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `stores`           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `fixed_expenses`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `regular_expenses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `summary_expenses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

COMMIT;
