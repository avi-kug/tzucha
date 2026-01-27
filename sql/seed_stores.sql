-- Seed Stores list (Hebrew + English names)
SET NAMES utf8mb4;
SET collation_connection = utf8mb4_unicode_ci;

-- Ensure stores table exists
CREATE TABLE IF NOT EXISTS stores (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;

-- Insert each store only if it does not already exist
INSERT INTO stores(name) SELECT 'אברימי ברים - פירות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אברימי ברים - פירות');
INSERT INTO stores(name) SELECT 'אולטרה קופי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אולטרה קופי');
INSERT INTO stores(name) SELECT 'אומנות השולחן' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אומנות השולחן');
INSERT INTO stores(name) SELECT 'אליעזר בן שם' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אליעזר בן שם');
INSERT INTO stores(name) SELECT 'ביתר  טויסט' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='ביתר  טויסט');
INSERT INTO stores(name) SELECT 'ברזלים' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='ברזלים');
INSERT INTO stores(name) SELECT 'גברעם - מעטפות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='גברעם - מעטפות');
INSERT INTO stores(name) SELECT 'גרפיכל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='גרפיכל');
INSERT INTO stores(name) SELECT 'גרפיקה  גב'' אלתר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='גרפיקה  גב'' אלתר');
INSERT INTO stores(name) SELECT 'דואר ישראל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='דואר ישראל');
INSERT INTO stores(name) SELECT 'חוצות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='חוצות');
INSERT INTO stores(name) SELECT 'דובי רוזנפלד' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='דובי רוזנפלד');
INSERT INTO stores(name) SELECT 'דיגטל פרינט' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='דיגטל פרינט');
INSERT INTO stores(name) SELECT 'הום פלייס' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='הום פלייס');
INSERT INTO stores(name) SELECT 'היימשע פוסט' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='היימשע פוסט');
INSERT INTO stores(name) SELECT 'הכל לבית' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='הכל לבית');
INSERT INTO stores(name) SELECT 'העברה' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='העברה');
INSERT INTO stores(name) SELECT 'וייסטק' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='וייסטק');
INSERT INTO stores(name) SELECT 'טכנוליין' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='טכנוליין');
INSERT INTO stores(name) SELECT 'טלשופ' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='טלשופ');
INSERT INTO stores(name) SELECT 'טמבור B' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='טמבור B');
INSERT INTO stores(name) SELECT 'בנצי בריכטא' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='בנצי בריכטא');
INSERT INTO stores(name) SELECT 'טמבורית אברימי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='טמבורית אברימי');
INSERT INTO stores(name) SELECT 'יהודה פולצק' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='יהודה פולצק');
INSERT INTO stores(name) SELECT 'יומלודת' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='יומלודת');
INSERT INTO stores(name) SELECT 'ימות המשיח' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='ימות המשיח');
INSERT INTO stores(name) SELECT 'יש חסד' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='יש חסד');
INSERT INTO stores(name) SELECT 'כלי וחומר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='כלי וחומר');
INSERT INTO stores(name) SELECT 'כתיבה - גב'' קיל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='כתיבה - גב'' קיל');
INSERT INTO stores(name) SELECT 'לייפציג' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='לייפציג');
INSERT INTO stores(name) SELECT 'ללא חנות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='ללא חנות');
INSERT INTO stores(name) SELECT 'מאיר קליין' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מאיר קליין');
INSERT INTO stores(name) SELECT 'מאפית נחמה' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מאפית נחמה');
INSERT INTO stores(name) SELECT 'מוטי בריכטא' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מוטי בריכטא');
INSERT INTO stores(name) SELECT 'מונית' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מונית');
INSERT INTO stores(name) SELECT 'מזומן' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מזומן');
INSERT INTO stores(name) SELECT 'מילגם' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מילגם');
INSERT INTO stores(name) SELECT 'מלאך אסתר גרפיקה' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מלאך אסתר גרפיקה');
INSERT INTO stores(name) SELECT 'מרדכי רינדר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מרדכי רינדר');
INSERT INTO stores(name) SELECT 'מרכז המחשב' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='מרכז המחשב');
INSERT INTO stores(name) SELECT 'נסיעות אברימי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='נסיעות אברימי');
INSERT INTO stores(name) SELECT 'נסיעות ברוך' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='נסיעות ברוך');
INSERT INTO stores(name) SELECT 'סיטי קאר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='סיטי קאר');
INSERT INTO stores(name) SELECT 'עיצובית' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='עיצובית');
INSERT INTO stores(name) SELECT 'פ.א. פתרונות טכנולוגיים' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פ.א. פתרונות טכנולוגיים');
INSERT INTO stores(name) SELECT 'פלא אריזות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פלא אריזות');
INSERT INTO stores(name) SELECT 'פעמית B' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פעמית B');
INSERT INTO stores(name) SELECT 'פריטנר דיל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פריטנר דיל');
INSERT INTO stores(name) SELECT 'צין קרינות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='צין קרינות');
INSERT INTO stores(name) SELECT 'קוליקול' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='קוליקול');
INSERT INTO stores(name) SELECT 'קופת ביתר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='קופת ביתר');
INSERT INTO stores(name) SELECT 'קרביץ' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='קרביץ');
INSERT INTO stores(name) SELECT 'קשרי חסד' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='קשרי חסד');
INSERT INTO stores(name) SELECT 'רוטשילד' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='רוטשילד');
INSERT INTO stores(name) SELECT 'רויאל סטרלינג' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='רויאל סטרלינג');
INSERT INTO stores(name) SELECT 'רמי לוי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='רמי לוי');
INSERT INTO stores(name) SELECT 'שופרסל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שופרסל');
INSERT INTO stores(name) SELECT 'שוקה שקל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שוקה שקל');
INSERT INTO stores(name) SELECT 'שימעלה' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שימעלה');
INSERT INTO stores(name) SELECT 'שפע ברכת השם' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שפע ברכת השם');
INSERT INTO stores(name) SELECT 'שרפאן - דפים ומדבקות' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שרפאן - דפים ומדבקות');
INSERT INTO stores(name) SELECT 'B&M' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='B&M');
INSERT INTO stores(name) SELECT 'דוד כי טוב' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='דוד כי טוב');
INSERT INTO stores(name) SELECT 'אייזנטל - רו\'ח' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אייזנטל - רו\'ח');
INSERT INTO stores(name) SELECT 'גב'' הופשטיין' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='גב'' הופשטיין');
INSERT INTO stores(name) SELECT 'נטויל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='נטויל');
INSERT INTO stores(name) SELECT 'אר אל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='אר אל');
INSERT INTO stores(name) SELECT 'בזק' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='בזק');
-- 'ימות המשיח' appears earlier; duplicate skipped by NOT EXISTS
-- 'אר אל' appears earlier; duplicate skipped by NOT EXISTS
INSERT INTO stores(name) SELECT 'נדרים פלוס' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='נדרים פלוס');
INSERT INTO stores(name) SELECT 'שלח מסר' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='שלח מסר');
INSERT INTO stores(name) SELECT 'נטפרי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='נטפרי');
INSERT INTO stores(name) SELECT 'פאלאפון' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פאלאפון');
INSERT INTO stores(name) SELECT 'יהודה בן שם' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='יהודה בן שם');
INSERT INTO stores(name) SELECT 'פרחים באוהל' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='פרחים באוהל');
INSERT INTO stores(name) SELECT 'כי טוב נחמן' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='כי טוב נחמן');
INSERT INTO stores(name) SELECT 'רבינוביץ צבי' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='רבינוביץ צבי');
INSERT INTO stores(name) SELECT 'כריכיית ארזים' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='כריכיית ארזים');
INSERT INTO stores(name) SELECT 'תלתן' WHERE NOT EXISTS (SELECT 1 FROM stores WHERE name='תלתן');

COMMIT;
