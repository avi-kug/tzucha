<?php
/**
 * cash_api.php
 * Fetches donation data from ipapp.org API with session-based authentication
 * Also stores data locally in cash_donations table
 * Based on the API structure: api.php?action=select_donation
 */

header('Content-Type: application/json; charset=utf-8');

// ============================================================
// Load .env & DB
// ============================================================
require_once __DIR__ . '/../config/db.php';
$envPath = dirname(__DIR__) . '/.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') continue;
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

// ============================================================
// CONFIG
// ============================================================
$ipappBaseUrl  = 'https://ipapp.org/kupot/';
$ipappApiBase  = 'https://ipapp.org/kupot/api.php';
$ipappUser     = getenv('IPAPP_USER');     // tzucha
$ipappPass     = getenv('IPAPP_PASS');     // 147258
$ipappCode     = getenv('IPAPP_CODE');     // amshinov (project/kupot code)

$storageDir    = dirname(__DIR__) . '/storage';
$cookieFile    = $storageDir . '/ipapp_session.txt';
$cacheFile     = $storageDir . '/ipapp_cache.json';
$cacheMaxAge   = 3600; // 1 hour

// Get action from request
$action = $_GET['action'] ?? 'select';

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0700, true);
}

if (!$ipappUser || !$ipappPass || !$ipappCode) {
    http_response_code(500);
    echo json_encode(['error' => 'חסרים פרטי התחברות ל-ipapp.org (user/pass/code)']);
    exit;
}

// ============================================================
// Handle different actions
// ============================================================

// DELETE action
if ($action === 'delete') {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'חסר ID למחיקה']);
        exit;
    }
    
    $deleteUrl = $ipappApiBase . '?' . http_build_query([
        'action' => 'delete',
        'table' => 'donation',
        'id' => $id
    ]);
    
    $result = ipapp_login_and_fetch($ipappBaseUrl, $deleteUrl, $ipappUser, $ipappPass, $ipappCode, $cookieFile);
    
    if ($result['httpCode'] === 200) {
        // Clear cache
        @unlink($cacheFile);
        echo json_encode(['success' => true, 'message' => 'נמחק בהצלחה']);
    } else {
        http_response_code($result['httpCode']);
        echo json_encode(['error' => 'שגיאה במחיקה', 'details' => $result['error']]);
    }
    exit;
}

// UPDATE action
if ($action === 'update') {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    $key = $_POST['key'] ?? $_GET['key'] ?? null;
    $value = $_POST['value'] ?? $_GET['value'] ?? null;
    
    if (!$id || !$key) {
        http_response_code(400);
        echo json_encode(['error' => 'חסרים פרמטרים (id, key)']);
        exit;
    }
    
    $updateUrl = $ipappApiBase . '?' . http_build_query([
        'action' => 'update',
        'table' => 'donation',
        'id' => $id,
        'key' => $key,
        'value' => $value
    ]);
    
    $result = ipapp_login_and_fetch($ipappBaseUrl, $updateUrl, $ipappUser, $ipappPass, $ipappCode, $cookieFile);
    
    if ($result['httpCode'] === 200) {
        // Clear cache
        @unlink($cacheFile);
        echo json_encode(['success' => true, 'message' => 'עודכן בהצלחה']);
    } else {
        http_response_code($result['httpCode']);
        echo json_encode(['error' => 'שגיאה בעדכון', 'details' => $result['error']]);
    }
    exit;
}

