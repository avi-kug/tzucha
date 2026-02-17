<?php
/**
 * System Monitoring Dashboard - Real-time system health monitoring
 */
require_once '../config/auth.php';

auth_require_admin();
$title = 'ניטור מערכת';
require_once __DIR__ . '/../templates/header.php';
?>

<style>
.metric-card {
    transition: all 0.3s ease;
}
.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.status-ok { color: #28a745; }
.status-warning { color: #ffc107; }
.status-critical { color: #dc3545; }
.metric-value {
    font-size: 2rem;
    font-weight: bold;
}
</style>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>🖥️ ניטור מערכת בזמן אמת</h2>
                <div>
                    <span class="badge bg-success" id="statusBadge">פעיל</span>
                    <button class="btn btn-sm btn-primary" onclick="refreshAll()">
                        <i class="bi bi-arrow-clockwise"></i> רענן
                    </button>
                </div>
            </div>
            <small class="text-muted">עודכן: <span id="lastUpdate">-</span></small>
        </div>
    </div>

    <!-- Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📊 סטטוס כללי</h5>
                    <div class="row" id="healthChecks">
                        <div class="col-12 text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">טוען...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <!-- Database -->
        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-database fs-1 text-primary"></i>
                    <h6 class="card-title mt-3">מסד נתונים</h6>
                    <div class="metric-value" id="dbStatus">-</div>
                    <small class="text-muted" id="dbDetails">בודק...</small>
                </div>
            </div>
        </div>

        <!-- Disk Space -->
        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-hdd fs-1 text-success"></i>
                    <h6 class="card-title mt-3">מקום בדיסק</h6>
                    <div class="metric-value" id="diskSpace">-</div>
                    <small class="text-muted" id="diskDetails">בודק...</small>
                </div>
            </div>
        </div>

        <!-- Memory -->
        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-memory fs-1 text-info"></i>
                    <h6 class="card-title mt-3">זיכרון</h6>
                    <div class="metric-value" id="memoryUsage">-</div>
                    <small class="text-muted" id="memoryDetails">בודק...</small>
                </div>
            </div>
        </div>

        <!-- Activity -->
        <div class="col-md-3 mb-3">
            <div class="card metric-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-activity fs-1 text-warning"></i>
                    <h6 class="card-title mt-3">פעילות</h6>
                    <div class="metric-value" id="activityCount">-</div>
                    <small class="text-muted">כניסות בשעה האחרונה</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Security Events -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">🔐 אירועי אבטחה אחרונים</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>זמן</th>
                                    <th>אירוע</th>
                                    <th>משתמש</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody id="recentEvents">
                                <tr>
                                    <td colspan="4" class="text-center">טוען...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="security_logs.php" class="btn btn-sm btn-primary">צפייה מלאה</a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">💻 מידע מערכת</h5>
                    <table class="table table-sm">
                        <tr>
                            <td>PHP Version:</td>
                            <td><strong><?php echo PHP_VERSION; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Server Software:</td>
                            <td><strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Document Root:</td>
                            <td><small><?php echo $_SERVER['DOCUMENT_ROOT']; ?></small></td>
                        </tr>
                        <tr>
                            <td>Max Upload Size:</td>
                            <td><strong><?php echo ini_get('upload_max_filesize'); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Memory Limit:</td>
                            <td><strong><?php echo ini_get('memory_limit'); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📈 סטטיסטיקות</h5>
                    <div id="systemStats">טוען...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshAll() {
    loadHealthCheck();
    loadRecentEvents();
    updateTimestamp();
}

function updateTimestamp() {
    const now = new Date().toLocaleString('he-IL');
    $('#lastUpdate').text(now);
}

function loadHealthCheck() {
    $.ajax({
        url: 'health.php',
        method: 'GET',
        success: function(health) {
            if (health.status === 'healthy') {
                $('#statusBadge').removeClass('bg-warning bg-danger').addClass('bg-success').text('תקין');
            } else if (health.status === 'degraded') {
                $('#statusBadge').removeClass('bg-success bg-danger').addClass('bg-warning').text('אזהרה');
            } else {
                $('#statusBadge').removeClass('bg-success bg-warning').addClass('bg-danger').text('שגיאה');
            }

            // Render checks
            let html = '';
            $.each(health.checks, function(name, check) {
                const icon = check.status === 'ok' ? '✅' : check.status === 'warning' ? '⚠️' : '❌';
                const statusClass = check.status === 'ok' ? 'status-ok' : check.status === 'warning' ? 'status-warning' : 'status-critical';
                
                html += `
                    <div class="col-md-4 mb-2">
                        <div class="d-flex align-items-center">
                            <span class="me-2">${icon}</span>
                            <div>
                                <strong>${name}</strong><br>
                                <small class="${statusClass}">${check.message}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#healthChecks').html(html);

            // Update metrics
            if (health.checks.database) {
                $('#dbStatus').html(health.checks.database.status === 'ok' ? '✅' : '❌');
                $('#dbDetails').text(health.checks.database.message);
            }

            if (health.checks.disk_space) {
                $('#diskSpace').text(health.checks.disk_space.free_gb + 'GB');
                $('#diskDetails').text(health.checks.disk_space.percent_free + '% פנוי');
            }

            if (health.checks.memory) {
                $('#memoryUsage').text(health.checks.memory.usage_mb + 'MB');
                $('#memoryDetails').text(health.checks.memory.percent_used + '% בשימוש');
            }

            if (health.checks.activity) {
                $('#activityCount').text(health.checks.activity.count);
            }
        },
        error: function() {
            $('#statusBadge').removeClass('bg-success bg-warning').addClass('bg-danger').text('לא זמין');
            $('#healthChecks').html('<div class="col-12 text-center text-danger">שגיאה בטעינת נתונים</div>');
        }
    });
}

function loadRecentEvents() {
    $.ajax({
        url: 'security_logs_api.php',
        method: 'GET',
        success: function(response) {
            if (response.success && response.logs) {
                const recentLogs = response.logs.slice(0, 5);
                let html = '';
                
                if (recentLogs.length === 0) {
                    html = '<tr><td colspan="4" class="text-center">אין אירועים</td></tr>';
                } else {
                    recentLogs.forEach(log => {
                        html += `
                            <tr>
                                <td style="white-space: nowrap">${log.timestamp}</td>
                                <td><code>${log.action}</code></td>
                                <td>${log.username || '<span class="text-muted">אורח</span>'}</td>
                                <td><small>${log.ip_address}</small></td>
                            </tr>
                        `;
                    });
                }
                
                $('#recentEvents').html(html);
            }
        },
        error: function() {
            $('#recentEvents').html('<tr><td colspan="4" class="text-center text-danger">שגיאה בטעינה</td></tr>');
        }
    });
}

// Auto-refresh every 30 seconds
setInterval(refreshAll, 30000);

// Load on page ready
$(document).ready(function() {
    refreshAll();
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
