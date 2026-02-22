/**
 * ניהול מאגר ילדים
 */

$(document).ready(function() {
    let childrenTable;
    let childrenInitialized = false;
    // בדיקה אם יש כפתורי עריכה בטבלה (canEdit מ-PHP)
    const canEdit = $('#childrenActionBar').find('#addChildBtn').length > 0;
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    
    // Function to check if children tab is active and initialize
    function checkAndInitChildren() {
        if ($('#children-tab').hasClass('active') && !childrenInitialized) {
            childrenInitialized = true;
            setTimeout(initChildrenTable, 100);
        }
    }
    
    // Check on page load
    checkAndInitChildren();
    
    // Use MutationObserver to detect when tab becomes active
    const childrenTab = document.getElementById('children-tab');
    if (childrenTab) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    checkAndInitChildren();
                }
            });
        });
        observer.observe(childrenTab, { attributes: true });
    }
    
    // Also listen to click on tab button as fallback
    $(document).on('click', '.tab-btn[data-tab="children"]', function() {
        setTimeout(checkAndInitChildren, 50);
    });
    
    function initChildrenTable() {
        $('#childrenTableLoader').show();
        $('#childrenTableContent').hide().addClass('table-content-hidden');
        
        fetch('children_api.php?action=list&status=not_married')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderChildrenTable(data.children);
                } else {
                    alert('שגיאה בטעינת נתונים: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error loading children:', error);
                alert('שגיאה בטעינת נתונים');
            })
            .finally(() => {
                $('#childrenTableLoader').hide();
                $('#childrenTableContent').removeClass('table-content-hidden').show();
            });
    }
    
    function renderChildrenTable(children) {
        const tbody = $('#childrenTable tbody');
        tbody.empty();
        
        if (children.length === 0) {
            tbody.append('<tr><td colspan="' + (canEdit ? '13' : '12') + '" class="text-center">אין ילדים להצגה</td></tr>');
            return;
        }
        
        children.forEach(child => {
            const row = $('<tr>');
            
            // המרת יום לאות עברית
            let dayDisplay = '';
            if (child.birth_day) {
                dayDisplay = typeof numberToHebrewLetter === 'function' 
                    ? numberToHebrewLetter(parseInt(child.birth_day))
                    : child.birth_day;
            }
            
            // המרת שנה לגימטריה עברית
            let yearDisplay = '';
            if (child.birth_year) {
                yearDisplay = typeof yearToHebrewYear === 'function'
                    ? yearToHebrewYear(parseInt(child.birth_year))
                    : child.birth_year;
            }
            
            row.append(`<td>${escapeHtml(child.parent_family_name || '')}</td>`);
            row.append(`<td>${escapeHtml(child.parent_first_name || '')}</td>`);
            row.append(`<td>${escapeHtml(child.child_name || '')}</td>`);
            row.append(`<td>${escapeHtml(child.gender || '')}</td>`);
            row.append(`<td>${dayDisplay}</td>`);
            row.append(`<td>${escapeHtml(child.birth_month || '')}</td>`);
            row.append(`<td>${yearDisplay}</td>`);
            row.append(`<td>${escapeHtml(child.birth_date_gregorian || '')}</td>`);
            row.append(`<td>${escapeHtml(child.child_id || '')}</td>`);
            row.append(`<td>${escapeHtml(child.age || '')}</td>`);
            row.append(`<td>${escapeHtml(child.notes || '')}</td>`);
            row.append(`<td>${escapeHtml(child.status || '')}</td>`);
            
            if (canEdit) {
                const actions = $('<td>');
                actions.append(`<button class="btn btn-sm btn-primary edit-child-btn me-1" data-id="${child.id}"><i class="bi bi-pencil"></i></button>`);
                actions.append(`<button class="btn btn-sm btn-danger delete-child-btn" data-id="${child.id}"><i class="bi bi-trash"></i></button>`);
                row.append(actions);
            }
            
            tbody.append(row);
        });
        
        // Initialize DataTable if not already initialized
        if ($.fn.DataTable.isDataTable('#childrenTable')) {
            $('#childrenTable').DataTable().destroy();
        }
        
        childrenTable = $('#childrenTable').DataTable({
            language: {
                url: '../assets/js/datatables-he.json'
            },
            order: [[0, 'asc'], [2, 'asc']],
            pageLength: 50
        });
    }
    
    // Initialize hebrew date selects
    function initHebrewDateSelects() {
        // אתחול ימים עבריים (א'-ל')
        $('#birth_day').html(buildHebrewDayOptions());
        
        // אתחול שנים עבריות (תש"ע וכו')
        $('#birth_year').html(buildHebrewYearOptions(null, 5700, 5800));
    }
    
    // חישוב גיל אוטומטי
    function updateCalculatedAge() {
        const day = $('#birth_day').val();
        const month = $('#birth_month').val();
        const year = $('#birth_year').val();
        
        if (year) {
            const age = calculateHebrewAge(day, month, year);
            if (age !== null) {
                $('#calculated_age').val(age + ' שנים');
            } else {
                $('#calculated_age').val('');
            }
        } else {
            $('#calculated_age').val('');
        }
    }
    
    // Event listeners לחישוב גיל
    $('#birth_day, #birth_month, #birth_year').on('change', updateCalculatedAge);
    
    // Add new child
    $('#addChildBtn').on('click', function() {
        $('#childModalLabel').text('הוסף ילד חדש');
        $('#childForm')[0].reset();
        $('#child_id').val('');
        initHebrewDateSelects();
        $('#childModal').modal('show');
    });
    
    // Edit child
    $(document).on('click', '.edit-child-btn', function() {
        const childId = $(this).data('id');
        
        fetch(`children_api.php?action=get&id=${childId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const child = data.child;
                    $('#childModalLabel').text('ערוך ילד');
                    $('#child_id').val(child.id);
                    
                    // אתחול ה-selects
                    $('#birth_day').html(buildHebrewDayOptions(child.birth_day));
                    $('#birth_year').html(buildHebrewYearOptions(child.birth_year, 5700, 5800));
                    
                    $('#parent_select').val(child.parent_husband_id);
                    $('#child_name').val(child.child_name);
                    $('#gender').val(child.gender);
                    $('#birth_day').val(child.birth_day);
                    $('#birth_month').val(child.birth_month);
                    $('#birth_year').val(child.birth_year);
                    $('#birth_date_gregorian').val(child.birth_date_gregorian);
                    $('#child_id_field').val(child.child_id);
                    $('#child_status').val(child.status);
                    $('#child_notes').val(child.notes);
                    
                    // חישוב גיל
                    updateCalculatedAge();
                    
                    $('#childModal').modal('show');
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בטעינת נתונים');
            });
    });
    
    // Save child
    $('#saveChildBtn').on('click', function() {
        const formData = new FormData($('#childForm')[0]);
        formData.append('action', 'save');
        formData.append('csrf_token', csrfToken);
        
        fetch('children_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    $('#childModal').modal('hide');
                    initChildrenTable();
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה בשמירה');
            });
    });
    
    // Delete child
    $(document).on('click', '.delete-child-btn', function() {
        if (!confirm('האם אתה בטוח שברצונך למחוק ילד זה?')) {
            return;
        }
        
        const childId = $(this).data('id');
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', childId);
        formData.append('csrf_token', csrfToken);
        
        fetch('children_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    initChildrenTable();
                } else {
                    alert('שגיאה: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('שגיאה במחיקה');
            });
    });
    
    // Export children
    $('#exportChildrenBtn').on('click', function() {
        window.location.href = 'children_api.php?action=export&status=not_married';
    });
    
    // Import children
    $('#importChildrenFileBtn').on('click', function() {
        const fileInput = $('#children_excel_file')[0];
        if (!fileInput.files.length) {
            alert('נא לבחור קובץ');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('excel_file', fileInput.files[0]);
        formData.append('csrf_token', csrfToken);
        
        $(this).prop('disabled', true).text('מייבא...');
        
        fetch('children_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    $('#importChildrenModal').modal('hide');
                    initChildrenTable();
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
