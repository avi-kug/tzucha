<?php
// honor_clothing_api.php
// Fetches data from Kavod.org.il API with auto-login + LOCAL CACHE

header('Content-Type: application/json; charset=utf-8');

// --- Load .env ---
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

// --- CONFIG ---
$kavodApiUrl   = 'https://www.kavod.org.il/api/members';
$kavodLoginUrl = 'https://www.kavod.org.il/api/authenticate';
$kavodUser     = getenv('KAVOD_USER');
$kavodPass     = getenv('KAVOD_PASS');
$storageDir    = dirname(__DIR__) . '/storage';
$cookieFile    = $storageDir . '/kavod_cookie.txt';
$cacheFile     = $storageDir . '/kavod_cache.json';
$cacheMaxAge   = 3600; // Cache for 1 hour

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0700, true);
}

if (!$kavodUser || !$kavodPass) {
    http_response_code(500);
    echo json_encode(['error' => "\xD7\x97\xD7\xA1\xD7\xA8\xD7\x99\xD7\x9D \xD7\xA4\xD7\xA8\xD7\x98\xD7\x99 \xD7\x94\xD7\xAA\xD7\x97\xD7\x91\xD7\xA8\xD7\x95\xD7\xAA \xD7\x9C-Kavod"]);
    exit;
}

// Columns to exclude (too heavy, not needed for display)
$hiddenColumns = ['json_draft', 'previous_sales_orders'];

// Column name translations (English to Hebrew)
$columnTranslations = [
    'id' => 'מזהה',
    'phone_id' => 'מזהה טלפון',
    'husband_tz' => 'ת״ז בעל',
    'wife_tz' => 'ת״ז אישה',
    'husband_first' => 'שם פרטי בעל',
    'wife_first' => 'שם פרטי אישה',
    'last_name' => 'שם משפחה',
    'street' => 'רחוב',
    'neighborhood' => 'שכונה',
    'house_number' => 'מספר בית',
    'apartment_number' => 'מספר דירה',
    'city' => 'עיר',
    'husband_phone' => 'טלפון בעל',
    'wife_phone' => 'טלפון אישה',
    'phone' => 'טלפון ראשי',
    'email' => 'אימייל',
    'number_of_childern' => 'מספר ילדים',
    'number_of_married_childern' => 'ילדים נשואים',
    'organization_id' => 'מזהה ארגון',
    'branch_id' => 'מזהה סניף',
    'created_by_user_id' => 'נוצר ע״י',
    'status_id' => 'מזהה סטטוס',
    'husband_work' => 'עיסוק הבעל',
    'income_per_person' => 'הכנסה לנפש',
    'marital_status' => 'מצב משפחתי',
    'hat_style' => 'סגנון כובע',
    'suite_style' => 'סגנון חליפה',
    'comments' => 'הערות',
    'phone_spam' => 'טלפון ספאם',
    'husband_phone_spam' => 'טלפון בעל ספאם',
    'wife_phone_spam' => 'טלפון אישה ספאם',
    'correct_details' => 'פרטים מאומתים',
    'acknowledge' => 'אישור',
    'waived' => 'ויתור',
    'last_form_login' => 'כניסה אחרונה',
    'update_member_percent' => 'אחוז עדכון',
    'created' => 'תאריך יצירה',
    'male0to2credit' => 'קרדיט בנים 0–2',
    'male2to13credit' => 'קרדיט בנים 2–13',
    'female0to2credit' => 'קרדיט בנות 0–2',
    'female2to16credit' => 'קרדיט בנות 2–16',
    'male13_and_up_credit' => 'קרדיט בנים 13+',
    'female16_and_up_credit' => 'קרדיט בנות 16+',
    'ladies_and_female_above_15_credit' => 'קרדיט נשים 15+',
    'toys_credit' => 'קרדיט צעצועים',
    'foreign_id' => 'מזהה חיצוני',
    'import_batch' => 'אצוות ייבוא',
    'last_form_update' => 'עדכון טופס אחרון',
    'young_ladies_credit' => 'קרדיט נערות',
    'blocked' => 'חסום',
    'blocked_at' => 'תאריך חסימה',
    'block_reason' => 'סיבת חסימה',
    'total_balance' => 'יתרה כוללת',
    'total_paid' => 'סך שולם',
    'overall_update_status' => 'סטטוס עדכון',
    'status_name' => 'שם סטטוס',
    'status_color' => 'צבע סטטוס',
    'organization_name' => 'שם ארגון',
    'branch_name' => 'שם סניף',
    'orders_count' => 'מספר הזמנות',
    'total_previous_orders' => 'סה״כ הזמנות קודמות',
    'balance' => 'יתרה'
];

