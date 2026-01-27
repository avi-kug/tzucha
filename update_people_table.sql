-- Update people table with all required fields
USE tzucha;

-- Drop the existing people table
DROP TABLE IF EXISTS people;

-- Create the new people table with all fields
CREATE TABLE people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- אתר נט גרוב
    netgrov_site VARCHAR(100),
    
    -- מזהה תנובה
    tnuva_id VARCHAR(100),
    
    -- מספר תקים
    file_number VARCHAR(100),
    
    -- תו"ז הרכר
    coordinator_id VARCHAR(20),
    
    -- משפחה
    family_name VARCHAR(100) NOT NULL,
    
    -- שם
    first_name VARCHAR(100) NOT NULL,
    
    -- שם לאדרה
    address_name VARCHAR(100),
    
    -- שם לאדח תפקיד
    role_name VARCHAR(100),
    
    -- תעודת זהות בעל
    husband_id VARCHAR(20),
    
    -- תעודת זהות אשה
    wife_id VARCHAR(20),
    
    -- כתובת
    address TEXT,
    
    -- ת דואר
    po_box VARCHAR(50),
    
    -- שנה / אהד
    year_or_other VARCHAR(100),
    
    -- קומה
    floor VARCHAR(50),
    
    -- עיר
    city VARCHAR(100),
    
    -- טלפון
    phone VARCHAR(50),
    
    -- פיד בעל
    husband_mobile VARCHAR(50),
    
    -- טלפון בית
    home_phone VARCHAR(50),
    
    -- פיד אשה
    wife_mobile VARCHAR(50),
    
    -- כתובת נייל משפחתי
    family_email VARCHAR(200),
    
    -- נייל בעל
    husband_email VARCHAR(200),
    
    -- נייל אשה
    wife_email VARCHAR(200),
    
    -- קבלת?
    receive_option VARCHAR(100),
    
    -- מקום
    location VARCHAR(100),
    
    -- שליחית דואל
    mail_delivery VARCHAR(100),
    
    -- שווי אתנון
    value VARCHAR(100),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
