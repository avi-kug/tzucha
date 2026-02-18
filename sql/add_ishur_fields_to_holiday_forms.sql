-- Add ishur fields to holiday_forms table for expanded support requests

ALTER TABLE `holiday_forms` 
ADD COLUMN `ishur1` VARCHAR(200) DEFAULT NULL COMMENT 'כפוטא',
ADD COLUMN `ishur1_` INT(11) DEFAULT 0 COMMENT 'כמות (כפוטא)',
ADD COLUMN `ishur_1_` VARCHAR(200) DEFAULT NULL COMMENT 'עבור מי (כפוטא)',
ADD COLUMN `ishur2` VARCHAR(200) DEFAULT NULL COMMENT 'כובע רגיל',
ADD COLUMN `ishur2_` INT(11) DEFAULT 0 COMMENT 'כמות (כובע רגיל)',
ADD COLUMN `ishur_2_` VARCHAR(200) DEFAULT NULL COMMENT 'עבור מי (כובע רגיל)',
ADD COLUMN `ishur3` VARCHAR(200) DEFAULT NULL COMMENT 'כובע חסידי / ירושלמי',
ADD COLUMN `ishur3_` INT(11) DEFAULT 0 COMMENT 'כמות (כובע חסידי)',
ADD COLUMN `ishur_3_` VARCHAR(200) DEFAULT NULL COMMENT 'עבור מי (כובע חסידי)',
ADD COLUMN `ishur` TEXT DEFAULT NULL COMMENT 'בקשה לתמיכה מורחבת';
