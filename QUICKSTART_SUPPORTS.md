# התקנה מהירה - 3 שלבים ⚡

## שלב 1: צור את הטבלה (30 שניות)
```bash
mysql -u root -p tzucha < sql/create_supports_table.sql
```

## שלב 2: הוסף הרשאה (אם לא Admin)
```sql
INSERT INTO user_permissions (user_id, permission_key) VALUES (1, 'supports');
```

## שלב 3: גש לדף
```
http://localhost/tzucha/pages/supports.php
```

---

# זהו! המערכת מוכנה! 🎉

---

## רוצה לדעת יותר?

📖 **מדריך מלא:** [SUPPORTS_README.md](SUPPORTS_README.md)  
📋 **Checklist בדיקות:** [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md)  
📊 **סיכום פיצ'רים:** [SUPPORTS_SUMMARY.md](SUPPORTS_SUMMARY.md)  
🔧 **התקנה מפורטת:** [INSTALL_SUPPORTS.md](INSTALL_SUPPORTS.md)  

---

## תקלות?

### הטבלה לא נוצרה?
```
גש ל-phpMyAdmin → בחר tzucha → SQL → העתק את התוכן מ-sql/create_supports_table.sql
```

### אין גישה לדף?
```sql
-- וודא שיש לך הרשאה
SELECT * FROM user_permissions WHERE user_id = 1 AND permission_key = 'supports';
```

### שגיאת Excel?
```bash
# ודא שהתיקייה קיימת
mkdir uploads/temp
chmod 777 uploads/temp
```

---

**💡 טיפ:** התחל עם הוספת רשומה אחת ידנית כדי להבין את המערכת.

**🚀 בהצלחה!**
