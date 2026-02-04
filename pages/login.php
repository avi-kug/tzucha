<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once '../config/mailer.php';
require_once '../config/auth.php';
$navItems = require __DIR__ . '/../config/nav.php';

if (auth_is_logged_in()) {
    header('Location: /tzucha/pages/index.php');
    exit;
}

$errors = [];
$step = $_SESSION['login_step'] ?? 'credentials';

function resolve_geo_from_ip($ip) {
    if ($ip === 'unknown' || $ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
        return ['city' => '', 'country' => ''];
    }
    $url = 'https://ipapi.co/' . urlencode($ip) . '/json/';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 2
        ]
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) {
        return ['city' => '', 'country' => ''];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['city' => '', 'country' => ''];
    }
    return [
        'city' => $data['city'] ?? '',
        'country' => $data['country_name'] ?? ($data['country'] ?? '')
    ];
}

if ($step === 'otp' && empty($_SESSION['pending_user_id'])) {
    $step = 'credentials';
    $_SESSION['login_step'] = 'credentials';
}

// Ensure tables exist
$tablesReady = false;
try {
    $usersTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    $permsTable = $pdo->query("SHOW TABLES LIKE 'user_permissions'")->fetchColumn();
    $otpTable = $pdo->query("SHOW TABLES LIKE 'login_otps'")->fetchColumn();
    $tablesReady = ($usersTable && $permsTable && $otpTable);
} catch (Exception $e) {
    $tablesReady = false;
}

