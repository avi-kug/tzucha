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

-- Insert some initial data for testing
INSERT INTO departments (name) VALUES ('כללי'), ('מנהלה'), ('חינוך'), ('תרבות');
INSERT INTO categories (name) VALUES ('שכר'), ('שכירות'), ('שירותים'), ('ציוד');
INSERT INTO paid_by_options (name) VALUES ('מזומן'), ('אשראי'), ('העברה בנקאית'), ('צ׳ק');
INSERT INTO from_accounts (name) VALUES ('חשבון ראשי'), ('חשבון משני'), ('קופה');
