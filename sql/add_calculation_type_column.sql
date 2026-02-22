-- Add calculation_type column to holiday_calculations table
-- This column stores the type of calculation: fixed, multiply, per_item, per_match

ALTER TABLE `holiday_calculations` 
ADD COLUMN `calculation_type` VARCHAR(20) DEFAULT 'fixed' AFTER `name`;

-- Update existing records to have 'fixed' type
UPDATE `holiday_calculations` SET `calculation_type` = 'fixed' WHERE `calculation_type` IS NULL;
