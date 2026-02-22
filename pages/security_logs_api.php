<?php
/**
 * Security Logs API - Admin Only
 */
require_once '../config/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is admin
if (!auth_is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied - Admin only'
    ]);
    exit;
}

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 100, 60); // Higher limit for admin
    check_request_size();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch logs with filters
    $filters = [];
    $params = [];
    
    if (!empty($_GET['action'])) {
        $filters[] = 'action = ?';
        $params[] = $_GET['action'];
    }
    
    if (!empty($_GET['severity'])) {
        $filters[] = 'severity = ?';
        $params[] = $_GET['severity'];
    }
    
    if (!empty($_GET['date'])) {
        $filters[] = 'DATE(timestamp) = ?';
        $params[] = $_GET['date'];
    }
    
    if (!empty($_GET['username'])) {
        $filters[] = 'username LIKE ?';
        $params[] = '%' . $_GET['username'] . '%';
    }
    
    $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
    
    try {
        // First check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'security_logs'");
        if (!$tableCheck->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Security logs table does not exist. Please run database migrations.'
            ]);
            exit;
        }
        
        // Get logs
        $stmt = $pdo->prepare("
            SELECT * FROM security_logs 
            {$whereClause}
            ORDER BY timestamp DESC 
            LIMIT 500
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
            FROM security_logs
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure stats are never null
        if (!$stats) {
            $stats = [
                'total' => 0,
                'critical' => 0,
                'warning' => 0,
                'last_24h' => 0
            ];
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs ?: [],
            'stats' => $stats,
            'message' => count($logs) === 0 ? 'No logs found' : null
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cleanup old logs
    if ($_POST['action'] === 'cleanup') {
        $days = (int)($_POST['days'] ?? 30);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM security_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$days]);
            $deleted = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'deleted' => $deleted
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
    }
}

exit;

// OLD CODE BELOW (kept for reference, not executed)
// ================================================

$action = $_GET['action'] ?? '';
$logsDir = __DIR__ . '/../storage/logs';

if ($action === 'list_files') {
    // List all security log files
    $files = [];
    if (is_dir($logsDir)) {
        $items = scandir($logsDir);
        foreach ($items as $item) {
            if (preg_match('/^security_(\d{4}-\d{2}-\d{2})\.log$/', $item, $matches)) {
                $filePath = $logsDir . '/' . $item;
                $files[] = [
                    'filename' => $item,
                    'date' => $matches[1],
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }
        // Sort by date descending
        usort($files, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
    }
    echo json_encode(['files' => $files]);
    exit;
}

if ($action === 'read_log') {
    // Read a specific log file
    $filename = $_GET['filename'] ?? '';
    
    // Validate filename to prevent directory traversal
    if (!preg_match('/^security_\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'שם קובץ לא חוקי']);
        exit;
    }
    
    $filePath = $logsDir . '/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'קובץ לא נמצא']);
        exit;
    }
    
    // Read log file and parse entries
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $entries = [];
    
    foreach ($lines as $line) {
        // Parse log format: [timestamp] User: name | IP: address | Event: TYPE | Details: JSON
        if (preg_match('/^\[(.*?)\] User: (.*?) \| IP: (.*?) \| Event: (.*?) \| Details: (.*)$/', $line, $matches)) {
            $details = json_decode($matches[5], true);
            $entries[] = [
                'timestamp' => $matches[1],
                'user' => $matches[2],
                'ip' => $matches[3],
                'event' => $matches[4],
                'details' => $details ?? $matches[5]
            ];
        }
    }
    
    echo json_encode(['entries' => $entries]);
    exit;
}

if ($action === 'download') {
    // Download a specific log file
    $filename = $_GET['filename'] ?? '';
    
    // Validate filename to prevent directory traversal
    if (!preg_match('/^security_\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'שם קובץ לא חוקי']);
        exit;
    }
    
    $filePath = $logsDir . '/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'קובץ לא נמצא']);
        exit;
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'פעולה לא חוקית']);
