(function () {
    function bindDeleteConfirm() {
        document.querySelectorAll('.delete-user-btn').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                var message = btn.getAttribute('data-confirm') || 'למחוק משתמש?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function initUsersModal() {
        if (!window.bootstrap || !document.getElementById('userModal')) {
            return false;
        }
        var modalEl = document.getElementById('userModal');
        var modal = new bootstrap.Modal(modalEl);

        var addBtn = document.getElementById('addUserBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                document.getElementById('userModalTitle').textContent = 'הוסף משתמש';
                document.getElementById('userFormAction').value = 'create';
                document.getElementById('userId').value = '';
                document.getElementById('userUsername').value = '';
                document.getElementById('userEmail').value = '';
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = true;
                document.getElementById('userRole').value = 'viewer';
                document.querySelectorAll('.perm-check').forEach(function (cb) {
                    cb.checked = false;
                });
                modal.show();
            });
        }

        document.querySelectorAll('.edit-user-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('userModalTitle').textContent = 'ערוך משתמש';
                document.getElementById('userFormAction').value = 'update';
                document.getElementById('userId').value = btn.dataset.id;
                document.getElementById('userUsername').value = btn.dataset.username;
                document.getElementById('userEmail').value = btn.dataset.email;
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = btn.dataset.active === '1';
                document.getElementById('userRole').value = btn.dataset.role || 'viewer';
                document.querySelectorAll('.perm-check').forEach(function (cb) {
                    cb.checked = false;
                });
                try {
                    var perms = JSON.parse(btn.dataset.perms || '[]');
                    perms.forEach(function (p) {
                        var el = document.querySelector('input[name="permissions[]"][value="' + p + '"]');
                        if (el) {
                            el.checked = true;
                        }
                    });
                } catch (e) {
                    // Ignore invalid perms data.
                }
                modal.show();
            });
        });

        bindDeleteConfirm();
        return true;
    }

    function tryInit(attempts) {
        if (initUsersModal()) {
            return;
        }
        if ((attempts || 0) < 50) {
            setTimeout(function () {
                tryInit((attempts || 0) + 1);
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        console.log('Users JS - DOM Content Loaded');
        console.log('About to initialize tabs...');
        initTabs();
        tryInit(0);
        initSecurityLogs();
    });

    function initTabs() {
        console.log('initTabs() called (users)');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');

        console.log('Found tab buttons:', tabBtns.length);
        console.log('Found tab panels:', tabPanels.length);

        if (tabBtns.length === 0) {
            console.error('No tab buttons found!');
            return;
        }

        tabBtns.forEach((btn, index) => {
            const tabName = btn.getAttribute('data-tab');
            console.log('Setting up button', index, tabName);
            
            btn.addEventListener('click', function(e) {
                try {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetTab = this.getAttribute('data-tab');
                    
                    console.log('Tab clicked:', targetTab);
                    
                    // Remove active class from all buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('active');
                    });
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Hide all tab panels
                    tabPanels.forEach(panel => {
                        panel.classList.remove('active');
                        console.log('Hiding panel:', panel.id);
                    });
                    
                    // Show target panel
                    const targetPanel = document.getElementById(targetTab + '-tab');
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                        console.log('Showing panel:', targetTab + '-tab');
                    } else {
                        console.error('Panel not found:', targetTab + '-tab');
                        console.error('Looking for ID:', targetTab + '-tab');
                        console.error('Available panel IDs:', Array.from(tabPanels).map(p => p.id));
                    }
                } catch (error) {
                    console.error('Error in tab click handler:', error);
                }
            });
        });
        
        console.log('Tabs initialized successfully (users)');
    }

    // Security Logs functionality
    let allLogEntries = [];

    function initSecurityLogs() {
        const eventFilter = document.getElementById('eventTypeFilter');
        const searchBox = document.getElementById('logSearch');
        
        if (!eventFilter || !searchBox) {
            return; // Elements not found, probably not on users page
        }

        // Load logs from database immediately
        loadSecurityLogsFromDB();

        // Event listeners
        eventFilter.addEventListener('change', filterLogsFromDB);
        searchBox.addEventListener('input', filterLogsFromDB);
        
        // Remove old file selection stuff
        const logFileSelect = document.getElementById('logFileSelect');
        const downloadBtn = document.getElementById('downloadLogBtn');
        if (logFileSelect) logFileSelect.style.display = 'none';
        if (downloadBtn) downloadBtn.style.display = 'none';
    }

    function loadSecurityLogsFromDB() {
        const tbody = document.getElementById('securityLogsBody');
        if (!tbody) return;
        
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">טוען לוגים...</td></tr>';

        fetch('security_logs_api.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Security logs loaded:', data);
                
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">שגיאה בטעינת לוגים: ${data.error || 'לא ידוע'}</td></tr>`;
                    return;
                }
                
                allLogEntries = data.logs || [];
                console.log('Loaded ' + allLogEntries.length + ' log entries');
                updateStatisticsFromDB();
                filterLogsFromDB();
            })
            .catch(error => {
                console.error('Error loading security logs:', error);
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">שגיאה בטעינת לוגים: ${error.message}</td></tr>`;
            });
    }

    function filterLogsFromDB() {
        const eventFilter = document.getElementById('eventTypeFilter');
        const searchBox = document.getElementById('logSearch');
        
        if (!eventFilter || !searchBox) return;
        
        const eventType = eventFilter.value.toLowerCase();
        const searchTerm = searchBox.value.toLowerCase();
        
        let filtered = allLogEntries;
        
        // Filter by event type
        if (eventType) {
            filtered = filtered.filter(entry => entry.action.toLowerCase() === eventType);
        }
        
        // Filter by search term
        if (searchTerm) {
            filtered = filtered.filter(entry => 
                (entry.username && entry.username.toLowerCase().includes(searchTerm)) ||
                (entry.ip_address && entry.ip_address.toLowerCase().includes(searchTerm)) ||
                (entry.details && entry.details.toLowerCase().includes(searchTerm))
            );
        }
        
        displayLogEntriesFromDB(filtered);
    }

    function displayLogEntriesFromDB(entries) {
        const tbody = document.getElementById('securityLogsBody');
        if (!tbody) return;
        
        if (!entries || entries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">אין לוגי אבטחה להצגה. הלוגים יירשמו כאן אוטומטית כשמתבצעות פעולות במערכת.</td></tr>';
            return;
        }
        
        let html = '';
        entries.forEach(entry => {
            const username = escapeHtml(entry.username || 'אורח');
            const ip = escapeHtml(entry.ip_address || '-');
            const action = escapeHtml(entry.action || '-');
            const timestamp = escapeHtml(entry.timestamp || '-');
            
            let detailsText = '-';
            if (entry.details) {
                try {
                    const details = typeof entry.details === 'string' ? JSON.parse(entry.details) : entry.details;
                    detailsText = escapeHtml(JSON.stringify(details, null, 2));
                } catch (e) {
                    detailsText = escapeHtml(String(entry.details));
                }
            }
            
            html += `
                <tr>
                    <td style="white-space: nowrap;">${timestamp}</td>
                    <td>${username}</td>
                    <td><small>${ip}</small></td>
                    <td><code>${action}</code></td>
                    <td><small><pre style="margin:0; max-height:100px; overflow-y:auto;">${detailsText}</pre></small></td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }

    function updateStatisticsFromDB() {
        const statsDiv = document.getElementById('logStats');
        if (!statsDiv || !allLogEntries) return;
        
        const total = allLogEntries.length;
        const success = allLogEntries.filter(e => e.action === 'LOGIN_SUCCESS' || e.action === 'LOGIN_SUCCESS_SAME_IP_TODAY').length;
        const failed = allLogEntries.filter(e => e.action === 'LOGIN_FAILED').length;
        const rateLimit = allLogEntries.filter(e => e.action === 'LOGIN_RATE_LIMIT').length;
        
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statSuccess').textContent = success;
        document.getElementById('statFailed').textContent = failed;
        document.getElementById('statRateLimit').textContent = rateLimit;
        
        statsDiv.style.display = total > 0 ? 'block' : 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // System Monitoring Functions
    window.refreshMonitoring = function() {
        if (!document.getElementById('system-monitor')) return;
        
        loadHealthCheck();
        updateMonitorTimestamp();
    };

    function updateMonitorTimestamp() {
        const now = new Date().toLocaleString('he-IL');
        const elem = document.getElementById('lastMonitorUpdate');
        if (elem) elem.textContent = now;
    }

    function loadHealthCheck() {
        fetch('health.php')
            .then(response => response.json())
            .then(health => {
                const statusBadge = document.getElementById('statusBadge');
                if (statusBadge) {
                    if (health.status === 'healthy') {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'תקין';
                    } else if (health.status === 'degraded') {
                        statusBadge.className = 'badge bg-warning';
                        statusBadge.textContent = 'אזהרה';
                    } else {
                        statusBadge.className = 'badge bg-danger';
                        statusBadge.textContent = 'שגיאה';
                    }
                }

                // Hebrew translations for health check names
                const hebrewNames = {
                    'database': 'מסד נתונים',
                    'tables': 'טבלאות',
                    'disk_space': 'שטח דיסק',
                    'memory': 'זיכרון',
                    'logs': 'קבצי לוג',
                    'uploads': 'קבצי העלאה',
                    'activity': 'פעילות'
                };

                // Render health checks with Hebrew translations
                const checksDiv = document.getElementById('healthChecks');
                if (checksDiv && health.checks) {
                    let html = '';
                    for (const [name, check] of Object.entries(health.checks)) {
                        const icon = check.status === 'ok' ? '✅' : check.status === 'warning' ? '⚠️' : '❌';
                        const statusClass = check.status === 'ok' ? 'text-success' : check.status === 'warning' ? 'text-warning' : 'text-danger';
                        const hebrewName = hebrewNames[name] || name;
                        
                        // Translate common English messages to Hebrew
                        let message = check.message || '';
                        message = message.replace('Database connection successful', 'חיבור למסד נתונים תקין');
                        message = message.replace('Database connection failed', 'חיבור למסד נתונים נכשל');
                        message = message.replace('All required tables exist', 'כל הטבלאות הנדרשות קיימות');
                        message = message.replace('Missing tables:', 'טבלאות חסרות:');
                        message = message.replace('Low disk space:', 'שטח דיסק נמוך:');
                        message = message.replace('Disk space getting low:', 'שטח דיסק מתמעט:');
                        message = message.replace('Disk space:', 'שטח דיסק פנוי:');
                        message = message.replace('free', 'פנויים');
                        message = message.replace('PHP Memory:', 'זיכרון PHP:');
                        message = message.replace('used', 'בשימוש');
                        message = message.replace('Logs directory writable', 'תיקיית לוגים ניתנת לכתיבה');
                        message = message.replace('Logs directory not found', 'תיקיית לוגים לא נמצאה');
                        message = message.replace('Uploads directory writable', 'תיקיית העלאות ניתנת לכתיבה');
                        message = message.replace('Uploads directory not found', 'תיקיית העלאות לא נמצאה');
                        message = message.replace('logins in last hour', 'כניסות בשעה האחרונה');
                        
                        html += `
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2 fs-4">${icon}</span>
                                    <div>
                                        <strong>${hebrewName}</strong><br>
                                        <small class="${statusClass}">${message}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    checksDiv.innerHTML = html;
                }

                // Update metrics
                if (health.checks.database) {
                    const dbStatus = document.getElementById('dbStatus');
                    if (dbStatus) dbStatus.innerHTML = health.checks.database.status === 'ok' ? '✅' : '❌';
                    const dbDetails = document.getElementById('dbDetails');
                    if (dbDetails) dbDetails.textContent = health.checks.database.message;
                }

                if (health.checks.disk_space) {
                    const diskSpace = document.getElementById('diskSpace');
                    if (diskSpace) diskSpace.textContent = health.checks.disk_space.free_gb + 'GB';
                    const diskDetails = document.getElementById('diskDetails');
                    if (diskDetails) diskDetails.textContent = health.checks.disk_space.percent_free + '% פנוי';
                }

                if (health.checks.memory) {
                    const memUsage = document.getElementById('memoryUsage');
                    if (memUsage) memUsage.textContent = health.checks.memory.usage_mb + 'MB';
                    const memDetails = document.getElementById('memoryDetails');
                    if (memDetails) memDetails.textContent = health.checks.memory.percent_used + '% בשימוש';
                }

                if (health.checks.activity) {
                    const actCount = document.getElementById('activityCount');
                    if (actCount) actCount.textContent = health.checks.activity.count;
                }
            })
            .catch(error => {
                console.error('Error loading health check:', error);
                const statusBadge = document.getElementById('statusBadge');
                if (statusBadge) {
                    statusBadge.className = 'badge bg-danger';
                    statusBadge.textContent = 'לא זמין';
                }
            });
    }

    // Auto-load monitoring when tab is shown
    document.addEventListener('DOMContentLoaded', function() {
        const monitorTab = document.querySelector('[data-tab="system-monitor"]');
        if (monitorTab) {
            monitorTab.addEventListener('click', function() {
                setTimeout(refreshMonitoring, 100);
            });
        }
    });
})();
