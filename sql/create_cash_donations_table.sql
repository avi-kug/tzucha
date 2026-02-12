-- טבלת תרומות מזומן מ-ipapp.org
CREATE TABLE IF NOT EXISTS cash_donations (
    id INT PRIMARY KEY,  -- ה-ID מ-ipapp.org
    id_alfon INT,  -- מס' אלפון (מזהה ראשי)
    name VARCHAR(100),
    family VARCHAR(100),
    address VARCHAR(200),
    city VARCHAR(100),
    amount DECIMAL(10,2),
    notes TEXT,
    date DATE,
    heb_date VARCHAR(50),
    project VARCHAR(100),
    source VARCHAR(100),
    name_gabay VARCHAR(100),
    name_amarkal VARCHAR(100),
    creating_date DATE,
    receipt_date DATE,
    receipt_generated VARCHAR(50),
    id_project INT,
    id_gabay INT,
    record TEXT,
    synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_alfon (id_alfon),
    INDEX idx_name (name, family),
    INDEX idx_project (project),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
