<?php
session_start();
require_once '../config/auth.php';

// Log logout before destroying session
if (!empty($_SESSION['username'])) {
    security_log('LOGOUT', ['username' => $_SESSION['username']]);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: /tzucha/pages/login.php');
exit;
