<?php
/**
 * Enhanced Authentication with Rate Limiting and Security Logging
 */

if (session_status() === PHP_SESSION_NONE) {
    // Production-safe error handling
    $isProduction = !empty($_SERVER['SERVER_NAME']) && 
                    strpos($_SERVER['SERVER_NAME'], 'localhost') === false;
    
    if ($isProduction) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
    } else {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
    }
    error_reporting(E_ALL);
    
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; style-src 'self' https: 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

/**
 * Security Logger - Writes to both file and database
 */
function security_log($action, $details = [], $severity = 'info') {
    global $pdo;
    
    // 1. Write to file (backup)
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'action' => $action,
        'details' => $details,
        'severity' => $severity
    ];
    
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    // 2. Write to database (if available)
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO security_logs 
                (timestamp, ip_address, user_agent, user_id, username, action, details, severity) 
                VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $entry['ip'],
                $entry['user_agent'],
                $entry['user_id'],
                $entry['username'],
                $action,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $severity
            ]);
        } catch (Exception $e) {
            // Silently fail if table doesn't exist yet
            error_log("Security log DB write failed: " . $e->getMessage());
        }
    }
}

/**
 * Rate Limiting Protection
 * 
 * @param string $identifier Unique identifier (username, IP, etc.)
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds (default: 15 minutes)
 * @return bool True if allowed, throws exception if rate limited
 * @throws Exception When rate limit exceeded
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if exceeded
    if ($data['count'] >= $max_attempts) {
        $wait_time = $time_window - (time() - $data['first_attempt']);
        $minutes = ceil($wait_time / 60);
        
        security_log('rate_limit_exceeded', [
            'identifier' => $identifier,
            'attempts' => $data['count'],
            'wait_minutes' => $minutes
        ]);
        
        throw new Exception("יותר מדי נסיונות כושלים. נסה שוב בעוד {$minutes} דקות.");
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Reset rate limit after successful action
 */
function reset_rate_limit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Global API Rate Limiting - Protects against DDoS attacks
 * 
 * @param string $identifier Identifier (IP, user_id, etc.)
 * @param int $max_requests Maximum requests allowed
 * @param int $time_window Time window in seconds (default: 60 seconds)
 * @return bool True if allowed, throws exception if rate limited
 * @throws Exception When rate limit exceeded
 */
function check_api_rate_limit($identifier = null, $max_requests = 60, $time_window = 60) {
    // Use IP address if no identifier provided
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = 'api_rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_request' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_request'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_request' => time()];
        return true;
    }
    
    // Check if exceeded
    if ($data['count'] >= $max_requests) {
        $wait_time = $time_window - (time() - $data['first_request']);
        $seconds = ceil($wait_time);
        
        security_log('api_rate_limit_exceeded', [
            'identifier' => $identifier,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'requests' => $data['count'],
            'wait_seconds' => $seconds
        ], 'warning');
        
        http_response_code(429); // Too Many Requests
        throw new Exception("יותר מדי בקשות. נסה שוב בעוד {$seconds} שניות.");
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Check request size to prevent large payload attacks
 * 
 * @param int $max_size Maximum size in bytes (default: 10MB)
 * @throws Exception When request size exceeds limit
 */
function check_request_size($max_size = 10485760) { // 10MB default
    $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
    
    if ($content_length > $max_size) {
        security_log('request_size_exceeded', [
            'size' => $content_length,
            'max' => $max_size,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ], 'warning');
        
        http_response_code(413); // Payload Too Large
        throw new Exception("גודל הבקשה חורג מהמותר.");
    }
    
    return true;
}

/**
 * Block suspicious IPs (simple blacklist check)
 * 
 * @param PDO $pdo Database connection
 * @throws Exception When IP is blocked
 */
function check_ip_blacklist($pdo) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare('SELECT blocked_until, reason FROM ip_blacklist WHERE ip_address = ? AND (blocked_until IS NULL OR blocked_until > NOW())');
        $stmt->execute([$ip]);
        $block = $stmt->fetch();
        
        if ($block) {
            security_log('blocked_ip_attempt', [
                'ip' => $ip,
                'reason' => $block['reason'] ?? 'unknown',
                'blocked_until' => $block['blocked_until']
            ], 'critical');
            
            http_response_code(403);
            throw new Exception("הגישה נחסמה.");
        }
    } catch (PDOException $e) {
        // Table might not exist yet, silently ignore
        error_log("IP blacklist check failed: " . $e->getMessage());
    }
    
    return true;
}