// INSERT action
if ($action === 'insert' || $action === 'add') {
    $client = $_POST['client'] ?? null;
    $project = $_POST['project'] ?? $ipappCode; // Default to current project
    $amount = $_POST['amount'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if (!$client || !$amount) {
        http_response_code(400);
        echo json_encode(['error' => 'חסרים פרמטרים (client, amount)']);
        exit;
    }
    
    // Split client into name and family (if contains space)
    $clientParts = explode(' ', $client, 2);
    $name = $clientParts[0] ?? $client;
    $family = $clientParts[1] ?? '';
    
    // Try different API endpoints for insert
    $insertParams = [
        'action' => 'insert',
        'table' => 'donation',
        'name' => $name,
        'family' => $family,
        'project' => $project,
        'amount' => $amount,
        'notes' => $notes,
    ];
    
    // Try POST request to the form submission endpoint first
    $formSubmitUrl = $ipappBaseUrl . '?view=donation&setDonation=add';
    $formData = [
        'name' => $name,
        'family' => $family,
        'amount' => $amount,
        'notes' => $notes,
        'project' => $project,
        'submit' => 'שמור'
    ];
    
    $result = ipapp_login_and_fetch($ipappBaseUrl, $formSubmitUrl, $ipappUser, $ipappPass, $ipappCode, $cookieFile, $formData);
    
    // Save debug info
    file_put_contents($storageDir . '/ipapp_insert_debug.json', json_encode([
        'attempt' => 'form POST to setDonation=add',
        'url' => $formSubmitUrl,
        'params' => $formData,
        'httpCode' => $result['httpCode'],
        'response' => substr($result['response'] ?? '', 0, 1000)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Check if form submission was successful
    // Look for actual error messages, not just the word "error" (which might be in CSS/JS)
    $hasError = (stripos($result['response'], 'שגיאה ב') !== false || 
                 stripos($result['response'], 'error:') !== false ||
                 stripos($result['response'], 'failed') !== false ||
                 stripos($result['response'], 'נכשל') !== false);
    $hasHtml = (stripos($result['response'], '<!DOCTYPE') !== false || 
                stripos($result['response'], '<html') !== false);
    $formSuccess = ($result['httpCode'] === 200 || $result['httpCode'] === 302) && 
                   !$hasError && 
                   $hasHtml;
    
    // Debug logging
    file_put_contents($storageDir . '/ipapp_insert_check.json', json_encode([
        'httpCode' => $result['httpCode'],
        'hasError' => $hasError,
        'hasHtml' => $hasHtml,
        'formSuccess' => $formSuccess,
        'responseStart' => substr($result['response'] ?? '', 0, 200)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    if ($formSuccess) {
        // Clear cache
        @unlink($cacheFile);
        echo json_encode(['success' => true, 'message' => 'נוסף בהצלחה לאתר ipapp.org']);
    } else {
        http_response_code($result['httpCode']);
        echo json_encode([
            'error' => 'שגיאה בהוספה', 
            'details' => $result['error'],
            'response' => substr($result['response'] ?? '', 0, 500),
            'debug' => 'Check ipapp_insert_debug.json for details'
        ]);
    }
    exit;
}

// SELECT action (default) - continue to existing code below
$ipappApiUrl = $ipappApiBase . '?action=select_donation';

// ?refresh=1 forces re-fetch
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// ============================================================
// CACHE: Return cached data if fresh
// ============================================================
if (!$forceRefresh && file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    if ($cacheAge < $cacheMaxAge) {
        $cached = file_get_contents($cacheFile);
        if ($cached) {
            echo $cached;
            exit;
        }
    }
}

// ============================================================
// Fetch with Session-based Authentication
// ============================================================
function ipapp_login_and_fetch($baseUrl, $apiUrl, $user, $pass, $code, $cookieFile, $postData = null) {
    $ch = curl_init();
    
    // Step 0: First visit the page to get initial cookies
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    
    curl_exec($ch);
    
    // Try different login parameter combinations
    $loginAttempts = [
        ['project' => $code, 'user' => $user, 'pass' => $pass, 'login' => 'כניסה'],
        ['project' => $code, 'user' => $user, 'pass' => $pass],
        ['kupot' => $code, 'user' => $user, 'pass' => $pass, 'login' => 'כניסה'],
        ['code' => $code, 'username' => $user, 'password' => $pass, 'login' => 'כניסה'],
    ];
    
    $loginResponse = null;
    $loginCode = 0;
    $successfulLogin = false;
    
    foreach ($loginAttempts as $params) {
        // Step 1: POST login
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: he-IL,he;q=0.9',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://ipapp.org',
                'Referer: https://ipapp.org/kupot/',
            ],
        ]);
        
        $loginResponse = curl_exec($ch);
        $loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check if NOT still on login page
        if (stripos($loginResponse, 'כניסה למערכת') === false || 
            stripos($loginResponse, 'view=donation') !== false) {
            $successfulLogin = true;
            break;
        }
    }
    
    if (!$successfulLogin) {
        curl_close($ch);
        return [
            'response' => null,
            'httpCode' => 401,
            'error' => 'Login failed - credentials rejected',
            'loginCode' => $loginCode,
            'loginResponse' => substr($loginResponse, 0, 1000)
        ];
    }
    
    // Step 2: API endpoint with session cookies
    $curlOpts = [
        CURLOPT_URL => $apiUrl,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Referer: https://ipapp.org/kupot/?view=donation',
            'X-Requested-With: XMLHttpRequest'
        ],
    ];
    
    // Use POST if data provided, otherwise GET
    if ($postData !== null) {
        $curlOpts[CURLOPT_POST] = true;
        $curlOpts[CURLOPT_POSTFIELDS] = http_build_query($postData);
        $curlOpts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
    } else {
        $curlOpts[CURLOPT_POST] = false;
        $curlOpts[CURLOPT_HTTPGET] = true;
    }
    
    curl_setopt_array($ch, $curlOpts);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error,
        'loginCode' => $loginCode
    ];
}

// ============================================================
// Fetch Data
// ============================================================
$result = ipapp_login_and_fetch($ipappBaseUrl, $ipappApiUrl, $ipappUser, $ipappPass, $ipappCode, $cookieFile);

if (!$result['response'] || $result['httpCode'] !== 200) {
    // Save debug info
    if (isset($result['loginResponse'])) {
        file_put_contents($storageDir . '/ipapp_login_debug.html', $result['loginResponse']);
    }
    
    http_response_code($result['httpCode'] === 401 ? 401 : 502);
    echo json_encode([
        'error' => $result['httpCode'] === 401 ? 'התחברות נכשלה - בדוק את פרטי הכניסה' : 'שגיאה בשליפת נתונים',
        'httpCode' => $result['httpCode'],
        'loginCode' => $result['loginCode'],
        'details' => $result['error'],
        'credentials' => [
            'project' => $ipappCode,
            'user' => $ipappUser,
            'pass' => str_repeat('*', strlen($ipappPass))
        ],
        'debug' => 'Check ipapp_login_debug.html for details'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// Parse JSON Response (format: {"data": [...]})
// ============================================================
$jsonData = json_decode($result['response'], true);

if (!$jsonData || !isset($jsonData['data']) || !is_array($jsonData['data'])) {
    file_put_contents($storageDir . '/ipapp_debug.json', $result['response']);
    http_response_code(500);
    echo json_encode([
        'error' => 'תשובה לא תקינה מה-API',
        'debug' => 'Response saved to ipapp_debug.json'
    ]);
    exit;
}

$donations = $jsonData['data'];

if (empty($donations)) {
    echo json_encode([
        'data' => [],
        'columns' => [],
        'cached_at' => date('Y-m-d H:i:s'),
        'count' => 0,
    ]);
    exit;
}

// ============================================================
// Save to Database (sync with local DB)
// ============================================================
try {
    // Clear existing data
    $pdo->exec("TRUNCATE TABLE cash_donations");
    
    // Prepare insert statement
    $insertStmt = $pdo->prepare("
        INSERT INTO cash_donations 
        (id, id_alfon, name, family, address, city, amount, notes, date, heb_date, 
         project, source, name_gabay, name_amarkal, creating_date, receipt_date, 
         receipt_generated, id_project, id_gabay, record, synced_at)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $syncCount = 0;
    foreach ($donations as $row) {
        $insertStmt->execute([
            $row['id'] ?? null,
            $row['id_alfon'] ?? null,
            $row['name'] ?? null,
            $row['family'] ?? null,
            $row['address'] ?? null,
            $row['city'] ?? null,
            $row['amount'] ?? null,
            $row['notes'] ?? null,
            $row['date'] ?? null,
            $row['heb_date'] ?? null,
            $row['project'] ?? null,
            $row['source'] ?? null,
            $row['name_gabay'] ?? null,
            $row['name_amarkal'] ?? null,
            $row['creating_date'] ?? null,
            $row['receipt_date'] ?? null,
            $row['receipt_generated'] ?? null,
            $row['id_project'] ?? null,
            $row['id_gabay'] ?? null,
            $row['record'] ?? null,
        ]);
        $syncCount++;
    }
    
    $syncMessage = "Synced $syncCount records to DB";
} catch (Exception $e) {
    $syncMessage = "DB sync failed: " . $e->getMessage();
}

// ============================================================
// Hebrew Column Names Mapping
// ============================================================
$hebrewColumns = [
    'id' => '#',
    'name' => 'שם',
    'family' => 'משפחה',
    'address' => 'כתובת',
    'city' => 'עיר',
    'amount' => 'סכום',
    'notes' => 'הערות',
    'date' => 'תאריך',
    'heb_date' => 'תאריך עברי',
    'project' => 'פרוייקט',
    'source' => 'מקור הנתון',
    'name_gabay' => 'גבאי',
    'name_amarkal' => 'אמרכל',
    'creating_date' => 'תאריך יצירה',
    'receipt_date' => 'תאריך קבלה',
    'receipt_generated' => 'קבלה נוצרה',
    'id_alfon' => 'מס\' אלפון',
    'id_project' => 'מס\' פרוייקט',
    'id_gabay' => 'מס\' גבאי',
    'record' => 'הקלטת הערות',
];

// Get columns from first record
$allKeys = array_keys($donations[0]);
$columns = array_map(function($key) use ($hebrewColumns) {
    return $hebrewColumns[$key] ?? $key;
}, $allKeys);

// Convert records to Hebrew keys for display
$recordsWithHebrewKeys = [];
foreach ($donations as $row) {
    $hebrewRow = [];
    foreach ($row as $key => $value) {
        $hebrewKey = $hebrewColumns[$key] ?? $key;
        $hebrewRow[$hebrewKey] = $value;
    }
    $recordsWithHebrewKeys[] = $hebrewRow;
}

// ============================================================
// Build Response
// ============================================================
$output = json_encode([
    'data' => $recordsWithHebrewKeys,
    'columns' => $columns,
    'cached_at' => date('Y-m-d H:i:s'),
    'count' => count($donations),
    'total_amount' => array_sum(array_column($donations, 'amount')),
    'sync_status' => $syncMessage ?? 'No sync performed',
], JSON_UNESCAPED_UNICODE);

// Save to cache
file_put_contents($cacheFile, $output);

echo $output;
