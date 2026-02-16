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
    let currentLogFile = '';

    function initSecurityLogs() {
        const eventFilter = document.getElementById('eventTypeFilter');
        const searchBox = document.getElementById('logSearch');
        const logFileSelect = document.getElementById('logFileSelect');
        const downloadBtn = document.getElementById('downloadLogBtn');
        
        if (!eventFilter || !searchBox || !logFileSelect || !downloadBtn) {
            return; // Elements not found, probably not on users page
        }

        // Load log files list
        loadLogFiles();

        // Event listeners
        eventFilter.addEventListener('change', filterLogs);
        searchBox.addEventListener('input', filterLogs);
        logFileSelect.addEventListener('change', function() {
            currentLogFile = this.value;
            if (currentLogFile) {
                loadLogEntries(currentLogFile);
            }
        });
        downloadBtn.addEventListener('click', downloadCurrentLog);
    }

    function loadLogFiles() {
        fetch('security_logs_api.php?action=list_files')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('logFileSelect');
                select.innerHTML = '<option value="">בחר קובץ לוג...</option>';
                
                if (data.files && data.files.length > 0) {
                    data.files.forEach(file => {
                        const option = document.createElement('option');
                        option.value = file.filename;
                        const sizeKB = (file.size / 1024).toFixed(2);
                        option.textContent = `${file.date} (${sizeKB} KB)`;
                        select.appendChild(option);
                    });
                    // Auto-select the first (most recent) file
                    select.selectedIndex = 1;
                    currentLogFile = select.value;
                    if (currentLogFile) {
                        loadLogEntries(currentLogFile);
                    }
                } else {
                    select.innerHTML = '<option value="">אין קבצי לוג</option>';
                }
            })
            .catch(error => {
                console.error('Error loading log files:', error);
                document.getElementById('logFileSelect').innerHTML = '<option value="">שגיאה בטעינה</option>';
            });
    }

    function loadLogEntries(filename) {
        const tbody = document.getElementById('securityLogsBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">טוען לוגים...</td></tr>';

        fetch(`security_logs_api.php?action=read_log&filename=${encodeURIComponent(filename)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">שגיאה: ${data.error}</td></tr>`;
                    return;
                }
                
                allLogEntries = data.entries || [];
                updateStatistics();
                filterLogs();
            })
            .catch(error => {
                console.error('Error loading log entries:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">שגיאה בטעינת לוגים</td></tr>';
            });
    }

    function filterLogs() {
        const eventFilter = document.getElementById('eventTypeFilter').value.toLowerCase();
        const searchTerm = document.getElementById('logSearch').value.toLowerCase();
        
        let filtered = allLogEntries;
        
        // Filter by event type
        if (eventFilter) {
            filtered = filtered.filter(entry => entry.event.toLowerCase() === eventFilter);
        }
        
        // Filter by search term
        if (searchTerm) {
            filtered = filtered.filter(entry => 
                entry.user.toLowerCase().includes(searchTerm) ||
                entry.ip.toLowerCase().includes(searchTerm) ||
                JSON.stringify(entry.details).toLowerCase().includes(searchTerm)
            );
        }
        
        displayLogEntries(filtered);
    }

    function displayLogEntries(entries) {
        const tbody = document.getElementById('securityLogsBody');
        
        if (!entries || entries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">אין רשומות</td></tr>';
            return;
        }
        
        const eventColors = {
            'LOGIN_SUCCESS': 'success',
            'LOGIN_FAILED': 'danger',
            'LOGIN_RATE_LIMIT': 'warning',
            'LOGIN_OTP_SENT': 'info',
            'LOGIN_OTP_VERIFY_FAILED': 'warning',
            'LOGIN_INACTIVE_USER': 'secondary',
            'LOGOUT': 'primary'
        };

        const eventLabels = {
            'LOGIN_SUCCESS': 'כניסה מוצלחת',
            'LOGIN_FAILED': 'כניסה נכשלה',
            'LOGIN_RATE_LIMIT': 'הגבלת קצב',
            'LOGIN_OTP_SENT': 'OTP נשלח',
            'LOGIN_OTP_VERIFY_FAILED': 'OTP נכשל',
            'LOGIN_INACTIVE_USER': 'משתמש לא פעיל',
            'LOGOUT': 'יציאה'
        };
        
        tbody.innerHTML = entries.map(entry => {
            const color = eventColors[entry.event] || 'secondary';
            const label = eventLabels[entry.event] || entry.event;
            const detailsStr = typeof entry.details === 'object' ? 
                JSON.stringify(entry.details, null, 2) : 
                entry.details;
            
            return `
                <tr>
                    <td>${escapeHtml(entry.timestamp)}</td>
                    <td>${escapeHtml(entry.user)}</td>
                    <td>${escapeHtml(entry.ip)}</td>
                    <td><span class="badge bg-${color}">${label}</span></td>
                    <td><small><pre style="margin: 0; white-space: pre-wrap;">${escapeHtml(detailsStr)}</pre></small></td>
                </tr>
            `;
        }).join('');
    }

    function updateStatistics() {
        const statsDiv = document.getElementById('logStats');
        const total = allLogEntries.length;
        const success = allLogEntries.filter(e => e.event === 'LOGIN_SUCCESS').length;
        const failed = allLogEntries.filter(e => e.event === 'LOGIN_FAILED').length;
        const rateLimit = allLogEntries.filter(e => e.event === 'LOGIN_RATE_LIMIT').length;
        
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statSuccess').textContent = success;
        document.getElementById('statFailed').textContent = failed;
        document.getElementById('statRateLimit').textContent = rateLimit;
        
        if (total > 0) {
            statsDiv.style.display = 'block';
        } else {
            statsDiv.style.display = 'none';
        }
    }

    function downloadCurrentLog() {
        if (currentLogFile) {
            window.location.href = `security_logs_api.php?action=download&filename=${encodeURIComponent(currentLogFile)}`;
        } else {
            alert('אנא בחר קובץ לוג להורדה');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
