-- Add phone_id column to people table for Kavod.org.il integration
ALTER TABLE people ADD COLUMN phone_id VARCHAR(50) DEFAULT NULL AFTER wife_id;
