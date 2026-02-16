<?php
require_once '../config/auth.php';

// Only admins can view security logs
if (!auth_is_logged_in() || !auth_has_permission('users')) {
    http_response_code(403);
    echo json_encode(['error' => 'אין הרשאה']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

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
