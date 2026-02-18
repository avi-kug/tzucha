/**
 * ניהול בית נאמן - ילדים והוריהם מעל גיל 16
 */

$(document).ready(function() {
    let beitNeemanTable;
    let beitNeemanInitialized = false;
    // בדיקה אם יש כפתורי עריכה בטבלה (canEdit מ-PHP)
    const canEdit = $('#beitNeemanActionBar').find('#addBeitNeemanBtn').length > 0;
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    
    // Check if we're on the standalone page or a tab
    const isStandalonePage = $('#beit-neeman').length > 0 && $('#beit-neeman-tab').length === 0;
    
    // Function to check if beit neeman should be initialized
    function checkAndInitBeitNeeman() {
        // If standalone page, initialize immediately
        if (isStandalonePage && !beitNeemanInitialized) {
            beitNeemanInitialized = true;
            setTimeout(initBeitNeemanTable, 100);
            return;
        }
        
        // If tab, check if active
        if ($('#beit-neeman-tab').hasClass('active') && !beitNeemanInitialized) {
            beitNeemanInitialized = true;
            setTimeout(initBeitNeemanTable, 100);
        }
    }
    
    // Check on page load
    checkAndInitBeitNeeman();
    
    // Use MutationObserver to detect when tab becomes active (only if not standalone)
    if (!isStandalonePage) {
        const beitNeemanTab = document.getElementById('beit-neeman-tab');
        if (beitNeemanTab) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        checkAndInitBeitNeeman();
                    }
                });
            });
            observer.observe(beitNeemanTab, { attributes: true });
        }
        
        // Also listen to click on tab button as fallback
        $(document).on('click', '.tab-btn[data-tab="beit_neeman"]', function() {
            setTimeout(checkAndInitBeitNeeman, 50);
        });
    }
    
    function initBeitNeemanTable() {
        $('#beitNeemanTableLoader').show();
        $('#beitNeemanTableContent').hide().addClass('table-content-hidden');
        
        fetch('beit_neeman_api.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderBeitNeemanTable(data.records);
                } else {
                    alert('שגיאה בטעינת נתונים: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error loading beit neeman:', error);
                alert('שגיאה בטעינת נתונים');
            })
            .finally(() => {
                $('#beitNeemanTableLoader').hide();
                $('#beitNeemanTableContent').removeClass('table-content-hidden').show();
            });
    }
    
    function renderBeitNeemanTable(records) {
        const tbody = $('#beitNeemanTable tbody');
        tbody.empty();
        
        if (records.length === 0) {
            tbody.append('<tr><td colspan="' + (canEdit ? '19' : '18') + '" class="text-center">אין רשומות להצגה</td></tr>');
            return;
        }
        
        records.forEach(record => {
            const row = $('<tr>');
            
            // המרת יום לאות עברית
            let dayDisplay = '';
            if (record.birth_day) {
                dayDisplay = typeof numberToHebrewLetter === 'function'
                    ? numberToHebrewLetter(parseInt(record.birth_day))
                    : record.birth_day;
            }
            
            // המרת שנה לגימטריה עברית
            let yearDisplay = '';
            if (record.birth_year) {
                yearDisplay = typeof yearToHebrewYear === 'function'
                    ? yearToHebrewYear(parseInt(record.birth_year))
                    : record.birth_year;
            }
            
            row.append(`<td>${escapeHtml(record.family_name || '')}</td>`);
            row.append(`<td>${escapeHtml(record.child_name || '')}</td>`);
            row.append(`<td>${escapeHtml(record.age || '')}</td>`);
            row.append(`<td>${escapeHtml(record.father_name || '')}</td>`);
            row.append(`<td>${escapeHtml(record.father_mobile || '')}</td>`);
            row.append(`<td>${escapeHtml(record.mother_name || '')}</td>`);
            row.append(`<td>${escapeHtml(record.maiden_name || '')}</td>`);
            row.append(`<td>${escapeHtml(record.mother_mobile || '')}</td>`);
            row.append(`<td>${escapeHtml(record.address || '')}</td>`);
            row.append(`<td>${escapeHtml(record.city || '')}</td>`);
            row.append(`<td>${escapeHtml(record.gender || '')}</td>`);
            row.append(`<td>${escapeHtml(record.child_id || '')}</td>`);
            row.append(`<td>${dayDisplay}</td>`);
            row.append(`<td>${escapeHtml(record.birth_month || '')}</td>`);
            row.append(`<td>${yearDisplay}</td>`);
            row.append(`<td>${escapeHtml(record.study_place || '')}</td>`);
            row.append(`<td>${escapeHtml(record.notes || '')}</td>`);
            row.append(`<td>${escapeHtml(record.status || '')}</td>`);
            
            if (canEdit) {
                const actions = $('<td>');
                actions.append(`<button class="btn btn-sm btn-primary edit-bn-btn me-1" data-id="${record.id}"><i class="bi bi-pencil"></i></button>`);
                actions.append(`<button class="btn btn-sm btn-danger delete-bn-btn" data-id="${record.id}"><i class="bi bi-trash"></i></button>`);
                row.append(actions);
            }
            
            tbody.append(row);
        });
        
        // Initialize DataTable if not already initialized
        if ($.fn.DataTable.isDataTable('#beitNeemanTable')) {
            $('#beitNeemanTable').DataTable().destroy();
        }
        
        beitNeemanTable = $('#beitNeemanTable').DataTable({
            language: {
                url: '../assets/js/datatables-he.json'
            },
            order: [[0, 'asc'], [1, 'asc']],
            pageLength: 50,
            scrollX: true
        });
    }
    
    // Initialize hebrew date selects
    function initHebrewDateSelects() {
        // אתחול ימים עבריים (א'-ל')
        $('#bn_birth_day').html(buildHebrewDayOptions());
        
        // אתחול שנים עבריות (תש"ע וכו')
        $('#bn_birth_year').html(buildHebrewYearOptions(null, 5700, 5800));
    }
    
    // חישוב גיל אוטומטי
    function updateCalculatedAge() {
        const day = $('#bn_birth_day').val();
        const month = $('#bn_birth_month').val();
        const year = $('#bn_birth_year').val();
        
        if (year) {
            const age = calculateHebrewAge(day, month, year);
            if (age !== null) {
                $('#bn_calculated_age').val(age + ' שנים');
                $('#bn_age').val(age); // עדכון גם את שדה הגיל הרגיל
            } else {
                $('#bn_calculated_age').val('');
            }
        } else {
            $('#bn_calculated_age').val('');
        }
    }
    
    // Event listeners לחישוב גיל
    $('#bn_birth_day, #bn_birth_month, #bn_birth_year').on('change', updateCalculatedAge);
    
    // Add new record
    $('#addBeitNeemanBtn').on('click', function() {
        $('#beitNeemanModalLabel').text('הוסף רשומה חדשה - בית נאמן');
        $('#beitNeemanForm')[0].reset();
        $('#beit_neeman_id').val('');
        $('#child_record_id').val('');
        initHebrewDateSelects();
        $('#beitNeemanModal').modal('show');
    });
    
    // Sync from children database
    $('#syncBeitNeemanBtn').on('click', function() {
        if (!confirm('האם לסנכרן אוטומטית ילדים מעל גיל 16 ממאגר הילדים?')) {
            return;
        }
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>מסנכרן...');
        
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        
        fetch('beit_neeman_api.php?action=sync_from_children', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    initBeitNeemanTable();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בסנכרון');
            })
            .finally(() => {
                $(this).prop('disabled', false).text('סנכרון ממאגר ילדים');
            });
    });
    
    // Edit record
    $(document).on('click', '.edit-bn-btn', function() {
        const recordId = $(this).data('id');
        
        fetch(`beit_neeman_api.php?action=get&id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const record = data.record;
                    $('#beitNeemanModalLabel').text('ערוך רשומה - בית נאמן');
                    $('#beit_neeman_id').val(record.id);
                    $('#child_record_id').val(record.child_record_id || '');
                    
                    // אתחול ה-selects
                    $('#bn_birth_day').html(buildHebrewDayOptions(record.birth_day));
                    $('#bn_birth_year').html(buildHebrewYearOptions(record.birth_year, 5700, 5800));
                    
                    $('#bn_family_name').val(record.family_name);
                    $('#bn_child_name').val(record.child_name);
                    $('#bn_age').val(record.age);
                    $('#bn_father_name').val(record.father_name);
                    $('#bn_father_mobile').val(record.father_mobile);
                    $('#bn_mother_name').val(record.mother_name);
                    $('#bn_maiden_name').val(record.maiden_name);
                    $('#bn_mother_mobile').val(record.mother_mobile);
                    $('#bn_address').val(record.address);
                    $('#bn_city').val(record.city);
                    $('#bn_gender').val(record.gender);
                    $('#bn_child_id').val(record.child_id);
                    $('#bn_birth_day').val(record.birth_day);
                    $('#bn_birth_month').val(record.birth_month);
                    $('#bn_birth_year').val(record.birth_year);
                    $('#bn_study_place').val(record.study_place);
                    $('#bn_notes').val(record.notes);
                    $('#bn_status').val(record.status);
                    
                    // חישוב גיל
                    updateCalculatedAge();
                    $('#beitNeemanModal').modal('show');
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בטעינת נתונים');
            });
    });
    
    // Save record
    $('#saveBeitNeemanBtn').on('click', function() {
        const formData = new FormData($('#beitNeemanForm')[0]);
        formData.append('action', 'save');
        formData.append('csrf_token', csrfToken);
        
        fetch('beit_neeman_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    $('#beitNeemanModal').modal('hide');
                    initBeitNeemanTable();
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בשמירה');
            });
    });
    
    // Delete record
    $(document).on('click', '.delete-bn-btn', function() {
        if (!confirm('האם אתה בטוח שברצונך למחוק רשומה זו?')) {
            return;
        }
        
        const recordId = $(this).data('id');
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', recordId);
        formData.append('csrf_token', csrfToken);
        
        fetch('beit_neeman_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    initBeitNeemanTable();
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה במחיקה');
            });
    });
    
    // Export
    $('#exportBeitNeemanBtn').on('click', function() {
        window.location.href = 'beit_neeman_api.php?action=export';
    });
    
    // Import
    $('#importBeitNeemanFileBtn').on('click', function() {
        const fileInput = $('#beit_neeman_excel_file')[0];
        if (!fileInput.files.length) {
            alert('נא לבחור קובץ');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('excel_file', fileInput.files[0]);
        formData.append('csrf_token', csrfToken);
        
        $(this).prop('disabled', true).text('מייבא...');
        
        fetch('beit_neeman_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    $('#importBeitNeemanModal').modal('hide');
                    initBeitNeemanTable();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בייבוא');
            })
            .finally(() => {
                $(this).prop('disabled', false).text('ייבא');
            });
    });
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
});
