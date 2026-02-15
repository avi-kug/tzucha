-- Create standing orders tables for Koach HaRabim and Achim LeChesed

CREATE TABLE IF NOT EXISTS `standing_orders_koach` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `donation_date` DATE DEFAULT NULL,
  `full_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `amount` DECIMAL(10,2) DEFAULT 0.00,
  `last4` VARCHAR(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `method` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'אשראי',
  `notes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `person_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_person_id` (`person_id`),
  KEY `idx_donation_date` (`donation_date`),
  CONSTRAINT `fk_standing_orders_koach_person` FOREIGN KEY (`person_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `standing_orders_achim` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `donation_date` DATE DEFAULT NULL,
  `full_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `amount` DECIMAL(10,2) DEFAULT 0.00,
  `last4` VARCHAR(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `method` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'אשראי',
  `notes` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `person_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_person_id` (`person_id`),
  KEY `idx_donation_date` (`donation_date`),
  CONSTRAINT `fk_standing_orders_achim_person` FOREIGN KEY (`person_id`) REFERENCES `people` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add active standing orders columns to people table if they don't exist
ALTER TABLE `people` 
ADD COLUMN IF NOT EXISTS `active_so_koach` TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `active_so_achim` TINYINT(1) DEFAULT 0;
