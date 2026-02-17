<?php
/**
 * הוספת אינדקסים לטבלאות הוצאות
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

echo "🚀 הוספת אינדקסים לטבלאות הוצאות...\n\n";

function indexExists($pdo, $table, $indexName) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->execute([$table, $indexName]);
    return $stmt->fetchColumn() > 0;
}

function addIndexIfNotExists($pdo, $table, $indexName, $columns, $description = '') {
    try {
        if (indexExists($pdo, $table, $indexName)) {
            echo "⏭️  אינדקס $indexName על $table כבר קיים - מדלג\n";
            return false;
        }
        
        $columnsList = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "CREATE INDEX $indexName ON `$table` ($columnsList)";
        $pdo->exec($sql);
        echo "✅ נוסף אינדקס $indexName על $table ($columnsList)";
        if ($description) echo " - $description";
        echo "\n";
        return true;
    } catch (PDOException $e) {
        echo "❌ שגיאה בהוספת $indexName על $table: " . $e->getMessage() . "\n";
        return false;
    }
}

$addedCount = 0;

// טבלת regular_expenses
echo "📊 regular_expenses:\n";
$indexes = [
    ['idx_date', 'date', 'מיון וסינון לפי תאריך'],
    ['idx_amount', 'amount', 'מיון וסכימות'],
    ['idx_category', 'category', 'סינון לפי קטגוריה'],
    ['idx_department', 'department', 'סינון לפי אגף'],
];
foreach ($indexes as list($idx, $col, $desc)) {
    if (addIndexIfNotExists($pdo, 'regular_expenses', $idx, $col, $desc)) $addedCount++;
}

echo "\n📊 fixed_expenses:\n";
foreach ($indexes as list($idx, $col, $desc)) {
    if (addIndexIfNotExists($pdo, 'fixed_expenses', $idx, $col, $desc)) $addedCount++;
}

echo "\n📊 summary_expenses:\n";
foreach ($indexes as list($idx, $col, $desc)) {
    if (addIndexIfNotExists($pdo, 'summary_expenses', $idx, $col, $desc)) $addedCount++;
}

echo "\n✅ נוספו $addedCount אינדקסים לטבלאות הוצאות!\n";
