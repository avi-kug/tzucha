<?php
/**
 * סקריפט להוספת אינדקסים לשיפור ביצועים
 * מטפל בצורה חכמה באינדקסים קיימים
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

echo "🚀 התחלת תהליך הוספת אינדקסים...\n\n";

// פונקציה לבדיקה אם אינדקס קיים
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

// פונקציה להוספת אינדקס אם לא קיים
function addIndexIfNotExists($pdo, $table, $indexName, $columns, $description = '') {
    try {
        if (indexExists($pdo, $table, $indexName)) {
            echo "⏭️  אינדקס $indexName על $table כבר קיים - מדלג\n";
            return false;
        }
        
        $columnsList = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "CREATE INDEX $indexName ON $table ($columnsList)";
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
$skippedCount = 0;
$errorCount = 0;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 טבלת PEOPLE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexes = [
    ['idx_amarchal', 'amarchal', 'חיפוש וסינון לפי אמרכל'],
    ['idx_gizbar', 'gizbar', 'חיפוש וסינון לפי גזבר'],
    ['idx_full_name', 'full_name', 'מיון ואוטומציה'],
    ['idx_names', ['family_name', 'first_name'], 'מיון אלפביתי'],
    ['idx_donor_number', 'donor_number', 'חיפוש תורמים'],
    ['idx_husband_id', 'husband_id', 'חיפוש לפי ת.ז. בעל'],
    ['idx_wife_id', 'wife_id', 'חיפוש לפי ת.ז. אישה'],
];

foreach ($indexes as list($indexName, $columns, $desc)) {
    $result = addIndexIfNotExists($pdo, 'people', $indexName, $columns, $desc);
    if ($result === true) $addedCount++;
    elseif ($result === false && strpos(ob_get_clean() ?: '', 'כבר קיים') !== false) $skippedCount++;
    else $errorCount++;
    ob_start();
}
@ob_end_clean();

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 טבלת SUPPORTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexes = [
    ['idx_support_month', 'support_month', 'סינון לפי חודש'],
    ['idx_household_members', 'household_members', 'חישובים וסטטיסטיקות'],
];

foreach ($indexes as list($indexName, $columns, $desc)) {
    $result = addIndexIfNotExists($pdo, 'supports', $indexName, $columns, $desc);
    if ($result === true) $addedCount++;
    elseif ($result === false) $skippedCount++;
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 טבלת EXPENSES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexes = [
    ['idx_date', 'date', 'מיון וסינון לפי תאריכים'],
    ['idx_amount', 'amount', 'מיון וסכימות'],
    ['idx_category', 'category', 'סינון לפי קטגוריה'],
    ['idx_department', 'department', 'סינון לפי אגף'],
    ['idx_date_category', ['date', 'category'], 'דוחות משולבים'],
];

foreach ($indexes as list($indexName, $columns, $desc)) {
    $result = addIndexIfNotExists($pdo, 'expenses', $indexName, $columns, $desc);
    if ($result === true) $addedCount++;
    elseif ($result === false) $skippedCount++;
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 טבלת STANDING_ORDERS_KOACH\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexes = [
    ['idx_koach_person_id', 'person_id', 'חיבור לאנשים'],
    ['idx_koach_donation_date', 'donation_date', 'סינון לפי תאריך'],
    ['idx_koach_amount', 'amount', 'סכימות וסטטיסטיקות'],
];

foreach ($indexes as list($indexName, $columns, $desc)) {
    $result = addIndexIfNotExists($pdo, 'standing_orders_koach', $indexName, $columns, $desc);
    if ($result === true) $addedCount++;
    elseif ($result === false) $skippedCount++;
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 טבלת STANDING_ORDERS_ACHIM\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$indexes = [
    ['idx_achim_person_id', 'person_id', 'חיבור לאנשים'],
    ['idx_achim_donation_date', 'donation_date', 'סינון לפי תאריך'],
    ['idx_achim_amount', 'amount', 'סכימות וסטטיסטיקות'],
];

foreach ($indexes as list($indexName, $columns, $desc)) {
    $result = addIndexIfNotExists($pdo, 'standing_orders_achim', $indexName, $columns, $desc);
    if ($result === true) $addedCount++;
    elseif ($result === false) $skippedCount++;
}

// סיכום
echo "\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📈 סיכום\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ אינדקסים חדשים שנוספו: $addedCount\n";
echo "⏭️  אינדקסים שכבר היו קיימים: $skippedCount\n";
if ($errorCount > 0) {
    echo "❌ שגיאות: $errorCount\n";
}
echo "\n";

// הצגת כל האינדקסים הקיימים
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 רשימת כל האינדקסים במערכת\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$stmt = $pdo->query("
    SELECT 
        TABLE_NAME,
        INDEX_NAME,
        GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') AS columns
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('people', 'supports', 'expenses', 'standing_orders_koach', 'standing_orders_achim')
    GROUP BY TABLE_NAME, INDEX_NAME
    ORDER BY TABLE_NAME, INDEX_NAME
");

$currentTable = '';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($currentTable !== $row['TABLE_NAME']) {
        $currentTable = $row['TABLE_NAME'];
        echo "\n📊 $currentTable:\n";
    }
    echo "   • {$row['INDEX_NAME']} ({$row['columns']})\n";
}

echo "\n🎉 תהליך הוספת אינדקסים הושלם!\n";
echo "⚡ המערכת צפויה להיות מהירה יותר כעת.\n\n";
