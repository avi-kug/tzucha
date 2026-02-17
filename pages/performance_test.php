<?php
/**
 * בדיקת ביצועים - CPU, זיכרון, זמני שאילתות
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

// מדידת זמן התחלה
$startTime = microtime(true);
$startMemory = memory_get_usage();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>בדיקת ביצועים - CPU ושאילתות</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .test-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .metric { padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 10px 0; }
        .metric-good { border-left: 4px solid #28a745; }
        .metric-warning { border-left: 4px solid #ffc107; }
        .metric-bad { border-left: 4px solid #dc3545; }
        .metric-title { font-weight: bold; font-size: 0.9em; color: #666; margin-bottom: 5px; }
        .metric-value { font-size: 1.5em; font-weight: bold; }
        .metric-detail { font-size: 0.85em; color: #888; margin-top: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 0.85em; }
        .spinner { display: inline-block; width: 1rem; height: 1rem; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spinner 0.75s linear infinite; }
        @keyframes spinner { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">🔍 בדיקת ביצועים - מערכת צוחה</h1>
    
    <?php
    // פונקציה למדידת זמן שאילתה
    function measureQuery($pdo, $name, $sql, $params = []) {
        $start = microtime(true);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $end = microtime(true);
        $duration = round(($end - $start) * 1000, 2); // באלפיות שנייה
        
        $class = 'metric-good';
        if ($duration > 100) $class = 'metric-warning';
        if ($duration > 500) $class = 'metric-bad';
        
        return [
            'name' => $name,
            'duration' => $duration,
            'rows' => count($result),
            'class' => $class
        ];
    }
    
    // ====================================
    // 1. מידע מערכת
    // ====================================
    ?>
    <div class="test-card">
        <h3>💻 מידע מערכת</h3>
        <div class="row">
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">PHP Version</div>
                    <div class="metric-value"><?php echo PHP_VERSION; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">Memory Limit</div>
                    <div class="metric-value"><?php echo ini_get('memory_limit'); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">Max Execution Time</div>
                    <div class="metric-value"><?php echo ini_get('max_execution_time'); ?>s</div>
                </div>
            </div>
        </div>
        
        <?php
        // בדיקת גרסת MySQL
        $versionStmt = $pdo->query("SELECT VERSION() as version");
        $mysqlVersion = $versionStmt->fetch(PDO::FETCH_ASSOC)['version'];
        ?>
        <div class="metric metric-good">
            <div class="metric-title">MySQL/MariaDB Version</div>
            <div class="metric-value"><?php echo $mysqlVersion; ?></div>
        </div>
    </div>
    
    <?php
    // ====================================
    // 2. בדיקת שאילתות קריטיות
    // ====================================
    ?>
    <div class="test-card">
        <h3>⚡ בדיקת מהירות שאילתות</h3>
        
        <?php
        $tests = [];
        
        // מספר רשומות בטבלאות
        echo '<h5 class="mt-3">📊 גודל טבלאות:</h5>';
        $tables = ['people', 'supports', 'standing_orders_koach', 'standing_orders_achim', 'regular_expenses', 'fixed_expenses'];
        echo '<div class="row">';
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                echo '<div class="col-md-4"><div class="metric metric-good">';
                echo '<div class="metric-title">' . $table . '</div>';
                echo '<div class="metric-value">' . number_format($count) . '</div>';
                echo '<div class="metric-detail">רשומות</div>';
                echo '</div></div>';
            } catch (Exception $e) {
                // טבלה לא קיימת
            }
        }
        echo '</div>';
        
        echo '<h5 class="mt-4">🔍 שאילתות קריטיות (צריכות להיות מתחת ל-100ms):</h5>';
        
        // בדיקת שאילתות עם אינדקסים
        $tests[] = measureQuery($pdo, 
            "חיפוש לפי אמרכל (עם אינדקס)", 
            "SELECT * FROM people WHERE amarchal = ? LIMIT 10",
            ['כהן']
        );
        
        $tests[] = measureQuery($pdo,
            "חיפוש לפי גזבר (עם אינדקס)",
            "SELECT * FROM people WHERE gizbar = ? LIMIT 10",
            ['לוי']
        );
        
        $tests[] = measureQuery($pdo,
            "רשימת אמרכלים ייחודיים",
            "SELECT DISTINCT amarchal FROM people WHERE amarchal IS NOT NULL AND amarchal <> '' ORDER BY amarchal"
        );
        
        $tests[] = measureQuery($pdo,
            "רשימת גזברים ייחודיים",
            "SELECT DISTINCT gizbar FROM people WHERE gizbar IS NOT NULL AND gizbar <> '' ORDER BY gizbar"
        );
        
        $tests[] = measureQuery($pdo,
            "שאילתה מורכבת - standing_orders עם JOIN",
            "SELECT p.id, p.full_name, COALESCE(SUM(k.amount), 0) as total 
             FROM people p 
             LEFT JOIN standing_orders_koach k ON k.person_id = p.id 
             GROUP BY p.id 
             LIMIT 50"
        );
        
        $tests[] = measureQuery($pdo,
            "supports עם חישובים (אם קיים)",
            "SELECT * FROM supports ORDER BY created_at DESC LIMIT 50"
        );
        
        // הצגת תוצאות
        foreach ($tests as $test) {
            echo '<div class="metric ' . $test['class'] . '">';
            echo '<div class="metric-title">' . $test['name'] . '</div>';
            echo '<div class="metric-value">' . $test['duration'] . ' ms</div>';
            echo '<div class="metric-detail">' . number_format($test['rows']) . ' שורות</div>';
            
            if ($test['duration'] < 50) {
                echo '<div class="metric-detail text-success">✅ מהיר מאוד!</div>';
            } elseif ($test['duration'] < 100) {
                echo '<div class="metric-detail text-success">✅ טוב</div>';
            } elseif ($test['duration'] < 500) {
                echo '<div class="metric-detail text-warning">⚠️ בינוני - יש מקום לשיפור</div>';
            } else {
                echo '<div class="metric-detail text-danger">❌ איטי - דורש אופטימיזציה!</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
    
    <?php
    // ====================================
    // 3. בדיקת אינדקסים
    // ====================================
    ?>
    <div class="test-card">
        <h3>🗂️ סטטוס אינדקסים</h3>
        
        <?php
        $indexQuery = "
            SELECT 
                TABLE_NAME,
                INDEX_NAME,
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') AS columns,
                CASE WHEN NON_UNIQUE = 0 THEN 'UNIQUE' ELSE 'INDEX' END as type
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN ('people', 'supports', 'standing_orders_koach', 'standing_orders_achim')
            GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
            ORDER BY TABLE_NAME, INDEX_NAME
        ";
        
        $indexStmt = $pdo->query($indexQuery);
        $indexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $indexesByTable = [];
        foreach ($indexes as $idx) {
            $indexesByTable[$idx['TABLE_NAME']][] = $idx;
        }
        
        foreach ($indexesByTable as $table => $tableIndexes) {
            echo '<h5>' . $table . ' (' . count($tableIndexes) . ' אינדקסים)</h5>';
            echo '<pre>';
            foreach ($tableIndexes as $idx) {
                echo sprintf("%-30s %-10s %s\n", 
                    $idx['INDEX_NAME'], 
                    $idx['type'], 
                    $idx['columns']
                );
            }
            echo '</pre>';
        }
        
        // בדיקת אינדקסים חסרים
        $criticalIndexes = [
            'people' => ['idx_amarchal', 'idx_gizbar', 'idx_full_name'],
            'supports' => ['idx_person_id', 'idx_support_month'],
            'standing_orders_koach' => ['idx_koach_person_id'],
            'standing_orders_achim' => ['idx_achim_person_id']
        ];
        
        $missing = [];
        foreach ($criticalIndexes as $table => $requiredIndexes) {
            $existing = array_column(array_filter($indexes, fn($i) => $i['TABLE_NAME'] === $table), 'INDEX_NAME');
            foreach ($requiredIndexes as $req) {
                if (!in_array($req, $existing)) {
                    $missing[] = "$table.$req";
                }
            }
        }
        
        if (empty($missing)) {
            echo '<div class="alert alert-success">✅ כל האינדקסים הקריטיים קיימים!</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ אינדקסים חסרים: ' . implode(', ', $missing) . '</div>';
        }
        ?>
    </div>
    
    <?php
    // ====================================
    // 4. בדיקת שימוש בזיכרון
    // ====================================
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    $peakMemory = memory_get_peak_usage();
    
    $totalTime = round(($endTime - $startTime) * 1000, 2);
    $usedMemory = round(($endMemory - $startMemory) / 1024 / 1024, 2);
    $peakMemoryMB = round($peakMemory / 1024 / 1024, 2);
    ?>
    
    <div class="test-card">
        <h3>💾 שימוש במשאבים בדף הבדיקה</h3>
        <div class="row">
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">זמן טעינה כולל</div>
                    <div class="metric-value"><?php echo $totalTime; ?> ms</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">שימוש בזיכרון</div>
                    <div class="metric-value"><?php echo $usedMemory; ?> MB</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric metric-good">
                    <div class="metric-title">זיכרון מקסימלי</div>
                    <div class="metric-value"><?php echo $peakMemoryMB; ?> MB</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="test-card">
        <h3>📝 המלצות</h3>
        <?php
        $avgQueryTime = array_sum(array_column($tests, 'duration')) / count($tests);
        
        if ($avgQueryTime < 50) {
            echo '<div class="alert alert-success">';
            echo '✅ <strong>מצוין!</strong> השאילתות מהירות מאוד (ממוצע: ' . round($avgQueryTime, 2) . 'ms).<br>';
            echo 'המערכת מוכנה לעומס גבוה ולהעלאה לפרודקשן.';
            echo '</div>';
        } elseif ($avgQueryTime < 100) {
            echo '<div class="alert alert-info">';
            echo '✅ <strong>טוב!</strong> השאילתות מהירות (ממוצע: ' . round($avgQueryTime, 2) . 'ms).<br>';
            echo 'המערכת יכולה לטפל במקרי שימוש רגילים.';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">';
            echo '⚠️ <strong>שים לב:</strong> חלק מהשאילתות איטיות (ממוצע: ' . round($avgQueryTime, 2) . 'ms).<br>';
            echo 'מומלץ לבדוק אינדקסים ואופטימיזציה של שאילתות.';
            echo '</div>';
        }
        ?>
        
        <h5>כלים נוספים לבדיקה:</h5>
        <ul>
            <li><strong>Windows Task Manager:</strong> לחץ Ctrl+Shift+Esc לראות שימוש CPU/RAM של Apache ו-MySQL</li>
            <li><strong>MySQL EXPLAIN:</strong> הרץ <code>EXPLAIN SELECT...</code> לבדיקת שימוש באינדקסים</li>
            <li><strong>Chrome DevTools:</strong> F12 → Network → רענן דף אמיתי לראות זמן טעינה</li>
            <li><strong>Apache Benchmark:</strong> <code>ab -n 100 -c 10 http://localhost/tzucha/pages/people.php</code></li>
        </ul>
    </div>
    
    <div class="text-center mt-4 mb-4">
        <button onclick="location.reload()" class="btn btn-primary">🔄 בדיקה מחדש</button>
        <a href="people.php" class="btn btn-secondary">חזרה לדף אנשים</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
