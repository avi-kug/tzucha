<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
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
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' https:; style-src 'self' https: 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https://ipapi.co; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
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

function auth_require_login($pdo) {
    if (auth_is_logged_in()) { return; }
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/tzucha/pages/index.php';
    header('Location: /tzucha/pages/login.php');
    exit;
}

function auth_require_permission($key) {
    if (!$key) { return; }
    if (auth_has_permission($key)) { return; }
    http_response_code(403);
    echo 'אין הרשאה לגישה לעמוד זה.';
    exit;
}

function auth_guard_page($pdo, $navItems) {
    auth_require_login($pdo);
    $timeout = 30 * 60;
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout) {
        session_destroy();
        header('Location: /tzucha/pages/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT username, role, is_admin, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || (int)$u['is_active'] !== 1) {
            session_destroy();
            header('Location: /tzucha/pages/login.php');
            exit;
        }
        $_SESSION['username'] = $u['username'];
        $_SESSION['role'] = $u['role'] ?? ( (int)$u['is_admin'] === 1 ? 'admin' : 'viewer');
        $_SESSION['is_admin'] = (int)$u['is_admin'] === 1;
    }
    if (!auth_is_admin() && empty(auth_permissions()) && !empty($_SESSION['user_id'])) {
        auth_load_permissions($pdo, (int)$_SESSION['user_id']);
    }
    $current = $_SERVER['PHP_SELF'] ?? '';
    $map = [];
    foreach ($navItems as $item) {
        if (!empty($item['url'])) {
            $map[basename($item['url'])] = $item['key'] ?? '';
        }
    }
    $currentBase = basename($current);
    if (isset($map[$currentBase])) {
        auth_require_permission($map[$currentBase]);
        return;
    }

    // Deny access to unlisted pages for non-admin users (except login/logout)
    $allow = ['login.php', 'logout.php'];
    if (!auth_is_admin() && !in_array($currentBase, $allow, true)) {
        if (str_contains($current, '/pages/')) {
            http_response_code(403);
            echo 'אין הרשאה לגישה לעמוד זה.';
            exit;
        }
    }
}
