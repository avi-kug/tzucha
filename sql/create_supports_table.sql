-- יצירת טבלת תמיכות
CREATE TABLE IF NOT EXISTS supports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- פרטים אישיים
    position_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'שם עמדה',
    first_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'שם פרטי',
    last_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'שם משפחה',
    id_number VARCHAR(20) NULL COMMENT 'מספר זהות',
    city VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'עיר',
    street VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'רחוב',
    phone VARCHAR(50) NULL COMMENT 'מס טל',
    
    -- מבנה משפחתי
    household_members INT NULL DEFAULT 0 COMMENT 'מס נפשות בבית (כולל ההורים)',
    married_children INT NULL DEFAULT 0 COMMENT 'מס ילדים נשואים',
    
    -- הכנסות - מקורות לימודים/עבודה
    study_work_place_1 VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'מקום לימודים / עבודה 1',
    income_scholarship_1 DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'סכום הכנסה / מלגה 1',
    study_work_place_2 VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'מקום לימודים / עבודה 2',
    income_scholarship_2 DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'סכום הכנסה / מלגה 2',
    
    -- הכנסות - קצבאות
    child_allowance DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'קצבת ילדים',
    survivor_allowance DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'קצבת שארים',
    disability_allowance DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'קצבת נכות',
    income_guarantee DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'הבטחת הכנסה',
    income_supplement DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'השלמת הכנסה',
    rent_assistance DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'סיוע בשכר דירה',
    other_allowance_source VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'מקור הקצבה אחר',
    other_allowance_amount DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'סכום',
    
    -- הוצאות
    housing_expenses DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'הוצאות דיור',
    tuition_expenses DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'הוצאות שכר לימוד (סכום כולל למוסדות)',
    recurring_exceptional_expense DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'הוצאה חריגה קבועה',
    exceptional_expense_details TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'פירוט - הוצאה חריגה',
    
    -- הערות ומידע נוסף
    difficulty_reason TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'פרט מה סיבת הקושי',
    notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'הערות',
    
    -- פרטי חשבון בנק
    account_holder_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'שם בעל החשבון',
    bank_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'בנק',
    branch_number VARCHAR(50) NULL COMMENT 'סניף',
    account_number VARCHAR(50) NULL COMMENT 'מס חשבון',
    
    -- מידע נוסף
    support_requester_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'שם מבקש התמיכה',
    transaction_number VARCHAR(100) NULL COMMENT 'מספר עסקה',
    
    -- Foreign Key
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE SET NULL,
    INDEX idx_person_id (person_id),
    INDEX idx_id_number (id_number),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='טבלת תמיכות';
