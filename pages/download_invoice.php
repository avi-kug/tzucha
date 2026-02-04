<?php
require_once '../config/db.php';
require_once '../config/auth.php';
$navItems = require __DIR__ . '/../config/nav.php';

auth_guard_page($pdo, $navItems);
if (!auth_has_permission('expenses')) {
    http_response_code(403);
    exit('אין הרשאה');
}

$file = $_GET['file'] ?? '';
$file = basename((string)$file);
if ($file === '') {
    http_response_code(400);
    exit('קובץ לא תקין');
}

$allowedExt = ['jpg','jpeg','png','pdf'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    exit('סוג קובץ לא נתמך');
}

$root = dirname(__DIR__, 2); // c:\xampp\htdocs
$base = dirname($root) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tzucha' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
$path = $base . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit('קובץ לא נמצא');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $file . '"');
readfile($path);
exit;
