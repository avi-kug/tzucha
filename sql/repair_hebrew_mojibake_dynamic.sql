-- Dynamic Hebrew mojibake repair: only runs on existing tables/columns
-- BACKUP FIRST!
SET NAMES utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE repair_mojibake()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE t VARCHAR(64);
  DECLARE c VARCHAR(64);
  DECLARE cnt INT DEFAULT 0;

  -- Cursor over table/column pairs we may need to repair
  DECLARE cur CURSOR FOR
    SELECT * FROM (
      SELECT 'departments'      AS t, 'name'         AS c UNION ALL
      SELECT 'categories',           'name'               UNION ALL
      SELECT 'expense_types',        'name'               UNION ALL
      SELECT 'paid_by_options',      'name'               UNION ALL
      SELECT 'from_accounts',        'name'               UNION ALL
      SELECT 'stores',               'name'               UNION ALL
      SELECT 'fixed_expenses',       'for_what'           UNION ALL
      SELECT 'fixed_expenses',       'store'              UNION ALL
      SELECT 'fixed_expenses',       'department'         UNION ALL
      SELECT 'fixed_expenses',       'category'           UNION ALL
      SELECT 'fixed_expenses',       'expense_type'       UNION ALL
      SELECT 'fixed_expenses',       'paid_by'            UNION ALL
      SELECT 'fixed_expenses',       'from_account'       UNION ALL
      SELECT 'regular_expenses',     'for_what'           UNION ALL
      SELECT 'regular_expenses',     'store'              UNION ALL
      SELECT 'regular_expenses',     'department'         UNION ALL
      SELECT 'regular_expenses',     'category'           UNION ALL
      SELECT 'regular_expenses',     'expense_type'       UNION ALL
      SELECT 'regular_expenses',     'paid_by'            UNION ALL
      SELECT 'regular_expenses',     'from_account'       UNION ALL
      SELECT 'summary_expenses',     'for_what'           UNION ALL
      SELECT 'summary_expenses',     'store'              UNION ALL
      SELECT 'summary_expenses',     'department'         UNION ALL
      SELECT 'summary_expenses',     'category'           UNION ALL
      SELECT 'summary_expenses',     'expense_type'       UNION ALL
      SELECT 'summary_expenses',     'paid_by'            UNION ALL
      SELECT 'summary_expenses',     'from_account'
    ) pairs;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO t, c;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SELECT COUNT(*) INTO cnt
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = t
      AND column_name = c;

    IF cnt > 0 THEN
      SET @sql = CONCAT(
        'UPDATE `', t, '` SET `', c, "` = CONVERT(CAST(CONVERT(`", c, "` USING latin1) AS BINARY) USING utf8mb4) ",
        "WHERE `", c, "` LIKE '%×%'"
      );
      PREPARE stmt FROM @sql;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
    END IF;
  END LOOP;
  CLOSE cur;
END //
DELIMITER ;

START TRANSACTION;
CALL repair_mojibake();
COMMIT;
DROP PROCEDURE repair_mojibake;