// Bootstrap admin if no users
$userCount = 0;
if ($tablesReady) {
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}
if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate()) {
    $errors[] = 'פג תוקף הטופס, נסה שוב.';
} elseif ($tablesReady && $userCount === 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bootstrap') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $policyError = '';
    if ($username === '' || $email === '' || $password === '') {
        $errors[] = 'יש למלא את כל השדות.';
    } elseif (!password_policy_validate($password, $policyError)) {
        $errors[] = $policyError;
    } else {
        $hash = hash_password($password);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, is_active, is_admin, created_at) VALUES (?, ?, ?, ?, 1, 1, NOW())');
        $stmt->execute([$username, $email, $hash, 'admin']);
        $userId = (int)$pdo->lastInsertId();
        $permStmt = $pdo->prepare('INSERT INTO user_permissions (user_id, permission_key) VALUES (?, ?)');
        foreach ($navItems as $item) {
            $permStmt->execute([$userId, $item['key']]);
        }
        $step = 'credentials';
        $_SESSION['login_step'] = 'credentials';
    }
}

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login' && csrf_validate()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limit: 5 attempts per 10 minutes per IP
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip = trim(explode(',', $ip)[0]);
    $limitWindowMinutes = 10;
    $limitMax = 5;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= (NOW() - INTERVAL ? MINUTE)');
    $stmt->execute([$ip, $limitWindowMinutes]);
    $attempts = (int)$stmt->fetchColumn();
    if ($attempts >= $limitMax) {
        $errors[] = 'יותר מדי ניסיונות כניסה. נסה שוב בעוד מספר דקות.';
        unset($_SESSION['pending_user_id'], $_SESSION['pending_username']);
        $_SESSION['login_step'] = 'credentials';
        $step = 'credentials';
    } else {
        $geo = resolve_geo_from_ip($ip);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'שם משתמש או סיסמה לא נכונים.';
            $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, success, geo_city, geo_country) VALUES (?, ?, NOW(), 0, ?, ?)')
                ->execute([$username, $ip, $geo['city'], $geo['country']]);
        } elseif ((int)$user['is_active'] !== 1) {
            $errors[] = 'משתמש לא פעיל.';
            $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, success, geo_city, geo_country) VALUES (?, ?, NOW(), 0, ?, ?)')
                ->execute([$username, $ip, $geo['city'], $geo['country']]);
        } elseif (empty($user['email'])) {
            $errors[] = 'אין כתובת מייל למשתמש זה.';
            $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, success, geo_city, geo_country) VALUES (?, ?, NOW(), 0, ?, ?)')
                ->execute([$username, $ip, $geo['city'], $geo['country']]);
        } else {
        $code = (string)random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 10 * 60);

        $pdo->prepare('DELETE FROM login_otps WHERE user_id = ?')->execute([$user['id']]);
        $pdo->prepare('INSERT INTO login_otps (user_id, otp_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([$user['id'], $hash, $expiresAt]);

        $subject = 'קוד חד פעמי לכניסה';
        $body = "קוד הכניסה שלך: {$code}\nהקוד תקף ל-10 דקות.";
        if (!send_mail($user['email'], $subject, $body)) {
            $errors[] = 'שליחת הקוד נכשלה. בדוק הגדרות מייל.';
        } else {
            $_SESSION['pending_user_id'] = $user['id'];
            $_SESSION['pending_username'] = $user['username'];
            $_SESSION['login_step'] = 'otp';
            $step = 'otp';
            $pdo->prepare('DELETE FROM login_attempts WHERE username = ? AND ip_address = ?')
                ->execute([$username, $ip]);
        }
        }
    }
}

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify' && csrf_validate()) {
    $code = trim($_POST['otp'] ?? '');
    $userId = $_SESSION['pending_user_id'] ?? null;
    if (!$userId || $code === '') {
        $errors[] = 'קוד לא תקין.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM login_otps WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otpRow || strtotime($otpRow['expires_at']) < time() || !password_verify($code, $otpRow['otp_hash'])) {
            $errors[] = 'קוד שגוי או שפג תוקף.';
        } else {
            $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $errors[] = 'משתמש לא נמצא.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (int)$user['is_admin'] === 1;
                $_SESSION['role'] = $user['role'] ?? ((int)$user['is_admin'] === 1 ? 'admin' : 'viewer');
                $_SESSION['last_activity'] = time();
                auth_load_permissions($pdo, $user['id']);
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $ip = trim(explode(',', $ip)[0]);
                $geo = resolve_geo_from_ip($ip);
                $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, success, geo_city, geo_country) VALUES (?, ?, NOW(), 1, ?, ?)')
                    ->execute([$user['username'], $ip, $geo['city'], $geo['country']]);
                $pdo->prepare('DELETE FROM login_otps WHERE user_id = ?')->execute([$user['id']]);
                unset($_SESSION['pending_user_id'], $_SESSION['pending_username'], $_SESSION['login_step']);
                $redirect = $_SESSION['redirect_after_login'] ?? '/tzucha/pages/index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'back_to_login' && csrf_validate()) {
    unset($_SESSION['pending_user_id'], $_SESSION['pending_username']);
    $_SESSION['login_step'] = 'credentials';
    $step = 'credentials';
}

$showBootstrap = ($tablesReady && $userCount === 0);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התחברות</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:480px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">התחברות</h4>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$tablesReady): ?>
                <div class="alert alert-warning">
                    טבלאות משתמשים לא קיימות. יש להריץ את הקובץ
                    <strong>sql/create_users_tables.sql</strong>
                    במסד הנתונים, ואז לרענן את הדף.
                </div>
            <?php else: ?>

            <?php if ($showBootstrap): ?>
                <div class="alert alert-info">אין משתמשים במערכת. צור משתמש מנהל ראשון.</div>
                <form method="post">
                    <input type="hidden" name="action" value="bootstrap">
                    <?php echo csrf_input(); ?>
                    <div class="mb-3">
                        <label class="form-label">שם משתמש</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">מייל</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">סיסמה</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">צור משתמש מנהל</button>
                </form>
            <?php else: ?>
                <?php if ($step === 'otp'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="verify">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label">קוד חד פעמי</label>
                            <input type="text" name="otp" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">אימות</button>
                    </form>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="action" value="back_to_login">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-outline-secondary w-100">חזרה להזדהות</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="login">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label">שם משתמש</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">סיסמה</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">המשך</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
