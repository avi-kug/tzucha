<?php
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE holiday_supports ADD COLUMN notes TEXT DEFAULT NULL AFTER support_date");
    echo "✅ Notes column added successfully to holiday_supports table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Notes column already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
