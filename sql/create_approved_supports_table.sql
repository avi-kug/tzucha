-- Create approved_supports table
CREATE TABLE IF NOT EXISTS approved_supports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    support_id INT NOT NULL,
    donor_number VARCHAR(50),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    support_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (support_id) REFERENCES supports(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_support_month (support_month),
    INDEX idx_support_id (support_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
