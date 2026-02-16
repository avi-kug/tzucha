# רשימת בדיקות להעלאה לאוויר (Production Deployment)

## 1. הגדרות אבטחה

### קבצי הגדרות (config)
- [ ] שנה את הגדרות מסד הנתונים ב-`config/db.php`
- [ ] שנה את `display_errors` ל-`false` ב-production
- [ ] וודא שיש סיסמאות חזקות למסד נתונים
- [ ] הגדר HTTPS (SSL Certificate)

### קובץ .htaccess
- [ ] ודא שיש `.htaccess` להגנה על תיקיות config
- [ ] חסום גישה לקבצים רגישים (.env, .sql, וכו')

## 2. מסד נתונים

### ייצוא
```bash
# ייצא את מסד הנתונים המקומי
cd c:\xampp\mysql\bin
.\mysqldump.exe -u root tzucha > tzucha_backup.sql
```

### ייבוא בשרת
- [ ] צור מסד נתונים חדש בשרת
- [ ] ייבא את קובץ ה-SQL
- [ ] עדכן את הגדרות החיבור ב-`config/db.php`

## 3. קבצים להעלאה

### קבצים שצריך להעלות:
- ✅ assets/
- ✅ config/
- ✅ controllers/
- ✅ pages/
- ✅ repositories/
- ✅ templates/
- ✅ vendor/
- ✅ views/
- ✅ uploads/ (רק התיקייה, לא את התוכן)
- ✅ .htaccess (קובץ הגנה)
- ✅ index.php (אם יש)

### קבצים שלא להעלות:
- ❌ sql/ (קבצי SQL מקומיים)
- ❌ .git/ (אלא אם כן עושה deploy דרך Git)
- ❌ קבצי test_*.php
- ❌ export_debug.txt
- ❌ README.md מקומיים

## 4. הגדרות PHP ב-Production

הוסף לקובץ `.htaccess` בשורש:
```apache
# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"

# PHP settings
php_flag display_errors Off
php_flag log_errors On
php_value error_log /path/to/error.log
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
```

## 5. בדיקות לאחר ההעלאה

- [ ] בדיקת התחברות למערכת
- [ ] בדיקת כל הדפים (תמיכות, אנשים, משתמשים וכו')
- [ ] בדיקת העלאת קבצים
- [ ] בדיקת ייצוא Excel מכל הטאבים
- [ ] בדיקת אישור תמיכות
- [ ] בדיקת הרשאות משתמשים (admin/editor/viewer)
- [ ] בדיקת HTTPS (שהאתר מאובטח)

## 6. גיבויים (Backups)

הגדר גיבויים אוטומטיים:
- [ ] גיבוי יומי של מסד הנתונים
- [ ] גיבוי שבועי של הקבצים
- [ ] שמור גיבויים במקום חיצוני (Google Drive, Dropbox)

## 7. ביצועים

- [ ] הפעל Gzip compression
- [ ] אופטימיזציה של תמונות
- [ ] שקול להוסיף CDN לקבצים סטטיים

## 8. דומיין ואימייל

- [ ] רכוש דומיין (אם עדיין אין)
- [ ] הגדר DNS לכוון לשרת
- [ ] הגדר אימיילים (@yourdomain.com)

---

## הוראות העלאה צעד אחר צעד

### שיטה 1: FTP (הכי פשוט)

1. **הורד תוכנת FTP** כמו FileZilla
2. **התחבר לשרת** עם פרטי ההתחברות שקיבלת מה-hosting
3. **העלה את כל הקבצים** לתיקיית `public_html/` או `www/`
4. **עדכן את config/db.php** עם פרטי מסד הנתונים של השרת
5. **ייבא את מסד הנתונים** דרך phpMyAdmin של השרת

### שיטה 2: Git (מומלץ)

אם השרת תומך ב-Git:
```bash
# בשרת
cd /path/to/public_html
git clone https://github.com/avi-kug/tzucha.git .
composer install
```

---

## טיפים חשובים

⚠️ **לפני ההעלאה:**
- גבה את כל מסד הנתונים המקומי
- בדוק שאין סיסמאות hardcoded בקוד
- ודא שיש CSRF protection בכל הטפסים

🔒 **אבטחה:**
- שנה את סיסמאות ה-admin אחרי ההעלאה
- הגדר SSL Certificate (Let's Encrypt חינמי)
- עקוב אחר error logs

📧 **תמיכה:**
- שמור את פרטי התמיכה של חברת ה-hosting
- תעד את כל ההגדרות שעשית
