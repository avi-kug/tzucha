<?php
/**
 * Production Database Configuration
 * 
 * IMPORTANT: Update these settings when deploying to production server
 */

// Production database settings - UPDATE THESE!
$host = 'localhost';  // Usually 'localhost' or provided by hosting
$dbname = 'YOUR_DB_NAME';  // Database name from hosting panel
$username = 'YOUR_DB_USER';  // Database username
$password = 'YOUR_DB_PASSWORD';  // Database password
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log error instead of displaying it
    error_log('Database connection failed: ' . $e->getMessage());
    die('שגיאה בהתחברות למסד הנתונים. אנא פנה למנהל המערכת.');
}
