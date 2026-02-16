-- Add support_amount and support_month columns to supports table
ALTER TABLE supports 
ADD COLUMN support_amount DECIMAL(10,2) DEFAULT 0 AFTER include_exceptional_in_calc,
ADD COLUMN support_month VARCHAR(7) DEFAULT '' AFTER support_amount;
