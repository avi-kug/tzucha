# מדריך העלאה מהיר לפרויקט Tzucha

## ✅ מה מוכן:
- 📦 קובץ מסד נתונים: `deployment/tzucha_2026-02-16.sql` (1.61 MB)
- 🔒 קבצי אבטחה: `.htaccess` מוכנים
- 📋 רשימת בדיקות: `DEPLOYMENT_CHECKLIST.md`

---

## 🚀 שלבים להעלאה (בסדר!)

### **1. בחר Hosting Provider**

**מומלץ לפרויקט PHP:**
- **Hostinger** (זול, תמיכה בעברית) - https://hostinger.com
  - תוכנית: Premium/Business (~₪20-40/חודש)
  - כולל: SSL, MySQL, Email
  
- **SiteGround** (אמין מאוד) - https://siteground.com
  - תוכנית: StartUp (~$4/חודש)

---

### **2. אחרי רכישת Hosting**

שמור את הפרטים הבאים (תקבל אותם באימייל):

```
✓ FTP Host: _________________
✓ FTP Username: _____________
✓ FTP Password: _____________
✓ MySQL Host: _______________
✓ MySQL Database Name: ______
✓ MySQL Username: ___________
✓ MySQL Password: ___________
✓ cPanel URL: _______________
```

---

### **3. העלאת מסד הנתונים**

1. **כנס ל-phpMyAdmin** בשרת (דרך cPanel)
2. **צור מסד נתונים חדש** (שם: `tzucha` או כל שם אחר)
3. **בחר את המסד** ולחץ **Import**
4. **העלה את הקובץ**: `deployment/tzucha_2026-02-16.sql`
5. **לחץ Go** ✓

---

### **4. עדכון הגדרות חיבור**

**ערוך את הקובץ** `config/db.php` **בשרת**:

```php
$host = 'localhost';  // או מה שה-hosting נתן
$dbname = 'YOUR_DB_NAME_HERE';  // מהשלב 3
$username = 'YOUR_DB_USER_HERE';  // מהשלב 2
$password = 'YOUR_DB_PASSWORD_HERE';  // מהשלב 2
```

**⚠️ חשוב:** אל תשנה את הקובץ המקומי! רק בשרת!

---

### **5. העלאת קבצי הפרויקט**

**אפשרות א' - FTP (FileZilla):**

1. הורד **FileZilla**: https://filezilla-project.org/
2. התחבר עם פרטי FTP מהשלב 2
3. העלה את התיקיות הבאות ל-`public_html/`:

```
העלה את:
✓ assets/
✓ config/
✓ controllers/
✓ pages/
✓ repositories/
✓ templates/
✓ vendor/
✓ views/
✓ uploads/ (רק התיקייה, לא קבצים)
✓ .htaccess

אל תעלה:
✗ deployment/
✗ sql/
✗ .git/
✗ README.md מקומיים
```

**אפשרות ב' - ZIP וחילוץ:**

1. דחוס את כל הפרויקט ל-ZIP (ללא deployment, sql, .git)
2. העלה דרך **File Manager** ב-cPanel
3. חלץ את הקובץ

---

### **6. הגדרות אבטחה**

**א. ודא ש-.htaccess פעיל**

הקבצים הבאים צריכים להיות בשרת:
- `/.htaccess` (בשורש)
- `/config/.htaccess` (בתוך config)

**ב. הפעל SSL (HTTPS)**

ב-cPanel:
1. לך ל-**SSL/TLS**
2. בחר **Let's Encrypt** (חינם)
3. התקן עבור הדומיין שלך

**ג. שנה סיסמאות Admin**

1. התחבר למערכת
2. לך ל**משתמשים**
3. **שנה** את סיסמת המשתמש admin לסיסמה חזקה!

---

### **7. בדיקות סופיות**

גש ל: `https://yourdomain.com` ובדוק:

- [ ] הדף הראשי נטען
- [ ] התחברות עובדת
- [ ] דף תמיכות נטען
- [ ] דף אנשים נטען
- [ ] העלאת קבצים עובדת
- [ ] ייצוא Excel עובד (3 טאבים)
- [ ] אישור תמיכות עובד
- [ ] הרשאות משתמשים עובדות

---

### **8. גיבויים (חשוב!)**

הגדר גיבוי אוטומטי ב-cPanel:
- **תדירות**: יומי למסד נתונים, שבועי לקבצים
- **שמירה**: גם בשרת וגם במקום חיצוני (Google Drive)

---

## 📞 תמיכה

**בעיות נפוצות:**

**שגיאה: "Error establishing database connection"**
→ בדוק את `config/db.php` - פרטי החיבור נכונים?

**שגיאה: "500 Internal Server Error"**
→ בדוק error logs ב-cPanel → Error Log

**קבצים לא נטענים (CSS/JS)**
→ ודא שהנתיב נכון, PHP version תואם (7.4+)

---

## ✨ הצלחה!

אחרי שהכל עובד:
1. שתף את הקישור עם המשתמשים
2. הדרך אותם איך להתחבר
3. עקוב אחר ה-error logs בימים הראשונים
4. הגדר גיבויים אוטומטיים

**🎉 מזל טוב - הפרויקט באוויר!**
