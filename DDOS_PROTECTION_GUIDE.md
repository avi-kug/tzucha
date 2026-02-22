# מדריך הגנה מפני DDoS - Tzucha Application
## DDoS Protection Implementation Guide

תאריך: 22 בפברואר 2026
מטרה: הגנה מקסימלית מפני התקפות DDoS על האפליקציה

---

## 🛡️ מה הוגן?

### 1. Rate Limiting ברמת Application (PHP)
✅ **הושלם** - הוספנו הגנות בשכבת ה-PHP:

#### פונקציות שנוספו ל-`config/auth.php`:
- `check_api_rate_limit()` - הגבלת 60 בקשות לדקה לכל IP
- `check_request_size()` - הגבלת גודל בקשה ל-10MB
- `check_ip_blacklist()` - חסימת IPs חשודים

#### API Endpoints מוגנים:
- ✅ `/pages/people_api.php` - 60 req/min
- ✅ `/pages/supports_api.php` - 60 req/min
- ✅ `/pages/holiday_supports_api.php` - 60 req/min
- ✅ `/pages/cash_api.php` - 30 req/min (External API)
- ✅ `/pages/children_api.php` - 60 req/min
- ✅ `/pages/beit_neeman_api.php` - 60 req/min
- ✅ `/pages/standing_orders_api.php` - 60 req/min
- ✅ `/pages/honor_clothing_combined_api.php` - 30 req/min
- ✅ `/pages/person_details_api.php` - 60 req/min
- ✅ `/pages/security_logs_api.php` - 100 req/min (Admin)

### 2. הגנות ברמת Apache (.htaccess)
✅ **הושלם** - הוספנו ל-`.htaccess`:

```apache
# Rate Limiting (mod_ratelimit)
- הגבלת 400KB/s לכל connection

# Connection Limits (mod_qos)
- 20 connections מקסימום לכל IP
- 60 requests ל-10 שניות

# DDoS Evasive (mod_evasive)
- חסימה אוטומטית של flooding

# Request Size Limit
- מקסימום 10MB לכל request

# Timeout Protection
- מניעת slow HTTP attacks

# SQL Injection Blocking
- חסימת query strings מסוכנים

# Suspicious User Agents
- חסימת bots ו-scanners
```

### 3. טבלת IP Blacklist
✅ **נוצר** - `sql/create_ip_blacklist.sql`
- ניהול ידני של IPs חסומים
- חסימה זמנית או קבועה
- מעקב אחר סיבת החסימה

---

## 📋 הוראות התקנה

### שלב 1: עדכון Database
```bash
# הפעל דרך phpMyAdmin או command line:
mysql -u root -p tzucha < sql/create_ip_blacklist.sql
```

### שלב 2: אפשר Apache Modules (Production)
```bash
# על שרת Ubuntu/Debian:
sudo a2enmod headers
sudo a2enmod ratelimit
sudo a2enmod qos
sudo a2enmod evasive
sudo a2enmod rewrite
sudo a2enmod reqtimeout
sudo systemctl restart apache2
```

**הערה**: XAMPP לא תומך בכל ה-modules האלו. בproduction חובה להשתמש ב-Apache רגיל.

### שלב 3: הגדרת mod_evasive (אופציונלי)
צור `/etc/apache2/mods-available/evasive.conf`:
```apache
<IfModule mod_evasive20.c>
    DOSHashTableSize 3097
    DOSPageCount 5
    DOSSiteCount 100
    DOSPageInterval 1
    DOSSiteInterval 1
    DOSBlockingPeriod 60
    DOSEmailNotify admin@example.com
    DOSLogDir "/var/log/mod_evasive"
</IfModule>
```

### שלב 4: הגדרת mod_qos (אופציונלי)
```bash
sudo apt-get install libapache2-mod-qos
sudo a2enmod qos
```

צור `/etc/apache2/mods-available/qos.conf`:
```apache
<IfModule mod_qos.c>
    # Max connections per IP
    QS_SrvMaxConnPerIP 20
    
    # Max 60 requests per 10 seconds per IP
    QS_SrvRequestRate 60
    
    # Min data rate (bytes/sec)
    QS_SrvMinDataRate 150
</IfModule>
```

---

## 🔥 שימוש במערכת

### בדיקת Rate Limiting
נסה לשלוח יותר מ-60 בקשות בדקה:
```bash
# בעזרת curl
for i in {1..100}; do
    curl -X GET "http://localhost/tzucha/pages/people_api.php?action=get_all" \
         -H "Cookie: PHPSESSID=your_session_id"
    sleep 0.1
done
```

**תוצאה צפויה**: אחרי 60 בקשות, תקבל:
```json
{
  "success": false,
  "error": "יותר מדי בקשות. נסה שוב בעוד XX שניות."
}
```

### חסימת IP ידנית
```sql
-- חסימה קבועה
INSERT INTO ip_blacklist (ip_address, reason, blocked_until) 
VALUES ('192.168.1.100', 'Repeated attacks', NULL);

-- חסימה זמנית (עד תאריך)
INSERT INTO ip_blacklist (ip_address, reason, blocked_until) 
VALUES ('10.0.0.50', 'Suspicious activity', '2026-03-01 00:00:00');

-- ביטול חסימה
DELETE FROM ip_blacklist WHERE ip_address = '192.168.1.100';
```

