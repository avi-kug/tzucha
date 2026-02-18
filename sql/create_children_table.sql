-- טבלת מאגר ילדים
CREATE TABLE IF NOT EXISTS children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_husband_id VARCHAR(20) NOT NULL COMMENT 'תעודת זהות של האב - שיוך להורה',
    child_name VARCHAR(255) NOT NULL COMMENT 'שם הילד',
    gender ENUM('זכר', 'נקבה') NOT NULL COMMENT 'מין',
    birth_day INT COMMENT 'יום לידה',
    birth_month VARCHAR(50) COMMENT 'חודש לידה עברי',
    birth_year INT COMMENT 'שנה עברית',
    birth_date_gregorian DATE COMMENT 'תאריך לידה לועזי',
    child_id VARCHAR(20) COMMENT 'תעודת זהות של הילד',
    notes TEXT COMMENT 'הערות',
    status ENUM('רווק', 'מאורס', 'נשוי', 'גרוש') DEFAULT 'רווק' COMMENT 'סטטוס',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_id (parent_husband_id),
    INDEX idx_status (status),
    INDEX idx_child_id (child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
