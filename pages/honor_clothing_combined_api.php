<?php
// honor_clothing_combined_api.php
// Combines local people data with Kavod API data

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../config/auth.php';

// Check authentication (for API, don't redirect - return JSON error)
if (!auth_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'לא מחובר'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!auth_has_permission('people')) {
    http_response_code(403);
    echo json_encode(['error' => 'אין הרשאה'], JSON_UNESCAPED_UNICODE);
    exit;
}

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 30, 60); // 30 requests per minute (external API)
    check_request_size();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if refresh is requested
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// If refresh requested, call Kavod API to update cache
if ($forceRefresh) {
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/honor_clothing_api.php?refresh=1';
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_COOKIE => session_name() . '=' . session_id() // Pass session
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// --- Load Kavod cache ---
$cacheFile = dirname(__DIR__) . '/storage/kavod_cache.json';
$kavodData = [];
$kavodCachedAt = '';

if (file_exists($cacheFile)) {
    $cached = file_get_contents($cacheFile);
    if ($cached) {
        $decoded = json_decode($cached, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded && isset($decoded['data'])) {
            // Index Kavod data by ID for quick lookup
            foreach ($decoded['data'] as $row) {
                if (isset($row['מזהה'])) {
                    $kavodData[$row['מזהה']] = $row;
                }
            }
            // Store when Kavod cache was created
            $kavodCachedAt = $decoded['cached_at'] ?? '';
        }
    }
}

// --- Fetch people from local database (only with phone_id) ---
try {
    $stmt = $pdo->prepare("
        SELECT 
            phone_id,
            family_name,
            first_name,
            husband_id,
            address,
            neighborhood,
            city,
            phone,
            husband_mobile,
            wife_mobile,
            updated_email
        FROM people 
        WHERE phone_id IS NOT NULL 
        AND phone_id != ''
        ORDER BY family_name, first_name
    ");
    $stmt->execute();
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine with Kavod data
    $combined = [];
    foreach ($people as $person) {
        $phoneId = $person['phone_id'] ?? '';
        $kavodInfo = [];
        
        // Only try to get Kavod data if phone_id exists
        if ($phoneId && isset($kavodData[$phoneId])) {
            $kavodInfo = $kavodData[$phoneId];
        }
        
        $combined[] = [
            'phone_id' => $phoneId,
            'family_name' => $person['family_name'] ?? '',
            'first_name' => $person['first_name'] ?? '',
            'husband_id' => $person['husband_id'] ?? '',
            'address' => $person['address'] ?? '',
            'neighborhood' => $person['neighborhood'] ?? '',
            'city' => $person['city'] ?? '',
            'phone' => $person['phone'] ?? '',
            'husband_mobile' => $person['husband_mobile'] ?? '',
            'wife_mobile' => $person['wife_mobile'] ?? '',
            'updated_email' => $person['updated_email'] ?? '',
            // From Kavod API (will be empty if no phone_id or not in cache)
            'number_of_children' => $kavodInfo['מספר ילדים'] ?? '',
            'update_status' => $kavodInfo['סטטוס עדכון'] ?? '',
            'orders_count' => $kavodInfo['מספר הזמנות'] ?? '',
            'total_previous_orders' => $kavodInfo['סה״כ הזמנות קודמות'] ?? '',
            'balance' => $kavodInfo['יתרה'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $combined,
        'count' => count($combined),
        'cached_at' => $kavodCachedAt ?: 'לא זמין'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'שגיאת מסד נתונים: ' . $e->getMessage()]);
}
