-- =========================================
-- בדיקת ביצועי MySQL - שאילתות ואינדקסים
-- =========================================

-- 1. בדיקת גרסת MySQL
SELECT VERSION() as mysql_version;

-- 2. בדיקת גודל טבלאות
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
    ROUND((index_length / 1024 / 1024), 2) AS index_mb
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
  AND table_name IN ('people', 'supports', 'standing_orders_koach', 'standing_orders_achim', 
                      'regular_expenses', 'fixed_expenses', 'summary_expenses')
ORDER BY (data_length + index_length) DESC;

-- 3. בדיקת אינדקסים קיימים
SELECT 
    TABLE_NAME as 'טבלה',
    INDEX_NAME as 'אינדקס',
    COLUMN_NAME as 'עמודה',
    SEQ_IN_INDEX as 'סדר',
    CARDINALITY as 'ייחודיות',
    INDEX_TYPE as 'סוג'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('people', 'supports', 'standing_orders_koach', 'standing_orders_achim')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- 4. בדיקת שימוש באינדקסים (דורש הרצת שאילתות קודם)
SHOW STATUS LIKE 'Handler_read%';

-- 5. בדיקת זמן ביצוע של שאילתות קריטיות עם EXPLAIN
-- (הסר את EXPLAIN כדי לבדוק זמן ביצוע בפועל)

EXPLAIN SELECT * FROM people WHERE amarchal = 'כהן' LIMIT 10;
EXPLAIN SELECT * FROM people WHERE gizbar = 'לוי' LIMIT 10;
EXPLAIN SELECT DISTINCT amarchal FROM people WHERE amarchal IS NOT NULL AND amarchal <> '';
EXPLAIN SELECT p.*, SUM(k.amount) as total 
FROM people p 
LEFT JOIN standing_orders_koach k ON k.person_id = p.id 
GROUP BY p.id 
LIMIT 50;

-- 6. בדיקת שאילתות איטיות (אם Slow Query Log מופעל)
-- SHOW VARIABLES LIKE 'slow_query_log%';
-- השתמש ב: SET GLOBAL slow_query_log = 'ON'; כדי להפעיל

-- 7. בדיקת חיבורים פעילים
SHOW PROCESSLIST;

-- 8. בדיקת Cache של שאילתות
SHOW STATUS LIKE 'Qcache%';

-- 9. בדיקת InnoDB Buffer Pool (חשוב לביצועים)
SHOW STATUS LIKE 'Innodb_buffer_pool%';

-- 10. בדוק אם יש טבלאות ללא אינדקסים חשובים
SELECT 
    t.TABLE_NAME,
    t.TABLE_ROWS,
    COUNT(DISTINCT s.INDEX_NAME) as num_indexes
FROM information_schema.TABLES t
LEFT JOIN information_schema.STATISTICS s 
    ON t.TABLE_SCHEMA = s.TABLE_SCHEMA 
    AND t.TABLE_NAME = s.TABLE_NAME
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_TYPE = 'BASE TABLE'
GROUP BY t.TABLE_NAME, t.TABLE_ROWS
HAVING num_indexes < 2
ORDER BY t.TABLE_ROWS DESC;

-- 11. סטטיסטיקת queries לפי זמן (אם Performance Schema מופעל)
-- SELECT * FROM performance_schema.events_statements_summary_by_digest 
-- ORDER BY SUM_TIMER_WAIT DESC LIMIT 10;
