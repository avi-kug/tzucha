<?php
// Load .env file (for local development)
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

// Railway compatibility: Check for DATABASE_URL first
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parse Railway's DATABASE_URL
    // Format: mysql://user:pass@host:port/database
    $url = parse_url($databaseUrl);
    
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? 3306;
    $db = ltrim($url['path'] ?? '/railway', '/');
    $user = $url['user'] ?? 'root';
    $pass = $url['pass'] ?? '';
} else {
    // Fallback to .env variables (XAMPP/local)
    $host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: 3306;
    $db = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'tzucha';
    $user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';
}

$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => true, // Connection pooling for better performance
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    error_log("Connection details: host=$host, port=$port, db=$db, user=$user");
    http_response_code(500);
    die("שגיאת חיבור למסד נתונים");
}
?>
