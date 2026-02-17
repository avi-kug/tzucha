<?php
/**
 * Run: Add logout tracking column to login_attempts table
 */
require_once __DIR__ . '/../config/db.php';

$sql = file_get_contents(__DIR__ . '/alter_login_attempts_add_logout_tracking.sql');

try {
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ SUCCESS: logout tracking column added to login_attempts!\n";
    
    // Show table structure
    $columns = $pdo->query('SHOW COLUMNS FROM login_attempts')->fetchAll(PDO::FETCH_ASSOC);
    echo "\n📋 Current table structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
