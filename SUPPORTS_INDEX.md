# 📚 מדריך התמיכות - אינדקס מסמכים

## התחלה מהירה ⚡

**אם אתה רוצה להתחיל מהר:**
👉 [QUICKSTART_SUPPORTS.md](QUICKSTART_SUPPORTS.md) - 3 שלבים פשוטים

---

## מסמכי התקנה 🔧

| מסמך | תיאור | מתי להשתמש |
|------|--------|------------|
| [QUICKSTART_SUPPORTS.md](QUICKSTART_SUPPORTS.md) | התקנה מהירה ב-3 שלבים | **התחל כאן!** |
| [INSTALL_SUPPORTS.md](INSTALL_SUPPORTS.md) | מדריך התקנה מפורט | אם יש בעיות |
| [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md) | רשימת בדיקות מלאה | אחרי התקנה |

---

## מסמכי שימוש 📖

| מסמך | תיאור | מתי להשתמש |
|------|--------|------------|
| [SUPPORTS_README.md](SUPPORTS_README.md) | מדריך שימוש מלא | למשתמשים חדשים |
| [EXCEL_IMPORT_GUIDE.md](EXCEL_IMPORT_GUIDE.md) | איך להכין קובץ Excel | לפני ייבוא |
| [SUPPORTS_SUMMARY.md](SUPPORTS_SUMMARY.md) | סקירת המערכת | הבנה כללית |

---

## קבצי מערכת 💻

### Backend
| קובץ | תיאור |
|------|--------|
| `sql/create_supports_table.sql` | טבלת SQL |
| `repositories/SupportsRepository.php` | ניהול נתונים |
| `pages/supports_api.php` | API endpoints |
| `pages/supports.php` | ממשק משתמש |

### Frontend
| קובץ | תיאור |
|------|--------|
| `assets/css/supports.css` | עיצוב |
| `assets/js/supports.js` | לוגיקה |

---

## תהליכי עבודה 🔄

### תהליך 1: התקנה ראשונית
```
1. QUICKSTART_SUPPORTS.md
   ↓
2. בדיקה: SUPPORTS_CHECKLIST.md (סעיף "התקנה")
   ↓
3. אם יש בעיות: INSTALL_SUPPORTS.md
```

### תהליך 2: הוספת נתונים ידנית
```
1. SUPPORTS_README.md → "הוספת תמיכה"
   ↓
2. גש ל-supports.php
   ↓
3. לחץ "הוסף תמיכה"
```

### תהליך 3: ייבוא מ-Excel
```
1. EXCEL_IMPORT_GUIDE.md → הכן קובץ
   ↓
2. גש ל-supports.php
   ↓
3. לחץ "ייבוא מאקסל"
   ↓
4. אם צריך שיוך: SUPPORTS_README.md → "שיוך ידני"
```

### תהליך 4: ייצוא דו"ח
```
1. גש ל-supports.php → טאב "תמיכה"
   ↓
2. בחר אם לכלול הוצאות חריגות
   ↓
3. לחץ "ייצוא לאקסל"
```

---

## שאלות נפוצות (FAQ) 🤔

### איפה מתחילים?
👉 [QUICKSTART_SUPPORTS.md](QUICKSTART_SUPPORTS.md)

### איך משתמשים במערכת?
👉 [SUPPORTS_README.md](SUPPORTS_README.md)

### איך מכינים קובץ Excel?
👉 [EXCEL_IMPORT_GUIDE.md](EXCEL_IMPORT_GUIDE.md)

### יש בעיית התקנה?
👉 [INSTALL_SUPPORTS.md](INSTALL_SUPPORTS.md)

### רוצה לבדוק שהכל עובד?
👉 [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md)

### רוצה סקירה על הפיצ'רים?
👉 [SUPPORTS_SUMMARY.md](SUPPORTS_SUMMARY.md)

---

## לפי תפקיד 👥

### אני מנהל מערכת / מתקין
1. ✅ [QUICKSTART_SUPPORTS.md](QUICKSTART_SUPPORTS.md) - התקנה
2. ✅ [INSTALL_SUPPORTS.md](INSTALL_SUPPORTS.md) - פירוט
3. ✅ [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md) - בדיקות

### אני משתמש קצה
1. ✅ [SUPPORTS_README.md](SUPPORTS_README.md) - הדרכה
2. ✅ [EXCEL_IMPORT_GUIDE.md](EXCEL_IMPORT_GUIDE.md) - ייבוא

### אני מפתח
1. ✅ [SUPPORTS_SUMMARY.md](SUPPORTS_SUMMARY.md) - ארכיטקטורה
2. ✅ קבצי הקוד (repositories/, pages/, assets/)

---

## מבנה הקבצים 📁

