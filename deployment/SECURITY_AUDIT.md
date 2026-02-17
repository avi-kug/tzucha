# 🔒 דוח בדיקת אבטחה וסטביליות - מערכת צוחה

**תאריך בדיקה:** 17 בפברואר 2026  
**גרסה:** Production Readiness Check  
**סטטוס כללי:** ⚠️ **דורש תיקונים קריטיים לפני העלאה**

---

## 📊 סיכום מהיר

| קטגוריה | מצב | פרטים |
|---------|-----|--------|
| **SQL Injection** | ✅ מוגן | Prepared statements בכל המקומות |
| **XSS Protection** | ✅ מוגן | htmlspecialchars בשימוש נרחב |
| **CSRF Protection** | ✅ מוגן | טוקנים בכל הטפסים |
| **Session Security** | ✅ מוגן | הגדרות חזקות |
| **File Upload** | ✅ מוגן | ולידציה חזקה |
| **הצגת שגיאות** | ❌ **קריטי** | חשיפת שגיאות ב-production |
| **קובץ .env** | ❌ **קריטי** | חסר .gitignore |
| **Headers אבטחה** | ✅ מוגן | CSP ו-Security Headers |
| **Password Policy** | ✅ חזק | Argon2ID + מדיניות חזקה |
| **Rate Limiting** | ⚠️ חסר | אין הגבלה על login |

---

## 🔴 בעיות קריטיות - **לתקן לפני העלאה!**

### 1️⃣ **חשיפת שגיאות בפרודקשן** 🚨

**קובץ:** [pages/people.php](c:/xampp/htdocs/tzucha/pages/people.php#L2-L3)

**הבעיה:**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);  // ← מסוכן!!!
```

**למה זה מסוכן:**
- חושף מבנה מסד נתונים
- חושף נתיבי קבצים במערכת
- חושף שמות משתמשים ומידע טכני
- מאפשר לתוקף ללמוד על המערכת

**תיקון מיידי:**
```php
// הוסף בתחילת הקובץ - זיהוי סביבה
$isProduction = !empty($_SERVER['SERVER_NAME']) && 
                strpos($_SERVER['SERVER_NAME'], 'localhost') === false;

