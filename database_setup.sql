-- Database setup for Tzucha
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS tzucha;
CREATE DATABASE tzucha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tzucha;

-- People table
CREATE TABLE people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    age INT,
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense types table (linked to categories)
CREATE TABLE expense_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_expense_type (category_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paid by options table
CREATE TABLE paid_by_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- From accounts table
CREATE TABLE from_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fixed expenses table
CREATE TABLE fixed_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    for_what VARCHAR(255),
    store VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    department VARCHAR(100),
    category VARCHAR(100),
    expense_type VARCHAR(100),
    paid_by VARCHAR(100),
    from_account VARCHAR(100),
    invoice_copy VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regular expenses table
CREATE TABLE regular_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    for_what VARCHAR(255),
    store VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    department VARCHAR(100),
    category VARCHAR(100),
    expense_type VARCHAR(100),
    paid_by VARCHAR(100),
    from_account VARCHAR(100),
    invoice_copy VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Summary expenses table
CREATE TABLE summary_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    for_what VARCHAR(255),
    store VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    department VARCHAR(100),
    category VARCHAR(100),
    expense_type VARCHAR(100),
    paid_by VARCHAR(100),
    from_account VARCHAR(100),
    invoice_copy VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial data
INSERT INTO departments (name) VALUES
('אחים לחסד'),
('איסוף קופות'),
('בגופו'),
('בית נאמן'),
('חו"ל'),
('יעזורו תעסוקה'),
('מצהלות'),
('משרד ראשי'),
('שיקום משפחות'),
('שמחם'),
('תמיכות'),
('יעזורו זכויות'),
('כח הרבים');
INSERT INTO categories (name) VALUES
('אירועים'),
('אגף'),
('גרפיקה'),
('דואר'),
('הדפסות'),
('הוצאות משרד'),
('הוצאות עמותה'),
('כללי'),
('כתיבה'),
('מתנות'),
('נסיעות'),
('קופות'),
('תמיכות'),
('תקשורת');

INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס נציגים' FROM categories WHERE name = 'אירועים';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אחים לחסד' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'איסוף קופות' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'בגופו' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'בית נאמן' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'חו"ל' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'יעזורו תעסוקה' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מצהלות' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'משרד ראשי' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שיקום משפחות' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שמחם' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'תמיכות' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'יעזורו זכויות' FROM categories WHERE name = 'אגף';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כח הרבים' FROM categories WHERE name = 'אגף';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אלפון' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דוח לאחים' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'חנוכה' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס מצהלות' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס נציגים' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מתנות' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון על הפרק' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פרסום - מודעות' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פורים' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פסח' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין תשרי' FROM categories WHERE name = 'גרפיקה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שבועות' FROM categories WHERE name = 'גרפיקה';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אלפון' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דוח לאחים' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'חנוכה' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס מצהלות' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס נציגים' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מתנות' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון על הפרק' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פרסום - מודעות' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פורים' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פסח' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין תשרי' FROM categories WHERE name = 'דואר';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שבועות' FROM categories WHERE name = 'דואר';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אלפון' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דוח לאחים' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'חנוכה' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס מצהלות' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס נציגים' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מתנות' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון על הפרק' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פרסום - מודעות' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פורים' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פסח' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין תשרי' FROM categories WHERE name = 'הדפסות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שבועות' FROM categories WHERE name = 'הדפסות';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אוכל ושתיה' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דיו וטונרים' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דפים' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מדפסות' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'ניקיון' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'ציוד מחשב' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'ציוד משרדי' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שכירות משרד' FROM categories WHERE name = 'הוצאות משרד';
INSERT INTO expense_types (category_id, name)
SELECT id, 'תיקונים' FROM categories WHERE name = 'הוצאות משרד';

INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'הוצאות עמותה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מרכז הצדקה קבוע' FROM categories WHERE name = 'הוצאות עמותה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עמלות סליקה' FROM categories WHERE name = 'הוצאות עמותה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'רוח' FROM categories WHERE name = 'הוצאות עמותה';

INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'כללי';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אלפון' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'דוח לאחים' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'חנוכה' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס מצהלות' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כינוס נציגים' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מתנות' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'עלון על הפרק' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פרסום - מודעות' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פורים' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין פסח' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'קמפיין תשרי' FROM categories WHERE name = 'כתיבה';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שבועות' FROM categories WHERE name = 'כתיבה';

INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'מתנות';

INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'נסיעות';

INSERT INTO expense_types (category_id, name)
SELECT id, 'ייצור קופות' FROM categories WHERE name = 'קופות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'קופות';

INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'תמיכות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פורים' FROM categories WHERE name = 'תמיכות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'פסח' FROM categories WHERE name = 'תמיכות';
INSERT INTO expense_types (category_id, name)
SELECT id, 'תשרי' FROM categories WHERE name = 'תמיכות';

INSERT INTO expense_types (category_id, name)
SELECT id, 'אינטרנט' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'אינטרנט וטלפון' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'טלפון' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'כללי' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מחשוב' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מיילים' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'מערכות טלפוניות' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'סינון' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'שליחת הודעות' FROM categories WHERE name = 'תקשורת';
INSERT INTO expense_types (category_id, name)
SELECT id, 'תוכנה' FROM categories WHERE name = 'תקשורת';

INSERT INTO paid_by_options (name) VALUES ('מזומן'), ('אשראי'), ('העברה בנקאית'), ('צ׳ק');
INSERT INTO from_accounts (name) VALUES ('חשבון ראשי'), ('חשבון משני'), ('קופה');
