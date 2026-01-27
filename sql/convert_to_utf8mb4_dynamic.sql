-- Dynamic conversion of selected tables to utf8mb4 if they exist
SET NAMES utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;

START TRANSACTION;
ALTER DATABASE `tzucha` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE convert_to_utf8mb4()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE t VARCHAR(64);
  DECLARE cur CURSOR FOR 
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
      AND table_name IN ('departments','categories','expense_types','paid_by_options','from_accounts','stores','fixed_expenses','regular_expenses','summary_expenses');
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO t;
    IF done THEN
      LEAVE read_loop;
    END IF;
    SET @sql = CONCAT('ALTER TABLE `', t, '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE cur;
END //
DELIMITER ;

CALL convert_to_utf8mb4();
DROP PROCEDURE convert_to_utf8mb4;

COMMIT;
