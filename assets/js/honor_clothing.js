var kavodDT = null;
var alphonDT = null;

function displayKavodData(resp) {
    $('#loadingIndicator').hide();
    
    if (resp.error) {
        $('#loadingIndicator').show().html('<div class="text-danger"><b>שגיאה:</b> ' + resp.error + '</div>');
        return;
    }
    
    if (!resp.data || resp.data.length === 0) {
        $('#loadingIndicator').show().html('<div class="text-warning">אין נתונים להצגה</div>');
        return;
    }
    
    // Show cache info
    if (resp.cached_at) {
        $('#cacheInfo').text('עודכן: ' + resp.cached_at + ' | ' + (resp.count || 0) + ' רשומות');
    }
    var headers = resp.columns || [];
    var headHtml = headers.map(function(h) { return '<th>' + h + '</th>'; }).join('');
    $('#kavodTableHead').html(headHtml);
    var columns = headers.map(function(h) { return { data: h, title: h, defaultContent: '' }; });
    $('#tableContainer').show();
    
    // Properly destroy existing DataTable
    if ($.fn.dataTable.isDataTable('#kavodTable')) {
        $('#kavodTable').DataTable().destroy();
        kavodDT = null;
    }
    
    kavodDT = $('#kavodTable').DataTable({
        data: resp.data,
        columns: columns,
        pageLength: 25,
        language: {
            url: '../assets/js/datatables-he.json'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'colvis',
                text: '<i class="bi bi-eye"></i> עמודות',
                className: 'btn btn-secondary btn-sm',
                popoverTitle: 'עמודות'
            }
        ]
    });
}

function displayAlphonData(resp) {
    $('#alphonLoadingIndicator').hide();
    if (resp.error) {
        alert('שגיאה: ' + resp.error);
        return;
    }
    
    // Show cache info
    if (resp.cached_at) {
        $('#alphonCacheInfo').text('עודכן: ' + resp.cached_at + ' | ' + (resp.count || 0) + ' רשומות');
    }
    
    // Properly destroy existing DataTable
    if ($.fn.dataTable.isDataTable('#alphonTable')) {
        $('#alphonTable').DataTable().destroy();
        alphonDT = null;
    }
    
    alphonDT = $('#alphonTable').DataTable({
        data: resp.data,
        columns: [
            { 
                data: 'phone_id', 
                title: 'מזהה מלבושי כבוד', 
                defaultContent: '',
                render: function(data, type, row) {
                    return '<a href="#" class="view-person-details" style="color: #0d6efd; text-decoration: underline;">' + data + '</a>';
                }
            },
            { data: 'family_name', title: 'משפחה', defaultContent: '' },
            { data: 'first_name', title: 'שם', defaultContent: '' },
            { data: 'husband_id', title: 'ת.ז.', defaultContent: '' },
            { data: 'address', title: 'כתובת', defaultContent: '' },
            { data: 'neighborhood', title: 'שכונה', defaultContent: '' },
            { data: 'city', title: 'עיר', defaultContent: '' },
            { data: 'phone', title: 'טלפון', defaultContent: '' },
            { data: 'husband_mobile', title: 'נייד בעל', defaultContent: '' },
            { data: 'wife_mobile', title: 'נייד אשה', defaultContent: '' },
            { data: 'updated_email', title: 'כתובת מייל', defaultContent: '' },
            { data: 'number_of_children', title: 'מספר ילדים', defaultContent: '' },
            { data: 'update_status', title: 'סטטוס עדכון', defaultContent: '' },
            { data: 'orders_count', title: 'מספר הזמנות', defaultContent: '' },
            { data: 'total_previous_orders', title: 'סה״כ הזמנות קודמות', defaultContent: '' },
            { data: 'balance', title: 'יתרה', defaultContent: '' }
        ],
        pageLength: 25,
        language: {
            url: '../assets/js/datatables-he.json'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'colvis',
                text: '<i class="bi bi-eye"></i> עמודות',
                className: 'btn btn-secondary btn-sm',
                popoverTitle: 'עמודות'
            }
        ]
    });
    
    // Add click event for viewing details
    $('#alphonTable tbody').on('click', '.view-person-details', function(e) {
        e.preventDefault();
        var data = alphonDT.row($(this).parents('tr')).data();
        showPersonDetails(data);
    });
}

