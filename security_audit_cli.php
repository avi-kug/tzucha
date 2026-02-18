<?php

echo "=================================================\n";
echo "   LOCAL PROJECT SECURITY AUDIT\n";
echo "=================================================\n\n";

function ok($msg){ echo "✔ [OK]   $msg\n"; }
function warn($msg){ echo "✘ [WARN] $msg\n"; }
function info($msg){ echo "ℹ [INFO] $msg\n"; }

$projectPath = __DIR__;

echo "1. PHP CONFIGURATION\n";
echo "-------------------------------------------------\n";

ini_get('display_errors') ? warn("display_errors ON") : ok("display_errors OFF");
ini_get('expose_php') ? warn("expose_php ON") : ok("expose_php OFF");

echo "\n2. DANGEROUS PHP FUNCTIONS\n";
echo "-------------------------------------------------\n";

$dangerous = ['exec','shell_exec','system','passthru','eval','base64_decode'];
foreach($dangerous as $func){
    if(function_exists($func)){
        warn("Function exists: $func (ensure not used improperly)");
    }
}

echo "\n3. SESSION SECURITY\n";
echo "-------------------------------------------------\n";

ini_get('session.cookie_httponly') ? ok("HttpOnly enabled") : warn("HttpOnly NOT enabled");
ini_get('session.cookie_secure') ? ok("Secure cookie enabled") : warn("Secure cookie NOT enabled");

echo "\n4. SCAN FOR SENSITIVE FILES\n";
echo "-------------------------------------------------\n";

$sensitiveFiles = ['.env','config.php','.git','backup.sql','dump.sql'];
$foundSensitive = 0;
foreach($sensitiveFiles as $file){
    if(file_exists($projectPath . '/' . $file)){
        warn("Sensitive file found: $file");
        $foundSensitive++;
    }
}
if($foundSensitive == 0) ok("No sensitive files found in root");

echo "\n5. SCAN PHP FILES FOR RISK PATTERNS\n";
echo "-------------------------------------------------\n";

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath));
$issues = 0;
$infoCount = 0;

foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
    
    // Skip vendor directory
    if(strpos($file->getPathname(), 'vendor') !== false) continue;

    $content = @file_get_contents($file);
    if($content === false) continue;

    if (preg_match("/SELECT .* \$_(GET|POST|REQUEST)/i", $content)) {
        warn("Possible SQL Injection risk in: " . $file);
        $issues++;
    }

    if (strpos($content, 'password_hash') === false && strpos($content, 'INSERT') !== false) {
        info("Check password hashing in: " . $file);
        $infoCount++;
    }

    if (preg_match("/echo\s+\$_(GET|POST|REQUEST)/i", $content)) {
        warn("Possible XSS risk in: " . $file);
        $issues++;
    }
}

echo "\nSummary: $issues potential security issues, $infoCount files to review\n";
$issues == 0 ? ok("No obvious injection patterns found") : warn("$issues potential code issues detected");

echo "\n6. FILE PERMISSIONS CHECK (Sample)\n";
echo "-------------------------------------------------\n";

$permIssues = 0;
$checked = 0;
foreach ($rii as $file) {
    if ($file->isFile() && $checked < 10) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        if ($perms > '0666') {
            warn("Open permissions: {$file} ($perms)");
            $permIssues++;
        }
        $checked++;
    }
}
if($permIssues == 0) ok("File permissions appear OK (sample checked)");

echo "\n7. GIT EXPOSURE\n";
echo "-------------------------------------------------\n";

if(is_dir($projectPath . '/.git')){
    warn(".git directory exists inside project root");
    info("Consider: .git should not be accessible via web");
} else {
    ok("No .git directory found in project root");
}

echo "\n=================================================\n";
echo "   AUDIT COMPLETE\n";
echo "=================================================\n";
?>