### ניטור במערכת Security Logs
כל חסימה נרשמת ב-`security_logs`:
- `API_RATE_LIMIT` - חריגה מהגבלת rate
- `REQUEST_SIZE_EXCEEDED` - בקשה גדולה מדי
- `blocked_ip_attempt` - ניסיון גישה מIP חסום

גישה ל-logs: `/pages/security_logs.php` (Admin בלבד)

---

## 🌐 הגנה נוספת - Cloudflare (מומלץ ביותר!)

### למה Cloudflare?
- ✅ הגנת DDoS אוטומטית
- ✅ CDN גלובלי - מפזר עומס
- ✅ WAF (Web Application Firewall)
- ✅ Rate Limiting מתקדם
- ✅ **חינמי** לשימוש בסיסי

### הוראות הגדרה:
1. הירשם ל-Cloudflare: https://cloudflare.com
2. הוסף את הדומיין שלך
3. שנה DNS Nameservers (הם יתנו לך הוראות)
4. הפעל:
   - **"Under Attack Mode"** - במקרה של התקפה
   - **"Bot Fight Mode"** - חסימת bots
   - **Rate Limiting Rules** - הגבלות נוספות

### Rate Limiting ב-Cloudflare:
```
Rule: API Protection
- If: URI Path contains "/pages/*_api.php"
- Then: Rate limit 100 requests per minute per IP
- Action: Block for 1 hour
```

---

## 📊 בדיקת ביצועים

### לפני ההגנות:
- ✅ Login: מוגן (5 ניסיונות ב-15 דקות)
- ❌ API Endpoints: לא מוגן
- ❌ תשתית: לא מוגנת

### אחרי ההגנות:
- ✅ Login: מוגן
- ✅ API Endpoints: מוגן (60 req/min)
- ✅ תשתית: מוגנת (.htaccess)
- ⚠️ שרת: XAMPP לא אידיאלי (עבור ל-Production)

---

## 🚨 מה עדיין חסר? (לשרת Production)

### 1. Load Balancer
```
                    ┌─────────────┐
        Internet ──→│Load Balancer├──┐
                    └─────────────┘  │
                                     ├──→ Server 1
                                     ├──→ Server 2
                                     └──→ Server 3
```
מפזר עומס בין מספר שרתים.

### 2. Database Connection Pooling
```php
// במקום PDO חדש בכל request:
$pdo = new PDO(...);  // ❌

// השתמש ב-persistent connections:
$pdo = new PDO(..., [PDO::ATTR_PERSISTENT => true]);  // ✅
```

### 3. Fail2Ban (Linux)
חסימה אוטומטית של IPs לפי logs:
```bash
sudo apt-get install fail2ban
```

הגדרה ב-`/etc/fail2ban/jail.local`:
```ini
[tzucha-ddos]
enabled = true
port = http,https
filter = tzucha-ddos
logpath = /var/www/tzucha/logs/security.log
maxretry = 10
findtime = 60
bantime = 3600
```

### 4. nginx במקום Apache (אלטרנטיבה)
nginx מהיר יותר וקל יותר תחת עומס:
```nginx
# Rate limiting ב-nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

location ~* _api\.php$ {
    limit_req zone=api burst=10;
}
```

---

## 🎯 סיכום ודירוג אבטחה

| רכיב | לפני | אחרי | סטטוס |
|------|------|------|-------|
| Login Rate Limit | ✅ | ✅ | מוגן |
| API Rate Limit | ❌ | ✅ | **מוגן** |
| Request Size Limit | ❌ | ✅ | **מוגן** |
| IP Blacklist | ❌ | ✅ | **מוגן** |
| Apache Modules | ❌ | ⚠️ | **חלקי** (XAMPP) |
| Cloudflare | ❌ | 📝 | **מומלץ** |
| Load Balancer | ❌ | ❌ | לעתיד |

### דירוג סיכון:
- **לפני**: 🔴 גבוה (7/10)
- **אחרי**: 🟡 בינוני (4/10)
- **עם Cloudflare**: 🟢 נמוך (2/10)
- **עם Production Server**: 🟢 נמוך מאוד (1/10)

---

## 📞 תמיכה וטיפול בבעיות

### בעיה: "יותר מדי בקשות"
```
פתרון: המתן XX שניות או נקה session:
unset($_SESSION['api_rate_limit_' . md5($ip)]);
```

### בעיה: Apache modules לא עובדים
```
סיבה: XAMPP לא תומך בכל ה-modules.
פתרון: עבור ל-Apache production או השתמש ב-Cloudflare.
```

### בעיה: מהירות איטית
```
בדיקה:
1. כמה connections פעילים? (SHOW PROCESSLIST)
2. האם יש slow queries? (slow_query_log)
3. האם השרת מעוגל CPU/RAM?

פתרון: Database indexing, caching, CDN.
```

---

## ✅ Checklist לפני Production

- [ ] הרצת `sql/create_ip_blacklist.sql`
- [ ] אפשור Apache modules (ratelimit, qos, evasive)
- [ ] בדיקת rate limiting (60 requests test)
- [ ] הגדרת Cloudflare
- [ ] העברה משרת XAMPP ל-Apache/nginx רגיל
- [ ] הגדרת SSL (HTTPS)
- [ ] הפעלת auto-backup למסד הנתונים
- [ ] ניטור logs: `/logs/security.log`
- [ ] בדיקת response time תחת עומס

---

**שאלות? בעיות?**
בדוק את `/logs/security.log` או פנה למפתח.

**Made with 🛡️ by GitHub Copilot**
