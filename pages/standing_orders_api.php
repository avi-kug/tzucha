<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../config/auth.php';
auth_require_login($pdo);

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 60, 60);
    check_request_size();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'history') {
    $personId = (int)($_GET['person_id'] ?? 0);
    if (!$personId) {
        echo json_encode(['success' => false, 'error' => 'Missing person_id']);
        exit;
    }

    // כח הרבים – grouped by month
    $koachStmt = $pdo->prepare("
        SELECT DATE_FORMAT(donation_date, '%Y-%m') AS month_key,
               DATE_FORMAT(donation_date, '%m/%Y') AS month_label,
               SUM(amount) AS total,
               COUNT(*) AS cnt
        FROM standing_orders_koach
        WHERE person_id = ?
        GROUP BY month_key
        ORDER BY month_key DESC
    ");
    $koachStmt->execute([$personId]);
    $koachMonths = $koachStmt->fetchAll(PDO::FETCH_ASSOC);

    $koachTotalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS grand_total FROM standing_orders_koach WHERE person_id = ?");
    $koachTotalStmt->execute([$personId]);
    $koachGrand = $koachTotalStmt->fetchColumn();

    // אחים לחסד – grouped by month
    $achimStmt = $pdo->prepare("
        SELECT DATE_FORMAT(donation_date, '%Y-%m') AS month_key,
               DATE_FORMAT(donation_date, '%m/%Y') AS month_label,
               SUM(amount) AS total,
               COUNT(*) AS cnt
        FROM standing_orders_achim
        WHERE person_id = ?
        GROUP BY month_key
        ORDER BY month_key DESC
    ");
    $achimStmt->execute([$personId]);
    $achimMonths = $achimStmt->fetchAll(PDO::FETCH_ASSOC);

    $achimTotalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS grand_total FROM standing_orders_achim WHERE person_id = ?");
    $achimTotalStmt->execute([$personId]);
    $achimGrand = $achimTotalStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'koach'   => ['months' => $koachMonths, 'grand_total' => (float)$koachGrand],
        'achim'   => ['months' => $achimMonths, 'grand_total' => (float)$achimGrand],
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
