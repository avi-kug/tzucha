-- Update people table with corrected field names
USE tzucha;

-- Drop the existing people table
DROP TABLE IF EXISTS people;

-- Create the new people table with all fields
CREATE TABLE people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- אמרכל
    amarchal VARCHAR(100),
    
    -- גזבר
    gizbar VARCHAR(100),
    
    -- מזהה תוכנה
    software_id VARCHAR(100),
    
    -- מס תורם
    donor_number VARCHAR(100),
    
    -- חתן הר"ר
    chatan_harar VARCHAR(100),
    
    -- משפחה
    family_name VARCHAR(100) NOT NULL,
    
    -- שם
    first_name VARCHAR(100) NOT NULL,
    
    -- שם לדואר
    name_for_mail VARCHAR(150),
    
    -- שם ומשפחה ביחד
    full_name VARCHAR(200),
    
    -- תעודת זהות בעל
    husband_id VARCHAR(20),
    
    -- תעודת זהות אשה
    wife_id VARCHAR(20),
    
    -- כתובת
    address TEXT,
    
    -- דואר ל
    mail_to VARCHAR(100),
    
    -- שכונה / אזור
    neighborhood VARCHAR(100),
    
    -- קומה
    floor VARCHAR(50),
    
    -- עיר
    city VARCHAR(100),
    
    -- טלפון
    phone VARCHAR(50),
    
    -- נייד בעל
    husband_mobile VARCHAR(50),
    
    -- שם האשה
    wife_name VARCHAR(100),
    
    -- נייד אשה
    wife_mobile VARCHAR(50),
    
    -- כתובת מייל מעודכן
    updated_email VARCHAR(200),
    
    -- מייל בעל
    husband_email VARCHAR(200),
    
    -- מייל אשה
    wife_email VARCHAR(200),
    
    -- קבלות ל
    receipts_to VARCHAR(100),
    
    -- אלפון
    alphon VARCHAR(100),
    
    -- שליחת הודעות
    send_messages VARCHAR(100),
    
    -- שינוי אחרון
    last_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
