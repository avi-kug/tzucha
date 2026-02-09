# התקנת מערכת התמיכות - מדריך מהיר

## שלבי ההתקנה

### 1. יצירת טבלת התמיכות
הרץ את הפקודה הבאה במסד הנתונים שלך:

```bash
mysql -u root -p tzucha < sql/create_supports_table.sql
```

או העתק את התוכן של [sql/create_supports_table.sql](sql/create_supports_table.sql) והרץ אותו דרך phpMyAdmin.

### 2. וידוא הרשאות משתמש

ודא שלמשתמש שלך יש הרשאה ל-'supports'. אם אתה Admin, כבר יש לך גישה.

אם לא, הוסף הרשאה דרך ממשק המשתמשים או הרץ:

```sql
INSERT INTO user_permissions (user_id, permission_key) 
VALUES ([USER_ID], 'supports');
```

### 3. בדיקה

1. גש לדף התמיכות: `http://localhost/tzucha/pages/supports.php`
2. נסה להוסיף תמיכה חדשה
3. בדוק שהחישובים עובדים כראוי
4. נסה לייצא ולייבא קובץ Excel

## קבצים שנוצרו / עודכנו

### Backend:
- ✅ `sql/create_supports_table.sql` - סקריפט יצירת טבלה
- ✅ `repositories/SupportsRepository.php` - ניהול נתוני תמיכות
- ✅ `pages/supports_api.php` - API endpoints
- ✅ `pages/supports.php` - דף ממשק המשתמש

### Frontend:
- ✅ `assets/css/supports.css` - עיצוב
- ✅ `assets/js/supports.js` - לוגיקה

### תיעוד:
- ✅ `SUPPORTS_README.md` - מדריך שימוש מלא
- ✅ `INSTALL_SUPPORTS.md` - מדריך התקנה זה

## בעיות נפוצות

### שגיאה: "Table 'supports' doesn't exist"
**פתרון:** הרץ את סקריפט SQL ליצירת הטבלה.

### שגיאה: "אין הרשאה לגישה לעמוד זה"
**פתרון:** ודא שלמשתמש שלך יש הרשאת 'supports'.

### שגיאה בייבוא Excel
**פתרון:** ודא שהתיקייה `uploads/temp` קיימת ויש הרשאות כתיבה:
```bash
mkdir -p uploads/temp
chmod 777 uploads/temp
```

### שגיאה: "Failed to upload file"
**פתרון:** בדוק את הגדרות PHP:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

## בדיקת התקנה מוצלחת

✅ ניתן לגשת לדף התמיכות  
✅ ניתן להוסיף רשומת תמיכה חדשה  
✅ החישובים מתבצעים באופן אוטומטי  
✅ ניתן לעבור בין הטאבים  
✅ ייצוא ל-Excel עובד  
✅ ייבוא מ-Excel עובד  

## תמיכה

לשאלות נוספות, עיין ב-[SUPPORTS_README.md](SUPPORTS_README.md) למדריך מלא.
