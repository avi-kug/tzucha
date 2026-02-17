-- =========================================
-- סקריפט בטוח להוספת אינדקסים
-- מוסיף רק אינדקסים שלא קיימים
-- תאריך: 17/02/2026
-- =========================================

-- בדיקה אילו אינדקסים כבר קיימים
SELECT '=== אינדקסים קיימים ===' AS info;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('people', 'supports', 'expenses', 'standing_orders_koach', 'standing_orders_achim')
ORDER BY TABLE_NAME, INDEX_NAME;
