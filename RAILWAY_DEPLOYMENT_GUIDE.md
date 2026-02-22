# 🚂 מדריך העלאה ל-Railway עם הגנת DDoS

**תאריך**: 22 בפברואר 2026  
**פלטפורמה**: Railway.app  
**אפליקציה**: Tzucha PHP

---

## ✅ מה יש לך ב-Railway?

### הגנות שיעבדו ב-Railway:

#### 1️⃣ **הגנות PHP** (100% פעיל!)
- ✅ Rate Limiting - 60 בקשות לדקה לכל IP
- ✅ Request Size Limits - מקסימום 10MB
- ✅ IP Blacklist - חסימת IPs חשודים
- ✅ CSRF Protection
- ✅ Session Security
- ✅ Security Logging

#### 2️⃣ **הגנות nginx** (מוגדר בקובץ)
- ✅ Rate Limiting ברמת שרת
  - 60 req/min ל-API endpoints
  - 5 req/min ל-login
  - 100 req/min כללי
- ✅ Connection Limit - 20 connections לכל IP
- ✅ Request Size - מקסימום 10MB
- ✅ Timeout Protection
- ✅ חסימת SQL injection patterns
- ✅ חסימת bots חשודים
- ✅ Security Headers

#### 3️⃣ **Railway Built-in Protection**
Railway מספק אוטומטית:
- ✅ SSL/TLS (HTTPS חינמי)
- ✅ DDoS Protection בסיסית
- ✅ Load Balancing
- ✅ Auto-scaling

---

## 🚀 הוראות העלאה ל-Railway

### דרישות מוקדמות:
- ✅ חשבון Railway (חינמי: railway.app)
- ✅ Git repository (GitHub/GitLab)
- ✅ MySQL database (Railway מספק חינמי)

---

### שלב 1: הכן את הקבצים

**קבצים שנוצרו בשבילך:**
- ✅ `nginx.conf` - הגדרות nginx עם הגנות DDoS
- ✅ `nixpacks.toml` - הגדרות build ל-Railway
- ✅ `php-fpm.conf` - הגדרות PHP-FPM

**בדוק שיש לך:**
```
tzucha/
├── nginx.conf         ✅ נוצר
├── nixpacks.toml      ✅ נוצר
├── php-fpm.conf       ✅ נוצר
├── .env.example       ✅ קיים
├── composer.json      ✅ קיים
├── pages/             ✅ קיים
├── config/            ✅ קיים
└── sql/               ✅ קיים
```

---

### שלב 2: הכן את Git Repository

```bash
# אם עדיין אין לך Git
cd c:\xampp\htdocs\tzucha
git init
git add .
git commit -m "Initial commit with DDoS protection"

# צור repository ב-GitHub
# אז:
git remote add origin https://github.com/YOUR_USERNAME/tzucha.git
git branch -M main
git push -u origin main
```

---

### שלב 3: צור פרויקט ב-Railway

1. **כנס ל-Railway**: https://railway.app
2. **לחץ "New Project"**
3. **בחר "Deploy from GitHub repo"**
4. **בחר את ה-repository** שלך (tzucha)
5. Railway יזהה אוטומטית PHP ויתחיל build

---

### שלב 4: הוסף MySQL Database

1. **בפרויקט Railway**, לחץ **"+ New"**
2. **בחר "Database" → "MySQL"**
3. Railway ייצור database ויתן לך:
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_DATABASE`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`
   - `MYSQL_URL` (connection string)

---

### שלב 5: הגדר Environment Variables

ב-Railway, לחץ על השירות שלך → **"Variables"**:

```env
# Database (Railway מספק אוטומטית)
MYSQL_HOST=containers-us-west-xxx.railway.app
MYSQL_PORT=6543
MYSQL_DATABASE=railway
MYSQL_USER=root
MYSQL_PASSWORD=xxxxxxxxxxxxx

# או השתמש ב-URL אחד:
DATABASE_URL=mysql://root:xxxxx@containers-us-west-xxx.railway.app:6543/railway

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tzucha-production.up.railway.app

# PHP Settings
PHP_MEMORY_LIMIT=256M
PHP_UPLOAD_MAX_FILESIZE=10M
PHP_POST_MAX_SIZE=10M
```

---

### שלב 6: העלה את ה-Database Schema

**דרך 1: Railway CLI** (מומלץ)
```bash
# התקן Railway CLI
npm install -g @railway/cli

# התחבר
railway login

# התחבר ל-database
railway connect mysql

# העלה SQL
mysql> source c:/xampp/htdocs/tzucha/sql/create_ip_blacklist.sql
mysql> source c:/xampp/htdocs/tzucha/deployment/tzucha_2026-02-16.sql
```

**דרך 2: דרך phpMyAdmin**
Railway לא מספק phpMyAdmin, אז השתמש ב-MySQL client:
```bash
mysql -h containers-us-west-xxx.railway.app -P 6543 -u root -p railway < sql/create_ip_blacklist.sql
```

---

### שלב 7: עדכן config/db.php

Railway מגדיר משתני סביבה אוטומטית. עדכן את `config/db.php`:

```php
<?php
// config/db.php - Railway Compatible

// Railway provides DATABASE_URL automatically
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parse Railway's DATABASE_URL
    // Format: mysql://user:pass@host:port/database
    $url = parse_url($databaseUrl);
    
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 3306;
    $database = ltrim($url['path'] ?? '/railway', '/');
    $username = $url['user'] ?? 'root';
    $password = $url['pass'] ?? '';
} else {
    // Fallback to individual env vars
    $host = getenv('MYSQL_HOST') ?: 'localhost';
    $port = getenv('MYSQL_PORT') ?: 3306;
    $database = getenv('MYSQL_DATABASE') ?: 'tzucha';
    $username = getenv('MYSQL_USER') ?: 'root';
    $password = getenv('MYSQL_PASSWORD') ?: '';
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true, // Connection pooling
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error");
}
```

