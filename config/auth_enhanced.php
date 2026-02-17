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