```
tzucha/
├── sql/
│   └── create_supports_table.sql          ← טבלת SQL
├── repositories/
│   └── SupportsRepository.php             ← Business Logic
├── pages/
│   ├── supports.php                       ← UI
│   └── supports_api.php                   ← API
├── assets/
│   ├── css/
│   │   └── supports.css                   ← עיצוב
│   └── js/
│       └── supports.js                    ← לוגיקה
├── uploads/
│   └── temp/                              ← קבצים זמניים
├── QUICKSTART_SUPPORTS.md                 ← ⚡ התחל כאן
├── INSTALL_SUPPORTS.md                    ← 🔧 התקנה מפורטת
├── SUPPORTS_README.md                     ← 📖 מדריך שימוש
├── SUPPORTS_SUMMARY.md                    ← 📊 סקירה
├── SUPPORTS_CHECKLIST.md                  ← ✅ בדיקות
├── EXCEL_IMPORT_GUIDE.md                  ← 📥 מדריך Excel
└── SUPPORTS_INDEX.md                      ← 📚 קובץ זה
```

---

## סדר קריאה מומלץ 📚

### למתקין (ראש צוות IT)
```
1️⃣ SUPPORTS_SUMMARY.md       (5 דקות)  - הבנת המערכת
2️⃣ QUICKSTART_SUPPORTS.md    (2 דקות)  - התקנה
3️⃣ SUPPORTS_CHECKLIST.md     (15 דקות) - בדיקות
4️⃣ SUPPORTS_README.md         (10 דקות) - הדרכה למשתמשים
```

### למשתמש (מנהל תמיכות)
```
1️⃣ SUPPORTS_README.md         (10 דקות) - הבנת המערכת
2️⃣ EXCEL_IMPORT_GUIDE.md      (10 דקות) - הכנת קובץ
3️⃣ התנסות במערכת             (30 דקות) - עבודה מעשית
```

---

## כרטיסיות מהירות 🎯

### התקנה נכשלה?
```
1. בדוק: INSTALL_SUPPORTS.md → "בעיות נפוצות"
2. הרץ: SUPPORTS_CHECKLIST.md → סעיף "התקנה"
3. עדיין לא? פנה למפתח
```

### לא מבין איך להשתמש?
```
1. קרא: SUPPORTS_README.md → "שימוש במערכת"
2. נסה: להוסיף רשומה אחת ידנית
3. צפה: בחישובים בטאב "תמיכה"
```

### קובץ Excel לא עולה?
```
1. בדוק: EXCEL_IMPORT_GUIDE.md → "שגיאות נפוצות"
2. ודא: שיש את כל העמודות בסדר
3. נסה: להעלות רשומה אחת קודם
```

---

## קישורים מהירים 🔗

| אני רוצה... | לך ל... |
|-------------|---------|
| להתקין | [QUICKSTART_SUPPORTS.md](QUICKSTART_SUPPORTS.md) |
| להבין | [SUPPORTS_SUMMARY.md](SUPPORTS_SUMMARY.md) |
| להשתמש | [SUPPORTS_README.md](SUPPORTS_README.md) |
| לייבא | [EXCEL_IMPORT_GUIDE.md](EXCEL_IMPORT_GUIDE.md) |
| לבדוק | [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md) |
| לתקן | [INSTALL_SUPPORTS.md](INSTALL_SUPPORTS.md) → "בעיות נפוצות" |

---

## סטטוס המסמכים ✅

- ✅ **QUICKSTART_SUPPORTS.md** - התקנה מהירה
- ✅ **INSTALL_SUPPORTS.md** - התקנה מפורטת
- ✅ **SUPPORTS_README.md** - מדריך שימוש מלא
- ✅ **SUPPORTS_SUMMARY.md** - סקירת המערכת
- ✅ **SUPPORTS_CHECKLIST.md** - בדיקות איכות
- ✅ **EXCEL_IMPORT_GUIDE.md** - מדריך Excel
- ✅ **SUPPORTS_INDEX.md** - אינדקס (זה)

**כל המסמכים עדכניים ומוכנים לשימוש! 🎉**

---

## גרסאות 📌

| גרסה | תאריך | שינויים |
|------|-------|---------|
| 1.0 | פברואר 2026 | שחרור ראשוני |

---

## תמיכה 🆘

**יש בעיה?**
1. בדוק את המדריכים הרלוונטיים למעלה
2. הרץ את [SUPPORTS_CHECKLIST.md](SUPPORTS_CHECKLIST.md)
3. צור קשר עם מפתח המערכת

**הכל עובד?**
🎉 מעולה! תתחיל להשתמש במערכת ותהנה!

---

**עדכון אחרון:** פברואר 2026  
**גרסה:** 1.0  
**סטטוס:** ✅ הכל מוכן ועובד!
