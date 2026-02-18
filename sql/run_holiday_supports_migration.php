<?php
// Run this script to create the holiday supports database tables
// From project root: php sql/run_holiday_supports_migration.php
// Or navigate to sql folder and run: php run_holiday_supports_migration.php

// Determine correct path to config
$configPath = file_exists(__DIR__ . '/../config/db.php') 
    ? __DIR__ . '/../config/db.php' 
    : __DIR__ . '/config/db.php';

if (!file_exists($configPath)) {
    die("Error: Cannot find config/db.php. Please run from project root or sql directory.\n");
}

require_once $configPath;

echo "Database connection established.\n";
echo "Current database: " . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n\n";

try {
    // Read SQL file
    $sqlFile = __DIR__ . '/create_holiday_supports_tables.sql';
    if (!file_exists($sqlFile)) {
        die("Error: Cannot find create_holiday_supports_tables.sql\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "SQL file size: " . strlen($sql) . " bytes\n";
    
    // Remove comments
    $sql = preg_replace('/--[^\n]*\n/', '', $sql);
    
    // Split by semicolons (statements)
    $rawStatements = explode(';', $sql);
    $statements = [];
    
    foreach ($rawStatements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt) && strlen($stmt) > 10) {
            $statements[] = $stmt;
        }
    }
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    // Execute each statement
    $successCount = 0;
    $errorCount = 0;
    foreach ($statements as $i => $statement) {
        if (!empty($statement)) {
            try {
                $affected = $pdo->exec($statement);
                $successCount++;
                $stmtPreview = substr(preg_replace('/\s+/', ' ', $statement), 0, 80);
                echo "✓ Statement " . ($i+1) . " executed (affected: $affected): $stmtPreview...\n";
            } catch (PDOException $e) {
                $errorCount++;
                echo "✗ Error in statement " . ($i+1) . ": " . $e->getMessage() . "\n";
                $stmtPreview = substr(preg_replace('/\s+/', ' ', $statement), 0, 100);
                echo "   Statement: $stmtPreview...\n\n";
            }
        }
    }
    
    echo "\n✅ Database migration completed!\n";
    echo "Success: $successCount, Errors: $errorCount\n\n";
    echo "Tables that should be created:\n";
    echo "- holiday_supports\n";
    echo "- holiday_forms\n";
    echo "- holiday_form_kids\n";
    echo "- holiday_calculations\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
