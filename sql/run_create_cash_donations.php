<?php
require_once __DIR__ . '/../config/db.php';

$sql = file_get_contents(__DIR__ . '/create_cash_donations_table.sql');

try {
    $pdo->exec($sql);
    echo "✅ Tabla cash_donations created successfully\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
