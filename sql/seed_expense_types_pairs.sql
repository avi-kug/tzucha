-- Seed categories and expense types based on provided pairs
SET NAMES utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;
START TRANSACTION;

-- Ensure categories exist
INSERT INTO categories(name) VALUES ('אירועים') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('גרפיקה') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('דואר') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('הדפסות') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('הוצאות משרד') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('הוצאות עמותה') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('כללי') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('כתיבה') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('מתנות') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('נסיעות') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('קופות') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('תמיכות') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO categories(name) VALUES ('תקשורת') ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Helper pattern: insert expense type for category only if missing
-- INSERT INTO expense_types(category_id, name)
-- SELECT c.id, 'TYPE' FROM categories c WHERE c.name='CATEGORY'
-- AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='TYPE');

-- אירועים
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='אירועים'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס נציגים');

-- גרפיקה
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אלפון' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דוח לאחים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='חנוכה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס מצהלות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס נציגים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מתנות' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מתנות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון על הפרק');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פרסום - מודעות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פורים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פסח');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין תשרי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שבועות' FROM categories c WHERE c.name='גרפיקה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שבועות');

-- דואר
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אלפון' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דוח לאחים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='חנוכה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס מצהלות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס נציגים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מתנות' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מתנות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון על הפרק');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פרסום - מודעות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פורים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פסח');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין תשרי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שבועות' FROM categories c WHERE c.name='דואר'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שבועות');

-- הדפסות
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אלפון' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דוח לאחים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='חנוכה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס מצהלות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס נציגים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מתנות' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מתנות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון על הפרק');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פרסום - מודעות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פורים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פסח');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין תשרי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שבועות' FROM categories c WHERE c.name='הדפסות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שבועות');

-- הוצאות משרד
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אוכל ושתיה' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אוכל ושתיה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דיו וטונרים' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דיו וטונרים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דפים' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דפים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מדפסות' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מדפסות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'ניקיון' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='ניקיון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'ציוד מחשב' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='ציוד מחשב');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'ציוד משרדי' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='ציוד משרדי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שכירות משרד' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שכירות משרד');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'תיקונים' FROM categories c WHERE c.name='הוצאות משרד'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='תיקונים');

-- הוצאות עמותה
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='הוצאות עמותה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מרכז הצדקה קבוע' FROM categories c WHERE c.name='הוצאות עמותה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מרכז הצדקה קבוע');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עמלות סליקה' FROM categories c WHERE c.name='הוצאות עמותה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עמלות סליקה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'רוח' FROM categories c WHERE c.name='הוצאות עמותה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='רוח');

-- כללי
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='כללי'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');

-- כתיבה
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אלפון' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='דוח לאחים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='חנוכה');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס מצהלות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כינוס נציגים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מתנות' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מתנות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='עלון על הפרק');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פרסום - מודעות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פורים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין פסח');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='קמפיין תשרי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שבועות' FROM categories c WHERE c.name='כתיבה'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שבועות');

-- מתנות
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='מתנות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');

-- נסיעות
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='נסיעות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');

-- קופות
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'ייצור קופות' FROM categories c WHERE c.name='קופות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='ייצור קופות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='קופות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');

-- תמיכות
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='תמיכות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פורים' FROM categories c WHERE c.name='תמיכות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פורים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'פסח' FROM categories c WHERE c.name='תמיכות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='פסח');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'תשרי' FROM categories c WHERE c.name='תמיכות'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='תשרי');

-- תקשורת
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אינטרנט' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אינטרנט');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'אינטרנט וטלפון' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='אינטרנט וטלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'טלפון' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='טלפון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'כללי' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='כללי');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מחשוב' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מחשוב');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מיילים' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מיילים');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'מערכות טלפוניות' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='מערכות טלפוניות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'סינון' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='סינון');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'שליחת הודעות' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='שליחת הודעות');
INSERT INTO expense_types(category_id, name)
SELECT c.id, 'תוכנה' FROM categories c WHERE c.name='תקשורת'
AND NOT EXISTS (SELECT 1 FROM expense_types et WHERE et.category_id=c.id AND et.name='תוכנה');

COMMIT;
