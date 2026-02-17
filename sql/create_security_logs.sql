-- Security Logs Table for Audit Trail
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    user_id INT NULL,
    username VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    details JSON,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_severity (severity),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some retention (optional - delete logs older than 1 year)
-- CREATE EVENT IF NOT EXISTS cleanup_old_security_logs
-- ON SCHEDULE EVERY 1 DAY
-- DO DELETE FROM security_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
