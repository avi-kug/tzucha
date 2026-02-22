<?php
/**
 * SQL Import Script for Railway
 * Run this ONCE from Railway environment only
 */

// Only allow in production
$isProduction = !empty($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') === false;
if (!$isProduction) {
    die('This script can only run in production (Railway).');
}

require_once 'config/db.php';

$sqlFile = __DIR__ . '/tzucha (2).sql';

if (!file_exists($sqlFile)) {
    die('SQL file not found: ' . $sqlFile);
}

echo "<h2>SQL Import Script</h2>";
echo "<p>Reading SQL file...</p>";

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die('Failed to read SQL file');
}

echo "<p>File size: " . strlen($sql) . " bytes</p>";
echo "<p>Parsing SQL statements...</p>";

// Split into statements
$statements = [];
$buffer = '';
$inString = false;
$stringChar = '';

for ($i = 0; $i < strlen($sql); $i++) {
    $char = $sql[$i];
    
    // Handle strings
    if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
        if (!$inString) {
            $inString = true;
            $stringChar = $char;
        } elseif ($char === $stringChar) {
            $inString = false;
        }
    }
    
    // Handle statement delimiter
    if (!$inString && $char === ';') {
        $stmt = trim($buffer);
        if (!empty($stmt) && !preg_match('/^(--|#)/', $stmt)) {
            $statements[] = $stmt;
        }
        $buffer = '';
        continue;
    }
    
    $buffer .= $char;
}

// Add last statement if any
$stmt = trim($buffer);
if (!empty($stmt) && !preg_match('/^(--|#)/', $stmt)) {
    $statements[] = $stmt;
}

$totalStatements = count($statements);
echo "<p>Found {$totalStatements} SQL statements</p>";
echo "<p>Executing...</p>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    
    $pdo->beginTransaction();
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $i => $statement) {
        if (($i + 1) % 100 === 0) {
            echo "<p>Progress: " . ($i + 1) . " / {$totalStatements}</p>";
            flush();
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            $errors++;
            echo "<p style='color:red'>Error at statement " . ($i + 1) . ": " . $e->getMessage() . "</p>";
            
            // Continue on non-critical errors
            if ($errors > 50) {
                throw new Exception("Too many errors, aborting.");
            }
        }
    }
    
    $pdo->commit();
    
    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    
    echo "<h3 style='color:green'>✓ Import completed!</h3>";
    echo "<p>Executed: {$executed} statements</p>";
    echo "<p>Errors: {$errors}</p>";
    
    echo "<hr>";
    echo "<p><strong>IMPORTANT:</strong> Delete this file and the SQL file immediately!</p>";
    echo "<pre>
git rm import_sql_railway.php \"tzucha (2).sql\"
git commit -m \"Remove SQL import files after successful import\"
git push origin main
</pre>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red'>✗ Import failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
