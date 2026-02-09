<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
$navItems = require __DIR__ . '/../config/nav.php';
auth_guard_page($pdo, $navItems);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>מערכת Tzucha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <?php $cssV = @filemtime(__DIR__ . '/../assets/css/style.css') ?: '20260128'; ?>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo $cssV; ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="main-container">
        <div class="sidebar sidebar-fixed p-3" style="display:flex; flex-direction:column; height:100vh; position:relative;">
            <h5>ניווט</h5>
            <?php if (auth_is_logged_in()): ?>
                <div class="mb-2 text-muted" style="font-size:0.95rem;">
                    משתמש: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            <?php endif; ?>
            <?php $currentPage = basename($_SERVER['SCRIPT_NAME']); ?>
            <ul class="nav flex-column" style="flex:1 1 auto;">
                <?php foreach ($navItems as $item): ?>
                    <?php if (auth_has_permission($item['key'])): ?>
                        <?php $isActive = (basename($item['url']) === $currentPage); ?>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php if (auth_is_logged_in()): ?>
                <div style="position:absolute; left:0; right:0; top:50%; transform:translateY(-50%); text-align:center;">
                    <a class="nav-link text-danger fw-bold" style="font-size:1.05rem; display:inline-block;" href="/tzucha/pages/logout.php">התנתקות</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="main-wrapper">
            <div class="content-area">