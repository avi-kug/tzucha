-- Seed categories and expense types (Hebrew)
-- Run inside database tzucha. Assumes tables: categories(name), expense_types(category_id,name) with a unique key to avoid duplicates.
-- Encoding: UTF-8

START TRANSACTION;

-- Categories
INSERT INTO categories (name) VALUES ('אירועים') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('גרפיקה') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('דואר') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('הדפסות') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('הוצאות משרד') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('הוצאות עמותה') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('כללי') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('כתיבה') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('מתנות') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('נסיעות') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('קופות') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('תמיכות') ON DUPLICATE KEY UPDATE name=name;
INSERT INTO categories (name) VALUES ('תקשורת') ON DUPLICATE KEY UPDATE name=name;

-- Helper: insert expense type by category name
-- Usage pattern below: SELECT id from categories by name

-- אירועים
INSERT INTO expense_types (category_id, name)
SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='אירועים'
ON DUPLICATE KEY UPDATE name=name;

-- גרפיקה
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אלפון' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מתנות' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שבועות' FROM categories c WHERE c.name='גרפיקה' ON DUPLICATE KEY UPDATE name=name;

-- דואר
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אלפון' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מתנות' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שבועות' FROM categories c WHERE c.name='דואר' ON DUPLICATE KEY UPDATE name=name;

-- הדפסות
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אלפון' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מתנות' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שבועות' FROM categories c WHERE c.name='הדפסות' ON DUPLICATE KEY UPDATE name=name;

-- הוצאות משרד
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אוכל ושתיה' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דיו וטונרים' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דפים' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מדפסות' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'ניקיון' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'ציוד מחשב' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'ציוד משרדי' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שכירות משרד' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'תיקונים' FROM categories c WHERE c.name='הוצאות משרד' ON DUPLICATE KEY UPDATE name=name;

-- הוצאות עמותה
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='הוצאות עמותה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מרכז הצדקה קבוע' FROM categories c WHERE c.name='הוצאות עמותה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עמלות סליקה' FROM categories c WHERE c.name='הוצאות עמותה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'רוח' FROM categories c WHERE c.name='הוצאות עמותה' ON DUPLICATE KEY UPDATE name=name;

-- כללי
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='כללי' ON DUPLICATE KEY UPDATE name=name;

-- כתיבה
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אלפון' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'דוח לאחים' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'חנוכה' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס מצהלות' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כינוס נציגים' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מתנות' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'עלון על הפרק' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פרסום - מודעות' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פורים' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין פסח' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'קמפיין תשרי' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שבועות' FROM categories c WHERE c.name='כתיבה' ON DUPLICATE KEY UPDATE name=name;

-- מתנות
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='מתנות' ON DUPLICATE KEY UPDATE name=name;

-- נסיעות
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='נסיעות' ON DUPLICATE KEY UPDATE name=name;

-- קופות
INSERT INTO expense_types (category_id, name) SELECT c.id, 'ייצור קופות' FROM categories c WHERE c.name='קופות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='קופות' ON DUPLICATE KEY UPDATE name=name;

-- תמיכות
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='תמיכות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פורים' FROM categories c WHERE c.name='תמיכות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'פסח' FROM categories c WHERE c.name='תמיכות' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'תשרי' FROM categories c WHERE c.name='תמיכות' ON DUPLICATE KEY UPDATE name=name;

-- תקשורת
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אינטרנט' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'אינטרנט וטלפון' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'טלפון' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'כללי' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מחשוב' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מיילים' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'מערכות טלפוניות' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'סינון' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'שליחת הודעות' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;
INSERT INTO expense_types (category_id, name) SELECT c.id, 'תוכנה' FROM categories c WHERE c.name='תקשורת' ON DUPLICATE KEY UPDATE name=name;

COMMIT;