function auth_is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function auth_is_admin() {
    return !empty($_SESSION['is_admin']);
}

function auth_role() {
    return $_SESSION['role'] ?? 'viewer';
}

function role_permissions($role) {
    switch ($role) {
        case 'admin':
            return ['*'];
        case 'manager':
            return [];
        case 'viewer':
        default:
            return ['home','people','alphon'];
    }
}

function auth_permissions() {
    return $_SESSION['permissions'] ?? [];
}

function auth_has_permission($key) {
    if (auth_is_admin()) { return true; }
    $rolePerms = role_permissions(auth_role());
    if (in_array('*', $rolePerms, true)) { return true; }
    if (in_array($key, $rolePerms, true)) { return true; }
    return in_array($key, auth_permissions(), true);
}

function auth_load_permissions($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT permission_key FROM user_permissions WHERE user_id = ?');
    $stmt->execute([$userId]);
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $_SESSION['permissions'] = $perms ?: [];
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate() {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function password_policy_validate($password, &$error = '') {
    if (strlen($password) < 8) { $error = 'סיסמה חייבת להכיל לפחות 8 תווים.'; return false; }
    if (!preg_match('/[A-Z]/', $password)) { $error = 'סיסמה חייבת להכיל אות גדולה.'; return false; }
    if (!preg_match('/[a-z]/', $password)) { $error = 'סיסמה חייבת להכיל אות קטנה.'; return false; }
    if (!preg_match('/\d/', $password)) { $error = 'סיסמה חייבת להכיל ספרה.'; return false; }
    return true;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 1 << 16,
        'time_cost' => 4,
        'threads' => 2
    ]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function auth_require_login($pdo) {
    if (!auth_is_logged_in()) {
        security_log('unauthorized_access', [
            'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
        ]);
        header('Location: /tzucha/pages/login.php');
        exit;
    }
}

function auth_require_permission($key) {
    if (!auth_has_permission($key)) {
        security_log('permission_denied', [
            'required_permission' => $key,
            'user_role' => auth_role(),
            'page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        http_response_code(403);
        die('אין לך הרשאה לגשת לעמוד זה.');
    }
}

function auth_logout() {
    security_log('logout', ['user_id' => $_SESSION['user_id'] ?? 'unknown']);
    
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * IDOR Protection - Check if user owns or has access to a resource
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name (e.g., 'people', 'supports')
 * @param int $resourceId Resource ID to check
 * @param string $ownerField Field name that contains owner/user reference (default: null for public access)
 * @return bool True if user has access
 * @throws Exception When access is denied
 */
function check_resource_access($pdo, $table, $resourceId, $ownerField = null) {
    // Admin has access to everything
    if (auth_is_admin()) {
        return true;
    }
    
    // If no owner field specified, any authenticated user can access
    if ($ownerField === null) {
        return true;
    }
    
    // Check if resource belongs to current user
    $userId = $_SESSION['user_id'] ?? 0;
    if (!$userId) {
        security_log('idor_attempt', [
            'table' => $table,
            'resource_id' => $resourceId,
            'reason' => 'no_user_session'
        ], 'warning');
        throw new Exception('אין הרשאה לגשת למשאב זה.');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE id = ? AND `{$ownerField}` = ?");
        $stmt->execute([$resourceId, $userId]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            security_log('idor_blocked', [
                'table' => $table,
                'resource_id' => $resourceId,
                'user_id' => $userId,
                'owner_field' => $ownerField
            ], 'warning');
            throw new Exception('אין הרשאה לגשת למשאב זה.');
        }
        
        return true;
    } catch (PDOException $e) {
        security_log('idor_check_failed', [
            'table' => $table,
            'resource_id' => $resourceId,
            'error' => $e->getMessage()
        ], 'error');
        throw new Exception('שגיאה בבדיקת הרשאות.');
    }
}

/**
 * Double Submit Prevention - Idempotency Key Validation
 * Prevents duplicate submissions of the same action
 * 
 * @param PDO $pdo Database connection
 * @param string $action Action identifier (e.g., 'payment', 'approve_support')
 * @param array $data Action data to hash for uniqueness
 * @param int $window Time window in seconds to check for duplicates (default: 300 = 5 minutes)
 * @return bool True if allowed
 * @throws Exception When duplicate action detected
 */
function check_idempotency($pdo, $action, $data = [], $window = 300) {
    $userId = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Generate unique hash for this action
    $dataHash = hash('sha256', json_encode($data));
    $key = $action . '_' . $userId . '_' . $dataHash;
    
    // Check session-based cache first (faster)
    $sessionKey = 'idempotency_' . md5($key);
    if (isset($_SESSION[$sessionKey])) {
        $cached = $_SESSION[$sessionKey];
        if ((time() - $cached['timestamp']) < $window) {
            security_log('duplicate_submission_blocked', [
                'action' => $action,
                'user_id' => $userId,
                'seconds_ago' => time() - $cached['timestamp']
            ], 'warning');
            throw new Exception('הפעולה כבר בוצעה לאחרונה. אנא המתן מספר שניות.');
        }
    }
    
    // Store in session for future checks
    $_SESSION[$sessionKey] = [
        'timestamp' => time(),
        'action' => $action
    ];
    
    // Also check database for persistent storage (survives session restart)
    try {
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idempotency_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action_key VARCHAR(255) NOT NULL,
                user_id INT,
                ip_address VARCHAR(45),
                action_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action_key (action_key),
                INDEX idx_created_at (created_at)
            )
        ");
        
        // Check for duplicate
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM idempotency_keys 
            WHERE action_key = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$key, $window]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            security_log('duplicate_submission_db_blocked', [
                'action' => $action,
                'user_id' => $userId
            ], 'warning');
            throw new Exception('הפעולה כבר בוצעה לאחרונה.');
        }
        
        // Store this action
        $stmt = $pdo->prepare("
            INSERT INTO idempotency_keys (action_key, user_id, ip_address, action_data) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$key, $userId, $ip, json_encode($data)]);
        
        // Cleanup old entries (older than 1 hour)
        $pdo->exec("DELETE FROM idempotency_keys WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        
        return true;
    } catch (PDOException $e) {
        // If table creation fails, just log and continue (non-critical)
        error_log("Idempotency check failed: " . $e->getMessage());
        return true;
    }
}

/**
 * Export Throttling - Limit data export frequency and volume
 * 
 * @param PDO $pdo Database connection
 * @param string $exportType Type of export (e.g., 'people', 'supports', 'reports')
 * @param int $recordCount Number of records being exported
 * @param int $maxExports Maximum exports per time window (default: 5)
 * @param int $window Time window in seconds (default: 3600 = 1 hour)
 * @param int $maxRecords Maximum records per export (default: 10000)
 * @return bool True if allowed
 * @throws Exception When throttle limit exceeded
 */
function check_export_throttle($pdo, $exportType, $recordCount, $maxExports = 5, $window = 3600, $maxRecords = 10000) {
    $userId = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Admin has higher limits
    if (auth_is_admin()) {
        $maxExports = 20;
        $maxRecords = 50000;
    }
    
    // Check record count limit
    if ($recordCount > $maxRecords) {
        security_log('export_too_large', [
            'export_type' => $exportType,
            'record_count' => $recordCount,
            'max_records' => $maxRecords,
            'user_id' => $userId
        ], 'warning');
        throw new Exception("ייצוא גדול מדי. מקסימום {$maxRecords} רשומות מותרות.");
    }
    
    // Check frequency limit
    $key = 'export_throttle_' . md5($exportType . '_' . $userId);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_export' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_export'] > $window) {
        $_SESSION[$key] = ['count' => 1, 'first_export' => time()];
    } else {
        // Check if exceeded
        if ($data['count'] >= $maxExports) {
            $wait_time = $window - (time() - $data['first_export']);
            $minutes = ceil($wait_time / 60);
            
            security_log('export_throttle_exceeded', [
                'export_type' => $exportType,
                'exports' => $data['count'],
                'user_id' => $userId,
                'wait_minutes' => $minutes
            ], 'warning');
            
            throw new Exception("יותר מדי ייצואים. נסה שוב בעוד {$minutes} דקות.");
        }
        
        $_SESSION[$key]['count']++;
    }
    
    // Log the export to database
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS export_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                username VARCHAR(255),
                export_type VARCHAR(100),
                record_count INT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_exported_at (exported_at),
                INDEX idx_export_type (export_type)
            )
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO export_logs (user_id, username, export_type, record_count, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_SESSION['username'] ?? 'unknown',
            $exportType,
            $recordCount,
            $ip,
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500)
        ]);
        
        security_log('export_performed', [
            'export_type' => $exportType,
            'record_count' => $recordCount,
            'user_id' => $userId
        ], 'info');
        
    } catch (PDOException $e) {
        error_log("Export logging failed: " . $e->getMessage());
    }
    
    return true;
}
