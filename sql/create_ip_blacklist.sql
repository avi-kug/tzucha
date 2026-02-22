-- Create IP Blacklist table for blocking suspicious IPs
-- Used by check_ip_blacklist() function in auth.php

CREATE TABLE IF NOT EXISTS ip_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL DEFAULT NULL,
    blocked_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_blocked_until (blocked_until),
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some common malicious IPs (example - update with your own)
-- INSERT INTO ip_blacklist (ip_address, reason, blocked_until) VALUES
-- ('192.168.1.100', 'Multiple failed login attempts', NULL),
-- ('10.0.0.50', 'Suspicious activity', '2026-12-31 23:59:59');

-- Create index on security_logs for faster queries
ALTER TABLE security_logs 
    ADD INDEX IF NOT EXISTS idx_ip_action (ip_address, action),
    ADD INDEX IF NOT EXISTS idx_timestamp (timestamp);