if ($isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/to/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```

**או פשוט יותר - בקובץ .htaccess:**
```apache
<IfModule mod_php.c>
    php_flag display_errors Off
    php_value error_reporting 0
    php_flag log_errors On
    php_value error_log /path/to/errors.log
</IfModule>
```

---

### 2️⃣ **קובץ .env חשוף** 🚨

**הבעיה:**
- אין קובץ `.gitignore` בתיקיית הפרויקט
- קובץ `.env` מכיל סיסמאות:
  - סיסמת MySQL: `Ak8518180`
  - סיסמת Gmail: `ocpf iidy apnr qqib`
  - סיסמאות למערכות חיצוניות

**תיקון מיידי - צור קובץ .gitignore:**
```gitignore
# Environment variables
.env
.env.local
.env.production

# Uploads and temp
uploads/invoices/*.pdf
uploads/temp/*
!uploads/temp/.gitignore
storage/*
!storage/.gitignore

# Logs
*.log
logs/

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Vendor (אם לא צריך)
# vendor/

# Sensitive
config/db.production.php
```

**חשוב:** אחרי יצירת הקובץ:
```bash
git rm --cached .env
git commit -m "Remove .env from repository"
git push
```

**ב-Azure:** הגדר את המשתנים ב-Application Settings במקום .env

---

### 3️⃣ **חסר הגנה על קובץ .env** ⚠️

**הוסף .htaccess לתיקיה הראשית:**
```apache
# Protect .env file
<Files .env>
    Require all denied
</Files>

# Protect sensitive files
<FilesMatch "\.(env|git|sql|md|log)$">
    Require all denied
</FilesMatch>
```

---

## 🟡 בעיות בינוניות - **מומלץ מאוד לתקן**

### 4️⃣ **חסר Rate Limiting על Login**

**הבעיה:**
תוקף יכול לנסות אינסוף סיסמאות ללא הגבלה.

**פתרון מהיר - הוסף לקובץ auth.php:**
```php
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if exceeded
    if ($data['count'] >= $max_attempts) {
        $wait_time = $time_window - (time() - $data['first_attempt']);
        throw new Exception("יותר מדי נסיונות. נסה שוב בעוד " . ceil($wait_time/60) . " דקות.");
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// שימוש בעמוד login:
// check_rate_limit($_POST['username']);
```

---

### 5️⃣ **חסר Logging למערכת**

**הוסף מערכת logging:**
```php
function security_log($action, $details = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'action' => $action,
        'details' => $details
    ];
    
    file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);
}

// שימוש:
// security_log('login_success', ['username' => $username]);
// security_log('login_failed', ['username' => $username, 'reason' => 'wrong_password']);
```

---

### 6️⃣ **חסר Backup אוטומטי**

**צור סקריפט גיבוי:**
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"
DB_NAME="tzucha"
DB_USER="root"
DB_PASS="Ak8518180"

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" /path/to/uploads

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

---

## ✅ דברים שעובדים מצוין!

### 🔒 אבטחה חזקה שכבר קיימת:

1. **✅ SQL Injection Protection**
   - כל השאילתות משתמשות ב-Prepared Statements
   - אין concatenation של קלט משתמש לשאילתות
   ```php
   $stmt = $pdo->prepare("SELECT * FROM people WHERE amarchal = ?");
   $stmt->execute([$data['name']]);
   ```

2. **✅ XSS Protection**
   - שימוש עקבי ב-`htmlspecialchars()` עם `ENT_QUOTES` ו-`UTF-8`
   - כל הפלט מסונן
   ```php
   echo htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
   ```

3. **✅ CSRF Protection**
   - טוקנים בכל הטפסים
   - ולידציה בצד שרת
   - שימוש ב-`hash_equals()` (timing-safe)
   ```php
   function csrf_validate() {
       $token = $_POST['csrf_token'] ?? '';
       return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
   }
   ```

4. **✅ Session Security**
   - `httponly` cookies
   - `secure` flag (בהתאם ל-HTTPS)
   - `SameSite=Lax`
   ```php
   session_set_cookie_params([
       'lifetime' => 0,
       'httponly' => true,
       'secure' => $secure,
       'samesite' => 'Lax'
   ]);
   ```

5. **✅ Security Headers**
   - X-Frame-Options: DENY
   - X-Content-Type-Options: nosniff
   - Content-Security-Policy
   - Referrer-Policy

6. **✅ Password Security**
   - Argon2ID hashing (חזק מאוד!)
   - מדיניות סיסמה חזקה (8+ תווים, אותיות, ספרות)
   ```php
   hash_password($password) {
       return password_hash($password, PASSWORD_ARGON2ID, [
           'memory_cost' => 1 << 16,
           'time_cost' => 4,
           'threads' => 2
       ]);
   }
   ```

7. **✅ File Upload Security**
   - בדיקת סוג קובץ (extension + MIME type)
   - הגבלת גודל (10MB)
   - קבצים לא נשמרים בשרת (רק בזיכרון)
   ```php
   $finfo = new finfo(FILEINFO_MIME_TYPE);
   $mime = $finfo->file($tmp);
   if (!in_array($mime, $allowedMime, true)) { ... }
   ```

8. **✅ Directory Protection**
   - קבצי .htaccess חוסמים גישה ל-config/, sql/, uploads/
   - חסימת ריצת PHP ב-uploads/
   ```apache
   <FilesMatch "\.(php|php3|php4|php5|phtml)$">
       Require all denied
   </FilesMatch>
   ```

9. **✅ Authentication & Authorization**
   - מערכת הרשאות מבוססת roles
   - בדיקת הרשאות בכל עמוד
   ```php
   auth_require_login($pdo);
   auth_require_permission('people');
   ```

---

## 📋 צ'ק ליסט לפני העלאה

### חובה (קריטי):
- [ ] **תקן display_errors=0 בפרודקשן**
- [ ] **צור .gitignore והסר .env מ-Git**
- [ ] **הוסף .htaccess להגנה על .env**
- [ ] **העתק .env ל-.env.example (ללא סיסמאות אמיתיות)**
- [ ] **הגדר משתני סביבה ב-Azure Application Settings**
- [ ] **בדוק שכל ה-.htaccess files קיימים בשרת**
- [ ] **הגדר HTTPS והפעל את ה-secure flag ב-session**
- [ ] **שנה סיסמאות של מסד הנתונים לפרודקשן**

### מומלץ מאוד:
- [ ] הוסף Rate Limiting ל-login
- [ ] הוסף מערכת Logging
- [ ] הגדר גיבויים אוטומטיים
- [ ] בדוק שכל הדפים פועלים ב-HTTPS
- [ ] הוסף monitoring (CPU, RAM, errors)
- [ ] צור דף 404/500 מותאם אישית

### טוב לעשות:
- [ ] הוסף 2FA למשתמשי admin
- [ ] הוסף IP whitelist ל-/sql/ directory
- [ ] שקול WAF (Web Application Firewall)
- [ ] הגדר email alerts על שגיאות קריטיות

---

## 🔧 תיקונים מוכנים להרצה

הכנתי לך תיקונים מוכנים - רוצה שאריץ אותם?

1. **תיקון display_errors** - אוסיף זיהוי סביבה אוטומטי
2. **יצירת .gitignore** - קובץ מוכן
3. **.htaccess מוגן** - הגנה על .env
4. **Rate limiting** - הוספה לauth.php
5. **Security logging** - מערכת מוכנה

---

## 📊 ציון כללי: 8.5/10

**חוזקות:**
- ✅ קוד מאובטח מאוד מבחינת SQL/XSS/CSRF
- ✅ אימות וסשנים חזקים
- ✅ הגנה מצוינת על העלאות קבצים

**חולשות:**
- ❌ חשיפת שגיאות בפרודקשן
- ❌ .env לא מוגן מספיק
- ⚠️ חסר rate limiting

**המלצה:** המערכת **בטוחה מאוד** בסיסית, אבל **חובה לתקן את 2 הבעיות הקריטיות** לפני העלאה!

---

רוצה שאתקן את הבעיות הקריטיות עכשיו? 🛠️
