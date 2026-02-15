var cashDT = null;

function displayCashData(resp) {
    if (resp.error) {
        $('#loadingIndicator').show().html('<div class="text-danger"><b>שגיאה:</b> ' + resp.error + '</div>');
        return;
    }
    
    var headers = resp.columns || [];
    // Add "פעולות" column at the end
    headers.push('פעולות');
    
    var headHtml = headers.map(function(h) { 
        return '<th>' + h + '</th>'; 
    }).join('');
    $('#cashTableHead').html(headHtml);
    
    var columns = headers.map(function(h, idx) {
        if (h === 'פעולות') {
            return {
                data: null,
                title: h,
                orderable: false,
                render: function(data, type, row) {
                    var id = row['#'] || row['id'];
                    return '<button class="btn btn-sm btn-primary edit-btn" data-id="' + id + '" title="עריכה"><i class="bi bi-pencil"></i></button> ' +
                           '<button class="btn btn-sm btn-danger delete-btn" data-id="' + id + '" title="מחיקה"><i class="bi bi-trash"></i></button>';
                }
            };
        }
        return { 
            data: h, 
            title: h, 
            defaultContent: '' 
        };
    });
    
    $('#tableContainer').show();
    if (cashDT) { 
        cashDT.destroy(); 
    }
    cashDT = $('#cashTable').DataTable({
        data: resp.data,
        columns: columns,
        pageLength: 25,
        language: {
            search: 'חיפוש:',
            lengthMenu: 'הצג _MENU_ רשומות',
            zeroRecords: 'לא נמצאו רשומות',
            info: '_START_–_END_ מתוך _TOTAL_',
            infoEmpty: 'אין רשומות',
            infoFiltered: '(מסונן מ-_MAX_)',
            paginate: { 
                first: 'ראשון', 
                last: 'אחרון', 
                next: 'הבא', 
                previous: 'קודם' 
            }
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל',
                className: 'btn btn-success btn-sm',
                filename: 'תרומות_מזומן_' + new Date().toISOString().split('T')[0]
            },
            {
                extend: 'colvis',
                text: '<i class="bi bi-eye"></i> עמודות',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'copy',
                text: '<i class="bi bi-clipboard"></i> העתק',
                className: 'btn btn-secondary btn-sm'
            }
        ],
        order: [[0, 'desc']]
    });
}

function loadCashData(refresh) {
    $('#loadingIndicator').show();
    $('#loadingText').text(refresh ? 'מרענן נתונים מ-ipapp.org... (עד דקה)' : 'טוען נתונים...');
    $('#tableContainer').hide();
    if (cashDT) { 
        cashDT.destroy(); 
        cashDT = null; 
        $('#cashTableHead').empty(); 
        $('#cashTable tbody').empty();
    }

    var url = 'cash_api.php' + (refresh ? '?refresh=1' : '');
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 90000,
        success: function(resp) {
            $('#loadingIndicator').hide();
            displayCashData(resp);
        },
        error: function(xhr) {
            var msg = 'שגיאה בטעינת נתונים מה-API';
            try {
                var errResp = JSON.parse(xhr.responseText);
                if (errResp.error) msg = errResp.error;
            } catch(e) {
                if (xhr.statusText === 'timeout') {
                    msg = 'הזמן הקצוב עבר. נסה שוב.';
                }
            }
            $('#loadingIndicator').html('<div class="text-danger"><b>' + msg + '</b></div>');
        }
    });
}

$(document).ready(function() {
    // Load initial data from server (no AJAX)
    if (window.initialCashData && window.initialCashData.count > 0) {
        displayCashData(window.initialCashData);
    } else {
        loadCashData(false);
    }
    
    $('#refreshBtn').on('click', function() {
        loadCashData(true);
    });
    
    // Add button handler
    $('#addBtn').on('click', function() {
        // Clear form
        $('#addClient').val('');
        $('#addProject').val('חודש אדר'); // Default to חודש אדר
        $('#addAmount').val('');
        $('#addNotes').val('');
        
        var addModal = new bootstrap.Modal(document.getElementById('addModal'));
        addModal.show();
    });
    
    // Save new donation
    $('#saveAddBtn').on('click', function() {
        var client = $('#addClient').val().trim();
        var project = $('#addProject').val().trim();
        var amount = $('#addAmount').val();
        var notes = $('#addNotes').val();
        
        if (!client || !amount) {
            alert('נא למלא לקוח וסכום');
            return;
        }
        
        $('#addSpinner').removeClass('d-none');
        $('#saveAddBtn').prop('disabled', true);
        
        $.ajax({
            url: 'cash_api.php?action=insert',
            method: 'POST',
            data: { 
                client: client,
                project: project,
                amount: amount, 
                notes: notes
            },
            dataType: 'json',
            success: function(resp) {
                $('#addSpinner').addClass('d-none');
                $('#saveAddBtn').prop('disabled', false);
                
                if (resp.success) {
                    var addModal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                    addModal.hide();
                    alert('נוסף בהצלחה! הטבלה תתרענן כעת.');
                    loadCashData(true);
                } else {
                    alert('שגיאה: ' + (resp.error || 'לא ידוע'));
                }
            },
            error: function(xhr) {
                $('#addSpinner').addClass('d-none');
                $('#saveAddBtn').prop('disabled', false);
                
                var errorMsg = 'שגיאה בהוספה';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg += ': ' + xhr.responseJSON.error;
                }
                alert(errorMsg);
            }
        });
    });
    

    
    // Delete button handler
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if (!confirm('האם אתה בטוח שברצונך למחוק תרומה זו?')) {
            return;
        }
        
        $.ajax({
            url: 'cash_api.php?action=delete',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    alert('נמחק בהצלחה');
                    loadCashData(true);
                } else {
                    alert('שגיאה: ' + (resp.error || 'לא ידוע'));
                }
            },
            error: function() {
                alert('שגיאה במחיקה');
            }
        });
    });
    
    // Edit button handler
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var row = cashDT.row($(this).parents('tr')).data();
        
        // Show edit modal with row data
        $('#editId').val(id);
        // Try both Hebrew and English keys
        $('#editAmount').val(row['סכום'] || row['amount'] || '');
        $('#editNotes').val(row['הערות'] || row['notes'] || '');
        
        // Initialize Bootstrap modal
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
    
    // Save edit
    $('#saveEditBtn').on('click', function() {
        var id = $('#editId').val();
        var amount = $('#editAmount').val();
        var notes = $('#editNotes').val();
        
        // Update amount
        if (amount) {
            $.ajax({
                url: 'cash_api.php?action=update',
                method: 'POST',
                data: { id: id, key: 'amount', value: amount },
                dataType: 'json',
                success: function(resp) {
                    if (!resp.success) {
                        alert('שגיאה בעדכון סכום');
                        return;
                    }
                    
                    // Update notes if changed
                    if (notes !== undefined) {
                        $.ajax({
                            url: 'cash_api.php?action=update',
                            method: 'POST',
                            data: { id: id, key: 'notes', value: notes },
                            dataType: 'json',
                            success: function(resp2) {
                                if (resp2.success) {
                                    var editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                                    editModal.hide();
                                    alert('עודכן בהצלחה');
                                    loadCashData(true);
                                }
                            }
                        });
                    } else {
                        var editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide();
                        alert('עודכן בהצלחה');
                        loadCashData(true);
                    }
                }
            });
        }
    });
});
