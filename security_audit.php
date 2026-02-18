<?php

echo "<h1>Local Project Security Audit</h1>";

function ok($msg){ echo "<p style='color:green;'>✔ $msg</p>"; }
function warn($msg){ echo "<p style='color:red;'>✘ $msg</p>"; }
function info($msg){ echo "<p style='color:orange;'>ℹ $msg</p>"; }

$projectPath = __DIR__;

echo "<h2>1. PHP Configuration</h2>";

ini_get('display_errors') ? warn("display_errors ON") : ok("display_errors OFF");
ini_get('expose_php') ? warn("expose_php ON") : ok("expose_php OFF");

echo "<h2>2. Dangerous PHP Functions</h2>";

$dangerous = ['exec','shell_exec','system','passthru','eval','base64_decode'];
foreach($dangerous as $func){
    if(function_exists($func)){
        warn("Function exists: $func (ensure not used improperly)");
    }
}

echo "<h2>3. Session Security</h2>";

ini_get('session.cookie_httponly') ? ok("HttpOnly enabled") : warn("HttpOnly NOT enabled");
ini_get('session.cookie_secure') ? ok("Secure cookie enabled") : warn("Secure cookie NOT enabled");

echo "<h2>4. Scan for Sensitive Files</h2>";

$sensitiveFiles = ['.env','config.php','.git','backup.sql','dump.sql'];
foreach($sensitiveFiles as $file){
    if(file_exists($projectPath . '/' . $file)){
        warn("Sensitive file found: $file");
    }
}

echo "<h2>5. Scan PHP Files for Risk Patterns</h2>";

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath));
$issues = 0;

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;

    $content = file_get_contents($file);

    if (preg_match("/SELECT .* \$_(GET|POST|REQUEST)/i", $content)) {
        warn("Possible SQL Injection risk in: " . $file);
        $issues++;
    }

    if (strpos($content, 'password_hash') === false && strpos($content, 'INSERT') !== false) {
        info("Check password hashing in: " . $file);
    }

    if (preg_match("/echo\s+\$_(GET|POST|REQUEST)/i", $content)) {
        warn("Possible XSS risk in: " . $file);
        $issues++;
    }
}

$issues == 0 ? ok("No obvious injection patterns found") : warn("$issues potential code issues detected");

echo "<h2>6. File Permissions Check</h2>";

foreach ($rii as $file) {
    if ($file->isFile()) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        if ($perms > 644) {
            warn("Open permissions: {$file} ($perms)");
        }
    }
}

echo "<h2>7. Git Exposure</h2>";

if(is_dir($projectPath . '/.git')){
    warn(".git directory exists inside project root");
}

echo "<h2>Audit Complete</h2>";
?>
