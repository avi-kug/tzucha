-- הוספת שדה לבחירה אישית האם לכלול הוצאה חריגה בחישוב
ALTER TABLE supports 
ADD COLUMN include_exceptional_in_calc TINYINT(1) DEFAULT 1 COMMENT 'האם לכלול הוצאה חריגה קבועה בחישוב';

-- עדכון רשומות קיימות (ברירת מחדל: כן)
UPDATE supports SET include_exceptional_in_calc = 1 WHERE include_exceptional_in_calc IS NULL;