---

### שלב 8: Deploy!

Railway יעשה deploy אוטומטית כשתעשה push:

```bash
git add .
git commit -m "Configure for Railway deployment"
git push origin main
```

**עקוב אחרי ה-build**: Railway → Project → Build Logs

---

## 🎯 בדיקות אחרי Deploy

### 1. בדוק Health Check
```bash
curl https://tzucha-production.up.railway.app/health
# Expected: OK
```

### 2. בדוק Rate Limiting
```bash
# נסה 70 בקשות בדקה
for i in {1..70}; do
  curl https://tzucha-production.up.railway.app/pages/people_api.php
  sleep 0.8
done

# אחרי 60 בקשות אמור להיחסם
```

### 3. בדוק Security Headers
```bash
curl -I https://tzucha-production.up.railway.app
# צריך לראות:
# X-Frame-Options: DENY
# X-Content-Type-Options: nosniff
# etc.
```

### 4. בדוק Security Logs
כנס ל-`/pages/security_logs.php` ובדוק שזה עובד.

---

## 📊 השוואה: XAMPP vs Railway

| תכונה | XAMPP (Local) | Railway (Production) |
|-------|---------------|---------------------|
| **Rate Limiting PHP** | ✅ | ✅ |
| **Rate Limiting Server** | ❌ (.htaccess) | ✅ (nginx) |
| **SSL/HTTPS** | ⚠️ ידני | ✅ אוטומטי |
| **DDoS Protection** | ⚠️ חלקי | ✅ מובנה |
| **Auto-scaling** | ❌ | ✅ |
| **Load Balancer** | ❌ | ✅ |
| **Monitoring** | ❌ | ✅ |
| **Backups** | ידני | אוטומטי |
| **עלות** | חינמי | $5-20/חודש |

---

## 💰 תמחור Railway

### Free Plan:
- ✅ $5 credit חינמי בחודש
- ✅ מספיק לפרויקט קטן
- ✅ SSL חינמי
- ⚠️ Sleep after 30 minutes inactive

### Hobby Plan ($5/month):
- ✅ $5 credit + שימוש לפי צריכה
- ✅ No sleep
- ✅ Custom domains

### Pro Plan ($20/month):
- ✅ $20 credit + שימוש לפי צריכה
- ✅ Priority support
- ✅ Advanced monitoring

**הערכה לפרויקט שלך**: ~$5-10/חודש

---

## 🔐 הגנות נוספות ב-Railway

### 1. Custom Domain + Cloudflare
```
Domain → Cloudflare → Railway
```
שילוב Railway + Cloudflare = הגנה מקסימלית!

### 2. Railway Monitoring
Railway מספק:
- CPU/Memory usage
- Request logs
- Error tracking
- Metrics dashboard

### 3. Environment-based Config
```bash
# Development
railway run --environment development

# Production
railway run --environment production
```

---

## 🆘 בעיות נפוצות

### בעיה: "502 Bad Gateway"
```
סיבה: PHP-FPM לא מתחיל
פתרון: בדוק שיש php-fpm.conf בroot
```

### בעיה: "Dataxxxxxxxxxxction failed"
```
סיבה: משתני סביבה לא מוגדרים
פתרון: בדוק Railway Variables → DATABASE_URL
```

### בעיה: "Rate limit לא עובד"
```
סיבה: nginx.conf לא נטען
פתרון: בדוק Build Logs - האם nginx.conf הועתק?
```

### בעיה: "Static files (CSS/JS) לא עובדים"
```
סיבה: nginx לא מוצא את הקבצים
פתרון: בדוק שה-root ב-nginx.conf מצביע ל-/app
```

---

## 📝 Checklist לפני Deploy

- [ ] `nginx.conf` בroot
- [ ] `nixpacks.toml` בroot
- [ ] `php-fpm.conf` בroot
- [ ] `.env.example` עם כל המשתנים
- [ ] `composer.json` מעודכן
- [ ] SQL files מוכנים
- [ ] `config/db.php` תומך ב-DATABASE_URL
- [ ] Git repository מעודכן
- [ ] Railway project נוצר
- [ ] MySQL database הוסף
- [ ] Environment variables הוגדרו
- [ ] Database schema הועלה

---

## 🎉 סיכום

### מה יש לך ב-Railway:

✅ **Rate Limiting ברמת PHP**: 60 req/min  
✅ **Rate Limiting ברמת nginx**: 60 req/min API, 5 req/min login  
✅ **Connection Limits**: 20 concurrent/IP  
✅ **Request Size Limits**: 10MB max  
✅ **IP Blacklist**: חסימה ידנית  
✅ **Security Headers**: כל ההדרים  
✅ **SSL/HTTPS**: אוטומטי  
✅ **DDoS Protection**: Railway built-in  
✅ **Auto-scaling**: אוטומטי  

### דירוג אבטחה ב-Railway:

| פלטפורמה | דירוג |
|----------|-------|
| XAMPP | 🟡 בינוני (4/10) |
| Railway | 🟢 טוב (2/10) |
| Railway + Cloudflare | 🟢 מצוין (1/10) |

---

**🚂 מוכן ל-Deploy? בהצלחה!**

יש שאלות? בדוק את:
- Build Logs ב-Railway
- `/logs/security.log`
- `/pages/security_logs.php`
