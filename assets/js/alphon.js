(function(){
    function initTable($){
        if ($.fn.DataTable.isDataTable('#alphonTable')) {
            $('#alphonTable').DataTable().clear().destroy();
        }
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        const table = $('#alphonTable').DataTable({
            language: { url: '../assets/js/datatables-he.json' },
            pageLength: 25,
            autoWidth: true,
            responsive: true,
            order: [[0, 'asc']],
            dom: "<'row'<'col-md-6'l><'col-md-6'f>>rt<'row'<'col-md-6'i><'col-md-6'p>>"
        });

        let originalValue = '';
        let currentCell = null;
        $('#alphonTable').on('dblclick', 'td.editable', function() {
            if (currentCell) { saveEdit(); }
            currentCell = $(this);
            originalValue = currentCell.text();
            const input = $('<input type="text" class="form-control form-control-sm">').val(originalValue).css({ 'width':'100%', 'box-sizing':'border-box' });
            currentCell.html(input).addClass('editing');
            input.focus().select();
            input.on('blur', saveEdit);
            input.on('keypress', function(e){ if (e.which === 13) { saveEdit(); } });
            input.on('keydown', function(e){ if (e.which === 27) { cancelEdit(); } });
        });

        function saveEdit() {
            if (!currentCell) return;
            const input = currentCell.find('input');
            if (!input.length) return;
            const newValue = input.val();
            const field = currentCell.data('field');
            const id = currentCell.closest('tr').data('id');
            if (newValue !== originalValue) {
                $.ajax({
                    url: 'people_api.php',
                    method: 'POST',
                    data: { action:'update', id, field, value:newValue, csrf_token: csrfToken },
                    success: function(response) {
                        if (response.success) {
                            currentCell.text(newValue).removeClass('editing');
                            currentCell.css('background-color', '#d4edda');
                            setTimeout(() => { currentCell.css('background-color', ''); }, 1000);
                        } else {
                            alert('שגיאה בעדכון: ' + (response.error || 'לא ידוע'));
                            currentCell.text(originalValue).removeClass('editing');
                        }
                    },
                    error: function(xhr){
                        const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'שגיאה בעדכון';
                        alert(msg);
                        currentCell.text(originalValue).removeClass('editing');
                    },
                    dataType:'json'
                });
            } else {
                currentCell.text(originalValue).removeClass('editing');
            }
            currentCell = null;
        }
        function cancelEdit(){ if (currentCell) { currentCell.text(originalValue).removeClass('editing'); currentCell = null; } }
    }

    function tryInit(attempts){
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            initTable(jQuery);
            return;
        }
        if ((attempts||0) < 50) {
            setTimeout(function(){ tryInit((attempts||0)+1); }, 100);
        }
    }
    tryInit(0);
})();
