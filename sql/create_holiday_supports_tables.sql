-- Create tables for Holiday Supports system

-- Table for holiday support records
CREATE TABLE IF NOT EXISTS `holiday_supports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `donor_number` VARCHAR(50) DEFAULT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `support_cost` DECIMAL(10,2) DEFAULT 0.00,
  `basic_support` DECIMAL(10,2) DEFAULT 0.00,
  `full_support` DECIMAL(10,2) DEFAULT 0.00,
  `support_date` DATE DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_donor_number` (`donor_number`),
  KEY `idx_approved_at` (`approved_at`),
  KEY `idx_support_date` (`support_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for holiday forms imported from JSON
CREATE TABLE IF NOT EXISTS `holiday_forms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` VARCHAR(50) NOT NULL,
  `created_date` DATETIME DEFAULT NULL,
  `masof_id` VARCHAR(50) DEFAULT NULL,
  `emda` VARCHAR(100) DEFAULT NULL,
  `full_name` VARCHAR(200) DEFAULT NULL,
  `street` VARCHAR(200) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `sum_kids` INT(11) DEFAULT 0,
  `num_kids` INT(11) DEFAULT 0,
  `maskorte_av` DECIMAL(10,2) DEFAULT 0.00,
  `maskorte_am` DECIMAL(10,2) DEFAULT 0.00,
  `hachnasa` DECIMAL(10,2) DEFAULT 0.00,
  `kitzva` DECIMAL(10,2) DEFAULT 0.00,
  `hotzaot_limud` DECIMAL(10,2) DEFAULT 0.00,
  `hotzaot_dira` DECIMAL(10,2) DEFAULT 0.00,
  `hotzaot_chariga` DECIMAL(10,2) DEFAULT 0.00,
  `hotzaot_chariga2` TEXT DEFAULT NULL,
  `sum_nefesh` DECIMAL(10,2) DEFAULT 0.00,
  `help` TEXT DEFAULT NULL,
  `sum_kids2` INT(11) DEFAULT 0,
  `sum_kids3` INT(11) DEFAULT 0,
  `sum_kids_m1` INT(11) DEFAULT 0,
  `sum_kids_m2` INT(11) DEFAULT 0,
  `sum_kids_m3` INT(11) DEFAULT 0,
  `bank_name` VARCHAR(200) DEFAULT NULL,
  `bank` VARCHAR(50) DEFAULT NULL,
  `snif` VARCHAR(50) DEFAULT NULL,
  `account` VARCHAR(50) DEFAULT NULL,
  `name_bakasha` VARCHAR(200) DEFAULT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `kids_data` LONGTEXT DEFAULT NULL,
  `donor_number` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_form_id` (`form_id`),
  KEY `idx_donor_number` (`donor_number`),
  KEY `idx_city` (`city`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for kids data from forms
CREATE TABLE IF NOT EXISTS `holiday_form_kids` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` INT(11) NOT NULL,
  `name` VARCHAR(200) DEFAULT NULL,
  `status` VARCHAR(100) DEFAULT NULL,
  `birthdate` VARCHAR(50) DEFAULT NULL,
  `age` INT(11) DEFAULT 0,
  `gender` ENUM('male', 'female', 'unknown') DEFAULT 'unknown',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_age` (`age`),
  KEY `idx_gender` (`gender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for calculation rules
CREATE TABLE IF NOT EXISTS `holiday_calculations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `conditions` LONGTEXT DEFAULT NULL,
  `amount` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example calculation rules (optional - can be added via UI)
INSERT INTO `holiday_calculations` (`name`, `conditions`, `amount`, `created_at`, `updated_at`) VALUES
('תמיכה בסיסית - עד 3 ילדים', '{"use_kids_count":1,"kids_from":0,"kids_to":3,"use_age":0,"use_city":0,"use_married":0}', 500.00, NOW(), NOW()),
('תמיכה בסיסית - 4-6 ילדים', '{"use_kids_count":1,"kids_from":4,"kids_to":6,"use_age":0,"use_city":0,"use_married":0}', 800.00, NOW(), NOW()),
('תמיכה בסיסית - 7+ ילדים', '{"use_kids_count":1,"kids_from":7,"kids_to":20,"use_age":0,"use_city":0,"use_married":0}', 1200.00, NOW(), NOW());

