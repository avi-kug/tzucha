<?php
require_once 'config/db.php';

echo "Checking holiday supports tables...\n\n";

$tables = ['holiday_supports', 'holiday_forms', 'holiday_form_kids', 'holiday_calculations'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Table '$table' exists with {$result['count']} records\n";
    } catch (PDOException $e) {
        echo "✗ Table '$table' does not exist or has errors\n";
    }
}

echo "\n✅ Verification complete!\n";
