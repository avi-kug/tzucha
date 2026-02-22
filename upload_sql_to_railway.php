<?php
/**
 * SQL Upload Script for Railway
 * העלאת קובץ SQL למסד נתונים Railway
 * 
 * שימוש:
 * 1. העלה את הקובץ הזה לשרת Railway
 * 2. העלה את קובץ ה-SQL לאותה תיקייה
 * 3. גש ל: https://your-domain.railway.app/upload_sql_to_railway.php
 * 4. הסקריפט יריץ את ה-SQL אוטומטית
 * 
 * אבטחה: מחק את הקובץ הזה אחרי השימוש!
 */

// הגדרות אבטחה - רק בסביבת ייצור
$isProduction = !empty($_SERVER['SERVER_NAME']) && 
                strpos($_SERVER['SERVER_NAME'], 'localhost') === false;

if (!$isProduction) {
    die('סקריפט זה פועל רק בסביבת Railway Production.');
}

// טען את קובץ החיבור למסד הנתונים
require_once __DIR__ . '/config/db.php';

// נתיב לקובץ SQL
$sqlFilePath = __DIR__ . '/tzucha (2).sql';

// בדוק אם הקובץ קיים
if (!file_exists($sqlFilePath)) {
    die("❌ קובץ SQL לא נמצא: {$sqlFilePath}<br><br>
         אנא העלה את הקובץ 'tzucha (2).sql' לתיקיית השורש של הפרויקט.");
}

echo "<h2>🚀 מעלה את מסד הנתונים ל-Railway...</h2>";
echo "<p>קובץ: " . basename($sqlFilePath) . "</p>";
echo "<p>גודל: " . number_format(filesize($sqlFilePath) / 1024 / 1024, 2) . " MB</p>";
echo "<hr>";

try {
    // קרא את קובץ ה-SQL
    $sql = file_get_contents($sqlFilePath);
    
    if ($sql === false) {
        throw new Exception('שגיאה בקריאת קובץ ה-SQL');
    }
    
    echo "<p>✅ קובץ ה-SQL נקרא בהצלחה</p>";
    
    // פצל את ה-SQL לפקודות נפרדות
    // הסר הערות והשורות הריקות
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/^\/\*.*?\*\//ms', '', $sql);
    
    // פצל לפי נקודה-פסיק בסוף השורה
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            return !empty($query);
        }
    );
    
    $totalQueries = count($queries);
    echo "<p>📊 נמצאו {$totalQueries} פקודות SQL</p>";
    echo "<hr>";
    
    // בטל את בדיקות foreign key זמנית
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    echo "<p>⚙️ בוטלו בדיקות Foreign Key</p>";
    
    // התחל טרנזקציה
    $pdo->beginTransaction();
    echo "<p>🔄 התחלת טרנזקציה...</p>";
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // בצע כל פקודה
    foreach ($queries as $index => $query) {
        $queryNum = $index + 1;
        
        // הצג התקדמות כל 100 פקודות
        if ($queryNum % 100 === 0) {
            echo "<p>⏳ מעבד פקודה {$queryNum}/{$totalQueries}...</p>";
            flush();
        }
        
        try {
            $pdo->exec($query);
            $successCount++;
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = [
                'query_num' => $queryNum,
                'error' => $e->getMessage(),
                'query' => substr($query, 0, 200) . '...'
            ];
            
            // אם יש יותר מ-10 שגיאות, עצור
            if ($errorCount > 10) {
                throw new Exception('יותר מדי שגיאות. מבטל את התהליך.');
            }
        }
    }
    
    // אשר את הטרנזקציה
    $pdo->commit();
    echo "<p>✅ טרנזקציה אושרה</p>";
    
    // החזר את בדיקות foreign key
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "<p>⚙️ הוחזרו בדיקות Foreign Key</p>";
    
    echo "<hr>";
    echo "<h3>📈 סיכום:</h3>";
    echo "<p>✅ פקודות שהצליחו: <strong>{$successCount}</strong></p>";
    echo "<p>❌ פקודות שנכשלו: <strong>{$errorCount}</strong></p>";
    
    if (!empty($errors)) {
        echo "<hr>";
        echo "<h4>⚠️ שגיאות:</h4>";
        echo "<ul>";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "<li>";
            echo "<strong>פקודה #{$error['query_num']}:</strong> ";
            echo htmlspecialchars($error['error']);
            echo "</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 ההעלאה הושלמה!</h3>";
    echo "<p><strong>חשוב:</strong> למחוק את הקבצים הבאים מהשרת לאבטחה:</p>";
    echo "<ul>";
    echo "<li>upload_sql_to_railway.php (קובץ זה)</li>";
    echo "<li>tzucha (2).sql (קובץ ה-SQL)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    // בטל את הטרנזקציה במקרה של שגיאה
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<p>🔙 הטרנזקציה בוטלה</p>";
    }
    
    echo "<hr>";
    echo "<h3>❌ שגיאה חמורה:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (!empty($errors)) {
        echo "<h4>שגיאות שנמצאו לפני הכישלון:</h4>";
        echo "<ul>";
        foreach (array_slice($errors, 0, 5) as $error) {
            echo "<li>";
            echo "<strong>פקודה #{$error['query_num']}:</strong> ";
            echo htmlspecialchars($error['error']);
            echo "</li>";
        }
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p>⏰ זמן: " . date('Y-m-d H:i:s') . "</p>";
?>
