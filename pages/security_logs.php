<?php
/**
 * Security Logs Viewer - Admin Only
 */
require_once '../config/auth.php';

auth_require_admin();
$title = 'יומן אבטחה';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>📋 יומן אירועי אבטחה</h2>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="refreshLogs()">
                        <i class="bi bi-arrow-clockwise"></i> רענן
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="clearOldLogs()">
                        <i class="bi bi-trash"></i> מחק ישנים (30+ ימים)
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">סוג אירוע</label>
                            <select class="form-select form-select-sm" id="filterAction">
                                <option value="">הכל</option>
                                <option value="LOGIN_SUCCESS">כניסה מוצלחת</option>
                                <option value="LOGIN_FAILED">כניסה נכשלה</option>
                                <option value="LOGIN_RATE_LIMIT">הגבלת קצב</option>
                                <option value="LOGOUT">התנתקות</option>
                                <option value="EXPORT">ייצוא נתונים</option>
                                <option value="DELETE">מחיקה</option>
                                <option value="UNAUTHORIZED">גישה לא מורשית</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">רמת חומרה</label>
                            <select class="form-select form-select-sm" id="filterSeverity">
                                <option value="">הכל</option>
                                <option value="info">מידע</option>
                                <option value="warning">אזהרה</option>
                                <option value="critical">קריטי</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">תאריך</label>
                            <input type="date" class="form-control form-control-sm" id="filterDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">משתמש</label>
                            <input type="text" class="form-control form-control-sm" id="filterUsername" placeholder="שם משתמש">
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-primary" onclick="applyFilters()">חפש</button>
                        <button class="btn btn-sm btn-secondary" onclick="clearFilters()">נקה</button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-3" id="statsCards">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">סה"כ אירועים</h5>
                            <h2 class="mb-0" id="statTotal">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <h5 class="card-title text-danger">קריטיים</h5>
                            <h2 class="mb-0 text-danger" id="statCritical">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning">אזהרות</h5>
                            <h2 class="mb-0 text-warning" id="statWarning">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success">24 שעות אחרונות</h5>
                            <h2 class="mb-0 text-success" id="stat24h">-</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="logsTable">
                            <thead>
                                <tr>
                                    <th>זמן</th>
                                    <th>משתמש</th>
                                    <th>אירוע</th>
                                    <th>IP</th>
                                    <th>חומרה</th>
                                    <th>פרטים</th>
                                </tr>
                            </thead>
                            <tbody id="logsBody">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">טוען...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilters = {};

function refreshLogs() {
    loadLogs(currentFilters);
}

function applyFilters() {
    currentFilters = {
        action: $('#filterAction').val(),
        severity: $('#filterSeverity').val(),
        date: $('#filterDate').val(),
        username: $('#filterUsername').val()
    };
    loadLogs(currentFilters);
}

function clearFilters() {
    $('#filterAction, #filterSeverity, #filterDate, #filterUsername').val('');
    currentFilters = {};
    loadLogs();
}

function loadLogs(filters = {}) {
    $.ajax({
        url: 'security_logs_api.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                renderLogs(response.logs);
                updateStats(response.stats);
            } else {
                alert('שגיאה בטעינת לוגים: ' + (response.error || 'Unknown'));
            }
        },
        error: function() {
            $('#logsBody').html('<tr><td colspan="6" class="text-center text-danger">שגיאה בטעינת נתונים</td></tr>');
        }
    });
}

function renderLogs(logs) {
    if (logs.length === 0) {
        $('#logsBody').html('<tr><td colspan="6" class="text-center">לא נמצאו רשומות</td></tr>');
        return;
    }

    const html = logs.map(log => {
        const severityBadge = {
            'info': 'bg-primary',
            'warning': 'bg-warning',
            'critical': 'bg-danger'
        }[log.severity] || 'bg-secondary';

        const details = log.details ? JSON.stringify(JSON.parse(log.details), null, 2) : '';
        
        return `
            <tr>
                <td style="white-space: nowrap">${log.timestamp}</td>
                <td>${log.username || '<span class="text-muted">אורח</span>'}</td>
                <td><code>${log.action}</code></td>
                <td><small class="text-muted">${log.ip_address}</small></td>
                <td><span class="badge ${severityBadge}">${log.severity}</span></td>
                <td>
                    ${details ? `<button class="btn btn-sm btn-outline-secondary" onclick='showDetails(${JSON.stringify(details)})'>
                        <i class="bi bi-eye"></i>
                    </button>` : '-'}
                </td>
            </tr>
        `;
    }).join('');

    $('#logsBody').html(html);
}

function updateStats(stats) {
    $('#statTotal').text(stats.total || 0);
    $('#statCritical').text(stats.critical || 0);
    $('#statWarning').text(stats.warning || 0);
    $('#stat24h').text(stats.last_24h || 0);
}

function showDetails(details) {
    alert(details);
}

function clearOldLogs() {
    if (!confirm('האם למחוק לוגים מעל 30 ימים?')) {
        return;
    }

    $.ajax({
        url: 'security_logs_api.php',
        method: 'POST',
        data: { action: 'cleanup', days: 30 },
        success: function(response) {
            if (response.success) {
                alert(`נמחקו ${response.deleted} רשומות`);
                refreshLogs();
            } else {
                alert('שגיאה: ' + response.error);
            }
        }
    });
}

// Load logs on page load
$(document).ready(function() {
    loadLogs();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshLogs, 30000);
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
