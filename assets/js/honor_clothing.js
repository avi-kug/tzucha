var kavodDT = null;

function loadKavodData(refresh) {
    $('#loadingIndicator').show();
    $('#loadingText').text(refresh ? 'מרענן נתונים מ-Kavod... (עד דקה)' : 'טוען נתונים...');
    $('#tableContainer').hide();
    if (kavodDT) { kavodDT.destroy(); kavodDT = null; $('#kavodTableHead').empty(); }

    var url = 'honor_clothing_api.php' + (refresh ? '?refresh=1' : '');
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 90000,
        success: function(resp) {
            $('#loadingIndicator').hide();
            if (resp.error) {
                $('#loadingIndicator').show().html('<div class="text-danger"><b>שגיאה:</b> ' + resp.error + '</div>');
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
            kavodDT = $('#kavodTable').DataTable({
                data: resp.data,
                columns: columns,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json'
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
                        className: 'btn btn-secondary btn-sm'
                    }
                ]
            });
        },
        error: function(xhr) {
            var msg = 'שגיאה בטעינת נתונים מה-API';
            try {
                var errResp = JSON.parse(xhr.responseText);
                if (errResp.error) msg = errResp.error;
            } catch(e) {
                if (xhr.statusText === 'timeout') msg = 'הזמן הקצוב עבר. נסה שוב.';
            }
            $('#loadingIndicator').html('<div class="text-danger"><b>' + msg + '</b></div>');
        }
    });
}

$(document).ready(function() {
    $('#refreshBtn').on('click', function() {
        loadKavodData(true);
    });
    loadKavodData(false);
});
