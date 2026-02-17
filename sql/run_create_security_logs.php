<?php
/**
 * Run: Create security_logs table
 */
require_once __DIR__ . '/../config/db.php';

$sql = file_get_contents(__DIR__ . '/create_security_logs.sql');

try {
    $pdo->exec($sql);
    echo "✅ SUCCESS: security_logs table created!\n";
    
    // Test insert
    $stmt = $pdo->prepare('
        INSERT INTO security_logs 
        (timestamp, ip_address, user_agent, user_id, username, action, details, severity) 
        VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        '127.0.0.1',
        'Test User Agent',
        null,
        'system',
        'SYSTEM_INIT',
        '{"message": "Security logging system initialized"}',
        'info'
    ]);
    
    echo "✅ Test log entry created!\n";
    
    // Count logs
    $count = $pdo->query('SELECT COUNT(*) FROM security_logs')->fetchColumn();
    echo "📊 Total logs in database: {$count}\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
