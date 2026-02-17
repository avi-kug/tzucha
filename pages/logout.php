<?php
session_start();
require_once '../config/auth.php';
require_once '../config/db.php';

// Log logout before destroying session
if (!empty($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip = trim(explode(',', $ip)[0]);
    
    // Mark manual logout in database
    try {
        $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, success, is_manual_logout) VALUES (?, ?, NOW(), 0, 1)')
            ->execute([$username, $ip]);
        security_log('LOGOUT', ['username' => $username, 'type' => 'manual']);
    } catch (Exception $e) {
        security_log('LOGOUT', ['username' => $username, 'type' => 'manual', 'error' => $e->getMessage()]);
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: /tzucha/pages/login.php');
exit;