// ?refresh=1 forces re-fetch from Kavod
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// ============================================================
// CACHE: If cached data exists and is fresh, return it instantly
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
// Login + Fetch functions
// ============================================================
function kavod_login(string $loginUrl, string $user, string $pass, string $cookieFile): bool {
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['username' => $user, 'password' => $pass]),
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode >= 200 && $httpCode < 300);
}

function kavod_fetch(string $apiUrl, string $cookieFile): array {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

// ============================================================
// Main: try fetch -> if fails, login -> fetch again
// ============================================================
$result = kavod_fetch($kavodApiUrl, $cookieFile);

if ($result['httpCode'] !== 200) {
    if (!kavod_login($kavodLoginUrl, $kavodUser, $kavodPass, $cookieFile)) {
        http_response_code(401);
        echo json_encode(['error' => "\xD7\x94\xD7\x94\xD7\xAA\xD7\x97\xD7\x91\xD7\xA8\xD7\x95\xD7\xAA \xD7\x9C-Kavod \xD7\xA0\xD7\x9B\xD7\xA9\xD7\x9C\xD7\x94"]);
        exit;
    }
    $result = kavod_fetch($kavodApiUrl, $cookieFile);
}

if ($result['httpCode'] !== 200 || !$result['response']) {
    http_response_code(502);
    echo json_encode(['error' => "\xD7\xA9\xD7\x92\xD7\x99\xD7\x90\xD7\x94 \xD7\x91\xD7\xA9\xD7\x9C\xD7\x99\xD7\xA4\xD7\xAA \xD7\xA0\xD7\xAA\xD7\x95\xD7\xA0\xD7\x99\xD7\x9D \xD7\x9E-Kavod", 'httpCode' => $result['httpCode']]);
    exit;
}

$data = json_decode($result['response'], true);
if (!isset($data['headers'], $data['rows'])) {
    http_response_code(500);
    echo json_encode(['error' => "\xD7\x9E\xD7\x91\xD7\xA0\xD7\x94 \xD7\xAA\xD7\xA9\xD7\x95\xD7\x91\xD7\x94 \xD7\x9C\xD7\x90 \xD7\xAA\xD7\xA7\xD7\x99\xD7\x9F"]);
    exit;
}

// Filter out heavy columns
$headers = $data['headers'];
$keepIndexes = [];
$cleanHeaders = [];
foreach ($headers as $i => $h) {
    if (!in_array($h, $hiddenColumns)) {
        $keepIndexes[] = $i;
        // Translate column name to Hebrew if translation exists
        $translatedName = $columnTranslations[$h] ?? $h;
        $cleanHeaders[] = $translatedName;
    }
}

$records = [];
foreach ($data['rows'] as $row) {
    $clean = [];
    foreach ($keepIndexes as $idx => $i) {
        // Use translated header name
        $clean[$cleanHeaders[$idx]] = $row[$i] ?? null;
    }
    $records[] = $clean;
}

$output = json_encode([
    'data'      => $records,
    'columns'   => $cleanHeaders,
    'cached_at' => date('Y-m-d H:i:s'),
    'count'     => count($records),
]);

// Save to cache
file_put_contents($cacheFile, $output);

echo $output;
