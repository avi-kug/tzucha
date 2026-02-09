<?php
// סקריפט להרצת כל קבצי ה-SQL שבתיקיית sql/
$dir = __DIR__ . '/../sql';
$files = glob($dir . '/*.sql');

$host = 'localhost';
$db   = 'tzucha';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

foreach ($files as $file) {
    $sql = file_get_contents($file);
    echo "מריץ: $file... ";
    try {
        $pdo->exec($sql);
        echo "בוצע בהצלחה.<br>\n";
    } catch (PDOException $e) {
        echo "שגיאה: " . $e->getMessage() . "<br>\n";
    }
}
echo "סיום.";
