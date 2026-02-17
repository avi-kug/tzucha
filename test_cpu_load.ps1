# =========================================
# בדיקת עומס CPU ו-RAM של XAMPP
# =========================================

Write-Host "🔍 בודק ביצועי XAMPP..." -ForegroundColor Cyan
Write-Host ""

# 1. בדיקת תהליכי Apache ו-MySQL
Write-Host "📊 תהליכים פעילים:" -ForegroundColor Yellow
Get-Process | Where-Object {$_.ProcessName -match "httpd|mysqld|apache|mysql"} | 
    Format-Table ProcessName, 
    @{Name="CPU(%)"; Expression={[Math]::Round($_.CPU, 2)}},
    @{Name="Memory(MB)"; Expression={[Math]::Round($_.WorkingSet64/1MB, 2)}},
    Id -AutoSize

# 2. שימוש כולל ב-CPU
Write-Host ""
Write-Host "💻 שימוש ב-CPU:" -ForegroundColor Yellow
$cpu = Get-Counter '\Processor(_Total)\% Processor Time' -SampleInterval 1 -MaxSamples 3
$avgCpu = ($cpu.CounterSamples | Measure-Object -Property CookedValue -Average).Average
Write-Host ("ממוצע: {0:N2}%" -f $avgCpu) -ForegroundColor $(if($avgCpu -lt 50){"Green"}elseif($avgCpu -lt 80){"Yellow"}else{"Red"})

# 3. שימוש בזיכרון
Write-Host ""
Write-Host "💾 שימוש ב-RAM:" -ForegroundColor Yellow
$os = Get-CimInstance Win32_OperatingSystem
$totalRAM = [Math]::Round($os.TotalVisibleMemorySize/1MB, 2)
$freeRAM = [Math]::Round($os.FreePhysicalMemory/1MB, 2)
$usedRAM = $totalRAM - $freeRAM
$percentUsed = [Math]::Round(($usedRAM / $totalRAM) * 100, 2)

Write-Host "סה`"כ RAM: $totalRAM GB" -ForegroundColor White
Write-Host "בשימוש: $usedRAM GB ($percentUsed%)" -ForegroundColor $(if($percentUsed -lt 70){"Green"}elseif($percentUsed -lt 85){"Yellow"}else{"Red"})
Write-Host "פנוי: $freeRAM GB" -ForegroundColor Green

# 4. בדיקת חיבורים ל-MySQL (אם פתוח)
Write-Host ""
Write-Host "🔌 נסיון בדיקת חיבורים MySQL..." -ForegroundColor Yellow
try {
    $mysqlConnections = & "C:\xampp\mysql\bin\mysql.exe" -u root -pAk8518180 -e "SHOW STATUS LIKE 'Threads_connected';" 2>$null
    if ($mysqlConnections) {
        Write-Host $mysqlConnections -ForegroundColor Green
    }
} catch {
    Write-Host "לא ניתן להתחבר ל-MySQL (נסה להריץ את XAMPP)" -ForegroundColor Red
}

# 5. המלצות
Write-Host ""
Write-Host "📋 המלצות:" -ForegroundColor Cyan
if ($avgCpu -lt 50) {
    Write-Host "✅ CPU: מעולה - המערכת פנויה" -ForegroundColor Green
} elseif ($avgCpu -lt 80) {
    Write-Host "⚠️  CPU: עומס בינוני - עדיין תקין" -ForegroundColor Yellow
} else {
    Write-Host "❌ CPU: עומס גבוה - בדוק תהליכים כבדים" -ForegroundColor Red
}

if ($percentUsed -lt 70) {
    Write-Host "✅ RAM: מעולה - יש זיכרון פנוי" -ForegroundColor Green
} elseif ($percentUsed -lt 85) {
    Write-Host "⚠️  RAM: עומס בינוני - שקול להגדיל זיכרון" -ForegroundColor Yellow
} else {
    Write-Host "❌ RAM: עומס גבוה - סגור יישומים מיותרים" -ForegroundColor Red
}

Write-Host ""
Write-Host "🌐 כדי לבדוק ביצועים בדפדפן:" -ForegroundColor Cyan
Write-Host "   1. פתח: http://localhost/tzucha/pages/performance_test.php" -ForegroundColor White
Write-Host "   2. לחץ F12 בדפדפן → Network → רענן דף" -ForegroundColor White
Write-Host "   3. בדוק זמן טעינה (צריך להיות מתחת ל-1 שנייה)" -ForegroundColor White
Write-Host ""
Write-Host "📊 לבדיקת MySQL:" -ForegroundColor Cyan
Write-Host "   mysql -u root -pAk8518180 tzucha < sql/check_mysql_performance.sql" -ForegroundColor White
Write-Host ""