function showPersonDetails(data) {
    // Populate modal with data
    $('#detail_phone_id').text(data.phone_id || '-');
    $('#detail_family_name').text(data.family_name || '-');
    $('#detail_first_name').text(data.first_name || '-');
    $('#detail_husband_id').text(data.husband_id || '-');
    $('#detail_address').text(data.address || '-');
    $('#detail_neighborhood').text(data.neighborhood || '-');
    $('#detail_city').text(data.city || '-');
    $('#detail_phone').text(data.phone || '-');
    $('#detail_husband_mobile').text(data.husband_mobile || '-');
    $('#detail_wife_mobile').text(data.wife_mobile || '-');
    $('#detail_updated_email').text(data.updated_email || '-');
    $('#detail_number_of_children').text(data.number_of_children || '-');
    $('#detail_update_status').text(data.update_status || '-');
    $('#detail_orders_count').text(data.orders_count || '-');
    $('#detail_total_previous_orders').text(data.total_previous_orders || '-');
    $('#detail_balance').text(data.balance || '-');
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('kavodPersonModal'));
    modal.show();
}

function loadAlphonData(refresh) {
    $('#alphonLoadingIndicator').show();
    $('#alphonLoadingIndicator .mt-2').text(refresh ? 'מרענן נתונים מ-Kavod ומעדכן... (עד דקה)' : 'טוען נתונים משולבים...');
    
    var url = 'honor_clothing_combined_api.php' + (refresh ? '?refresh=1' : '');
    var timeout = refresh ? 90000 : 30000; // More time if refreshing from Kavod
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: timeout,
        success: function(resp) {
            displayAlphonData(resp);
        },
        error: function(xhr) {
            $('#alphonLoadingIndicator').hide();
            var msg = 'שגיאה בטעינת נתונים';
            try {
                var errResp = JSON.parse(xhr.responseText);
                if (errResp.error) msg = errResp.error;
            } catch(e) {
                if (xhr.statusText === 'timeout') msg = 'הזמן הקצוב עבר. נסה שוב.';
            }
            alert(msg);
        }
    });
}

function loadKavodData(refresh) {
    $('#loadingIndicator').show();
    $('#loadingText').text(refresh ? 'מרענן נתונים מ-Kavod... (עד דקה)' : 'טוען נתונים...');
    $('#tableContainer').hide();
    
    // Properly destroy existing DataTable
    if ($.fn.dataTable.isDataTable('#kavodTable')) {
        $('#kavodTable').DataTable().destroy();
        kavodDT = null;
        $('#kavodTableHead').empty();
    }

    var url = 'honor_clothing_api.php' + (refresh ? '?refresh=1' : '');
    console.log('Loading from:', url);
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 90000,
        success: function(resp) {
            displayKavodData(resp);
        },
        error: function(xhr, status, error) {
            try {
                var errResp = JSON.parse(xhr.responseText);
                if (errResp.error) msg = errResp.error;
            } catch(e) {
                if (xhr.statusText === 'timeout') msg = 'הזמן הקצוב עבר. נסה שוב.';
                else msg += ' (Status: ' + xhr.status + ')';
            }
            $('#loadingIndicator').html('<div class="text-danger"><b>' + msg + '</b></div>');
        }
    });
}

$(document).ready(function() {
    $('#refreshBtn').on('click', function() {
        loadKavodData(true);
    });
    
    $('#refreshAlphonBtn').on('click', function() {
        loadAlphonData(true); // Refresh from Kavod API
    });
    
    // Load alphon data when tab is shown
    $('#alphon-tab').on('shown.bs.tab', function() {
        if (!alphonDT) {
            loadAlphonData(false); // Load from DB + existing cache
        }
    });
    
    // Always load Kavod data from API (cache or fresh)
    loadKavodData(false);
});
