-- =========================================
-- סקריפט הוספת אינדקסים לשיפור ביצועים
-- תאריך: 17/02/2026
-- =========================================

-- בדיקה אילו אינדקסים כבר קיימים
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('people', 'supports', 'expenses', 'standing_orders_koach', 'standing_orders_achim')
ORDER BY TABLE_NAME, INDEX_NAME;

-- =========================================
-- טבלת PEOPLE - אינדקסים קריטיים
-- =========================================

-- אינדקס על amarchal (שימוש אינטנסיבי בחיפושים וסינונים)
CREATE INDEX idx_amarchal ON people (amarchal);

-- אינדקס על gizbar (שימוש אינטנסיבי בחיפושים וסינונים)
CREATE INDEX idx_gizbar ON people (gizbar);

-- אינדקס על full_name (למיון ואוטומציה)
CREATE INDEX idx_full_name ON people (full_name);

-- אינדקס על family_name + first_name (למיון אלפביתי)
CREATE INDEX idx_names ON people (family_name, first_name);

-- אינדקס על donor_number (חיפוש תורמים)
CREATE INDEX idx_donor_number ON people (donor_number);

-- אינדקס על husband_id / wife_id (חיפוש לפי ת.ז.)
CREATE INDEX idx_husband_id ON people (husband_id);
CREATE INDEX idx_wife_id ON people (wife_id);

-- אינדקס על phone_id (אינטגרציה עם מערכת כבוד)
-- CREATE INDEX idx_phone_id ON people (phone_id);
-- הסר את ההערה אם השדה קיים

-- =========================================
-- טבלת SUPPORTS - אינדקסים (כבר קיימים חלקית)
-- =========================================

-- אינדקסים אלה כבר בקובץ create_supports_table.sql:
-- INDEX idx_person_id (person_id) - כבר קיים
-- INDEX idx_id_number (id_number) - כבר קיים
-- INDEX idx_created_at (created_at) - כבר קיים

-- אינדקס נוסף על support_month (סינון לפי חודש)
CREATE INDEX idx_support_month ON supports (support_month);

-- אינדקס על household_members (חישובים וסטטיסטיקות)
CREATE INDEX idx_household_members ON supports (household_members);

-- =========================================
-- טבלת EXPENSES - אינדקסים
-- =========================================

-- אינדקס על תאריך (למיון וסינון לפי תאריכים)
CREATE INDEX idx_date ON expenses (date);

-- אינדקס על סכום (למיון וסכימות)
CREATE INDEX idx_amount ON expenses (amount);

-- אינדקס על category (סינון לפי קטגוריה)
CREATE INDEX idx_category ON expenses (category);

-- אינדקס על department (סינון לפי אגף)
CREATE INDEX idx_department ON expenses (department);

-- אינדקס משולב: תאריך + קטגוריה (דוחות)
CREATE INDEX idx_date_category ON expenses (date, category);

-- =========================================
-- טבלאות STANDING_ORDERS - אינדקסים
-- =========================================

-- standing_orders_koach
CREATE INDEX idx_koach_person_id ON standing_orders_koach (person_id);
CREATE INDEX idx_koach_donation_date ON standing_orders_koach (donation_date);
CREATE INDEX idx_koach_amount ON standing_orders_koach (amount);

-- standing_orders_achim
CREATE INDEX idx_achim_person_id ON standing_orders_achim (person_id);
CREATE INDEX idx_achim_donation_date ON standing_orders_achim (donation_date);
CREATE INDEX idx_achim_amount ON standing_orders_achim (amount);

-- =========================================
-- טבלת CASH_DONATIONS - אינדקסים (אם קיימת)
-- =========================================

-- CREATE INDEX idx_cash_date ON cash_donations (date);
-- CREATE INDEX idx_cash_person_id ON cash_donations (person_id);
-- CREATE INDEX idx_cash_amount ON cash_donations (amount);
-- הסר את ההערה אם הטבלה קיימת

-- =========================================
-- בדיקה סופית - הצג את כל האינדקסים החדשים
-- =========================================

SHOW INDEX FROM people;
SHOW INDEX FROM supports;
SHOW INDEX FROM expenses;
SHOW INDEX FROM standing_orders_koach;
SHOW INDEX FROM standing_orders_achim;

-- =========================================
-- סטטיסטיקות לאחר הוספת אינדקסים
-- =========================================

SELECT 
    'people' AS table_name,
    COUNT(*) AS total_rows,
    COUNT(DISTINCT amarchal) AS distinct_amarchal,
    COUNT(DISTINCT gizbar) AS distinct_gizbar
FROM people

UNION ALL

SELECT 
    'supports' AS table_name,
    COUNT(*) AS total_rows,
    COUNT(DISTINCT person_id) AS distinct_persons,
    COUNT(DISTINCT support_month) AS distinct_months
FROM supports

UNION ALL

SELECT 
    'expenses' AS table_name,
    COUNT(*) AS total_rows,
    COUNT(DISTINCT category) AS distinct_categories,
    COUNT(DISTINCT department) AS distinct_departments
FROM expenses;

-- =========================================
-- הודעת סיום
-- =========================================
SELECT '✅ אינדקסים נוספו בהצלחה! המערכת אמורה להיות מהירה יותר כעת.' AS status;
