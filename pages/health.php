<?php
/**
 * Health Check Endpoint for Monitoring
 * Returns JSON with system health status
 */

header('Content-Type: application/json');

require_once '../config/db.php';

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

try {
    // 1. Database Connection
    try {
        $stmt = $pdo->query('SELECT 1');
        $health['checks']['database'] = [
            'status' => 'ok',
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
        $health['status'] = 'unhealthy';
    }

    // 2. Database Tables Exist
    try {
        $requiredTables = ['users', 'people', 'supports', 'expenses', 'login_attempts', 'security_logs'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            $health['checks']['tables'] = [
                'status' => 'ok',
                'message' => 'All required tables exist'
            ];
        } else {
            $health['checks']['tables'] = [
                'status' => 'warning',
                'message' => 'Missing tables: ' . implode(', ', $missingTables)
            ];
            $health['status'] = 'degraded';
        }
    } catch (Exception $e) {
        $health['checks']['tables'] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }

    // 3. Disk Space
    $freeSpace = @disk_free_space(__DIR__);
    $totalSpace = @disk_total_space(__DIR__);
    if ($freeSpace !== false && $totalSpace !== false) {
        $percentFree = ($freeSpace / $totalSpace) * 100;
        $freeGB = round($freeSpace / 1024 / 1024 / 1024, 2);
        
        if ($percentFree < 5) {
            $health['checks']['disk_space'] = [
                'status' => 'critical',
                'message' => "Low disk space: {$freeGB}GB free ({$percentFree}%)",
                'free_gb' => $freeGB,
                'percent_free' => round($percentFree, 2)
            ];
            $health['status'] = 'unhealthy';
        } elseif ($percentFree < 10) {
            $health['checks']['disk_space'] = [
                'status' => 'warning',
                'message' => "Disk space getting low: {$freeGB}GB free ({$percentFree}%)",
                'free_gb' => $freeGB,
                'percent_free' => round($percentFree, 2)
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        } else {
            $health['checks']['disk_space'] = [
                'status' => 'ok',
                'message' => "Disk space: {$freeGB}GB free",
                'free_gb' => $freeGB,
                'percent_free' => round($percentFree, 2)
            ];
        }
    }

    // 4. Memory Usage
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = return_bytes($memoryLimit);
    $memoryUsageMB = round($memoryUsage / 1024 / 1024, 2);
    $percentUsed = ($memoryUsage / $memoryLimitBytes) * 100;
    
    $health['checks']['memory'] = [
        'status' => $percentUsed > 80 ? 'warning' : 'ok',
        'message' => "Memory usage: {$memoryUsageMB}MB",
        'usage_mb' => $memoryUsageMB,
        'limit' => $memoryLimit,
        'percent_used' => round($percentUsed, 2)
    ];

    // 5. Log Directory Writable
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    if (is_writable($logDir)) {
        $health['checks']['logs'] = [
            'status' => 'ok',
            'message' => 'Log directory is writable'
        ];
    } else {
        $health['checks']['logs'] = [
            'status' => 'error',
            'message' => 'Log directory is not writable'
        ];
        $health['status'] = 'unhealthy';
    }

    // 6. Uploads Directory Writable
    $uploadsDir = __DIR__ . '/../uploads';
    if (is_writable($uploadsDir)) {
        $health['checks']['uploads'] = [
            'status' => 'ok',
            'message' => 'Uploads directory is writable'
        ];
    } else {
        $health['checks']['uploads'] = [
            'status' => 'error',
            'message' => 'Uploads directory is not writable'
        ];
        $health['status'] = 'unhealthy';
    }

    // 7. Recent Login Activity
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM login_attempts WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $recentLogins = (int)$stmt->fetchColumn();
        
        $health['checks']['activity'] = [
            'status' => 'info',
            'message' => "Recent login attempts: {$recentLogins} in last hour",
            'count' => $recentLogins
        ];
    } catch (Exception $e) {
        // Ignore if table doesn't exist yet
    }

} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['error'] = $e->getMessage();
}

// Set HTTP status code based on health
if ($health['status'] === 'unhealthy') {
    http_response_code(503); // Service Unavailable
} elseif ($health['status'] === 'degraded') {
    http_response_code(200); // OK but with warnings
} else {
    http_response_code(200); // OK
}

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
