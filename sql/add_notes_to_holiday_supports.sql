-- Add notes column to holiday_supports table

ALTER TABLE `holiday_supports` 
ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `support_date`;
