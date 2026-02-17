-- Add logout tracking to login_attempts table
-- This allows us to track manual logouts vs session timeouts

ALTER TABLE login_attempts 
ADD COLUMN is_manual_logout TINYINT(1) DEFAULT 0 AFTER success;

CREATE INDEX idx_manual_logout ON login_attempts(username, ip_address, is_manual_logout, attempted_at);
