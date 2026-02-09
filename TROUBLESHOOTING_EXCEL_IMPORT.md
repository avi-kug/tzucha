# פתרון בעיות בייבוא קבצי Excel

## בעיות נפוצות ופתרונות

### 1. שגיאה: "הקובץ לא נמצא או לא ניתן לקרוא אותו"
**סיבה**: קובץ ה-Excel לא הועלה כראוי או שאין הרשאות קריאה.
**פתרון**:
- ודא שהקובץ הועלה בהצלחה
- בדוק הרשאות תיקיית uploads/temp

### 2. שגיאה: "Allowed memory size exhausted"
**סיבה**: הקובץ גדול מדי לגבול הזיכרון של PHP.
**פתרון**:
עדכן את `php.ini`:
```ini
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

### 3. שגיאה: "No supporting reader found"
**סיבה**: הקובץ לא בפורמט Excel תקין.
**פתרון**:
- שמור את הקובץ מחדש כ-.xlsx
- נסה לפתוח את הקובץ ב-Excel ולשמור אותו מחדש
- ודא שהקובץ לא פגום

### 4. שגיאה: "Undefined offset" או "Cannot access empty property"
**סיבה**: מבנה הקובץ לא תואם למבנה הצפוי.
**פתרון**:
- ודא שהשורה הראשונה מכילה כותרות בעברית מדויקות
- ודא שאין שורות ריקות בתחילת הקובץ
- ייצא קובץ לדוגמה מהמערכת והשתמש באותו פורמט

### 5. קובץ "נתקע" בעת העלאה
**סיבה**: timeout או זיכרון לא מספיק.
**פתרון**:
- הקטן את הקובץ (פצל לכמה קבצים קטנים יותר)
- העלה פחות שורות בכל פעם
- הגדל את `max_execution_time` ב-php.ini

### 6. שגיאה: "Duplicate entry '0' for key 'PRIMARY'"
**סיבה**: 
- יש שמות כפולים באותו קובץ Excel (שתי שורות עם אותו "שם ומשפחה ביחד")
- בעיה עם AUTO_INCREMENT בטבלה

**פתרון**:
1. בדוק אם יש שורות כפולות בקובץ Excel - כל שם צריך להופיע פעם אחת בלבד
2. הסר שורות כפולות לפני ייבוא
3. אם הבעיה נמשכת, בדוק את AUTO_INCREMENT של הטבלה:

```sql
-- בדיקת מצב AUTO_INCREMENT
SELECT AUTO_INCREMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'tzucha' 
AND TABLE_NAME = 'people';

-- תיקון AUTO_INCREMENT אם הוא 0 או נמוך מדי
ALTER TABLE people AUTO_INCREMENT = 1;
```

## כיצד למצוא את השגיאה המדויקת?

השגיאות נרשמות ב-log של PHP. מיקום ה-log:
- **XAMPP**: `C:\xampp\php\logs\php_error_log`
- **Linux**: `/var/log/php/error.log` או `/var/log/apache2/error.log`

חפש שורות שמתחילות ב:
- `Import People Error:`
- `Import Amarchal/Gizbar Error:`

## פורמט קבצי Excel הנדרש

### ייבוא אנשים
כותרות חובה בשורה הראשונה:
- שם ומשפחה ביחד (או: משפחה + שם)
- שאר העמודות אופציונליות

### ייבוא אמרכלים/גזברים
כותרות חובה:
- אמרכל/גזבר (לפי סוג הייבוא)
- שאר העמודות אופציונליות

## בדיקת הגדרות PHP נוכחיות

הוסף קובץ `phpinfo.php` בתיקיית הבסיס:
```php
<?php phpinfo(); ?>
```

גש ל-`http://localhost/tzucha/phpinfo.php` ובדוק:
- `memory_limit`
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`

**חשוב**: מחק את הקובץ לאחר הבדיקה!

## עזרה נוספת

אם הבעיה נמשכת:
1. בדוק את ה-error log של PHP
2. נסה קובץ קטן יותר (5-10 שורות)
3. ודא שהקובץ נשמר כ-.xlsx (לא .xls או .csv)
4. נסה לייצא קובץ מהמערכת ולייבא אותו בחזרה
