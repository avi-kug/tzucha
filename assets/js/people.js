
(function() {
    const dataEl = document.getElementById('peopleData');
    const gizbarToAmarchal = dataEl ? JSON.parse(dataEl.dataset.gizbarToAmarchal || '{}') : {};
    const gizbarList = dataEl ? JSON.parse(dataEl.dataset.gizbarList || '[]') : [];
    const canEdit = dataEl ? dataEl.dataset.canEdit === '1' : false;
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // Initialize Tabs
    function initializeTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.dataset.tab;
                switchTab(tabName);
                // Update URL (query string)
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeTabs();
        // הצג את הטאב מה-URL אם קיים
        const url = new URL(window.location);
        const tabFromUrl = url.searchParams.get('tab');
        if (tabFromUrl) {
            switchTab(tabFromUrl);
        }
    });

    function switchTab(tabName) {
        // Update buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const targetBtn = document.querySelector(`[data-tab="${tabName}"]`);
        if (targetBtn) {
            targetBtn.classList.add('active');
        }
        
        // Update content
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        const targetContent = document.getElementById(`${tabName}-tab`);
        if (targetContent) {
            targetContent.classList.add('active');
        }
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }

    function setupPeoplePage($) {
        if (window.__peopleInitDone) { return; }
        window.__peopleInitDone = true;
        const modal = new bootstrap.Modal(document.getElementById('personModal'));
        let isEditMode = false;

        const messageEl = document.getElementById('messageModal');
        if (messageEl) {
            const messageModal = new bootstrap.Modal(messageEl);
            messageModal.show();
        }

        if ($.fn.DataTable.isDataTable('#peopleTable')) {
            $('#peopleTable').DataTable().clear().destroy();
        }
        const tableStateKey = 'peopleTableState';
        const table = $('#peopleTable').DataTable({
            destroy: true,
            retrieve: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json',
                search: 'חיפוש:',
                lengthMenu: 'הצג _MENU_ רשומות',
                info: 'מציג _START_ עד _END_ מתוך _TOTAL_ רשומות',
                infoEmpty: 'אין רשומות להצגה',
                infoFiltered: '(מסונן מתוך _MAX_ רשומות)',
                paginate: { first: 'ראשון', last: 'אחרון', next: 'הבא', previous: 'הקודם' }
            },
            pageLength: 25,
            autoWidth: true,
            responsive: true,
            order: [[6, 'asc']],
            dom: "<'row'<'col-md-6'l><'col-md-6'f>>Brt<'row'<'col-md-6'i><'col-md-6'p>>",
            buttons: [
                { extend: 'colvis', text: 'הצג/הסתר עמודות' }
            ],
            stateSave: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem(tableStateKey, JSON.stringify(data));
            },
            stateLoadCallback: function() {
                const raw = localStorage.getItem(tableStateKey);
                return raw ? JSON.parse(raw) : null;
            },
            columnDefs: [
                { orderable: false, targets: 0, width: '28px' },
                { orderable: false, targets: -1 }
            ]
        });

        function applyColumnResize(selector, dtInstance) {
            if (!$.fn.colResizable) { return; }
            let resizeTimer = null;
            const initResize = function() {
                try { $(selector).colResizable({ disable: true }); } catch (e) {}
                setTimeout(function() {
                    $(selector).colResizable({ liveDrag: false, resizeMode: 'fit' });
                    if (dtInstance && dtInstance.columns) { dtInstance.columns.adjust(); }
                }, 30);
            };
            initResize();
            if (dtInstance && dtInstance.on) {
                dtInstance.off('column-visibility.dt._resize');
                dtInstance.on('column-visibility.dt._resize', function() {
                    if (dtInstance && dtInstance.columns) { dtInstance.columns.adjust(); }
                    if (resizeTimer) { clearTimeout(resizeTimer); }
                    resizeTimer = setTimeout(function() {
                        initResize();
                    }, 200);
                });
            }
        }

        applyColumnResize('#peopleTable', table);
        if (table.buttons) {
            const placeColVis = function() {
                const $btns = $(table.buttons().container());
                if ($btns.length) {
                    $btns.insertAfter('#deleteSelectedBtn');
                }
            };
            placeColVis();
            table.off('draw._placeColVis');
            table.on('draw._placeColVis', function() {
                placeColVis();
            });
        }

        (function dedupeInfo(){
            const $wrapper = $('#peopleTable').closest('.dataTables_wrapper');
            const $infos = $wrapper.find('.dataTables_info');
            if ($infos.length > 1) { $infos.slice(1).remove(); }
        })();

        $('#addPersonBtn').on('click', function() {
            if (!canEdit) { alert('אין הרשאה לעריכה'); return; }
            isEditMode = false;
            $('#personModalLabel').text('הוסף איש קשר חדש');
            $('#personForm')[0].reset();
            $('#person_id').val('');
            modal.show();
        });

        function updateFullNameFromParts() {
            const family = $('#family_name').val() || '';
            const first = $('#first_name').val() || '';
            const combined = (family + ' ' + first).replace(/\s+/g, ' ').trim();
            $('#full_name').val(combined);
        }

        $('#family_name, #first_name').on('input', updateFullNameFromParts);

        function updateAmarchalFromGizbar(force) {
            const selected = $('#gizbar').val();
            const currentAmarchal = $('#amarchal').val();
            if (selected && gizbarToAmarchal[selected]) {
                if (force || !currentAmarchal) {
                    $('#amarchal').val(gizbarToAmarchal[selected]);
                }
            }
        }

        function ensureOption(selectId, value) {
            if (!value) { return; }
            const $select = $(selectId);
            if ($select.find('option').filter(function(){ return $(this).val() === value; }).length === 0) {
                $select.append($('<option>', { value: value, text: value }));
            }
        }

        $('#gizbar').on('change', function() {
            updateAmarchalFromGizbar(true);
        });

        const amarchalModal = new bootstrap.Modal(document.getElementById('addAmarchalModal'));
        const gizbarModal = new bootstrap.Modal(document.getElementById('addGizbarModal'));

        $('#addAmarchalBtn').on('click', function() {
            $('#amarchalPersonSelect').val('');
            amarchalModal.show();
        });

        $('#addGizbarBtn').on('click', function() {
            $('#gizbarPersonSelect').val('');
            gizbarModal.show();
        });

        function saveRepresentative(field, selectId, modalInstance) {
            if (!canEdit) { alert('אין הרשאה לעריכה'); return; }
            const $select = $(selectId);
            const personId = $select.val();
            const name = $select.find('option:selected').data('name');
            if (!personId || !name) {
                alert('נא לבחור שם מהרשימה');
                return;
            }
            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: { action: 'update', id: personId, field: field, value: name, csrf_token: csrfToken },
                success: function(response) {
                    if (response.success) {
                        modalInstance.hide();
                        window.location.reload();
                    } else {
                        alert('שגיאה בשמירה: ' + (response.error || 'לא ידוע'));
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'שגיאה בשמירה';
                    alert(msg);
                },
                dataType: 'json'
            });
        }

        $('#saveAmarchalBtn').on('click', function() {
            saveRepresentative('amarchal', '#amarchalPersonSelect', amarchalModal);
        });

        $('#saveGizbarBtn').on('click', function() {
            saveRepresentative('gizbar', '#gizbarPersonSelect', gizbarModal);
        });

        $('#peopleTable').on('click', '.edit-btn', function() {
            if (!canEdit) { alert('אין הרשאה לעריכה'); return; }
            isEditMode = true;
            const id = $(this).data('id');
            $('#personModalLabel').text('ערוך איש קשר');
            $.ajax({
                url: 'people_api.php?action=get_one&id=' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const person = response.data;
                        $('#person_id').val(person.id);
                        ensureOption('#amarchal', person.amarchal || '');
                        ensureOption('#gizbar', person.gizbar || '');
                        $('#amarchal').val(person.amarchal || '');
                        $('#gizbar').val(person.gizbar || '');
                        $('#software_id').val(person.software_id || '');
                        $('#donor_number').val(person.donor_number || '');
                        $('#chatan_harar').val(person.chatan_harar || '');
                        $('#family_name').val(person.family_name || '');
                        $('#first_name').val(person.first_name || '');
                        $('#name_for_mail').val(person.name_for_mail || '');
                        updateFullNameFromParts();
                        $('#husband_id').val(person.husband_id || '');
                        $('#wife_id').val(person.wife_id || '');
                        $('#address').val(person.address || '');
                        $('#mail_to').val(person.mail_to || '');
                        $('#neighborhood').val(person.neighborhood || '');
                        $('#floor').val(person.floor || '');
                        $('#city').val(person.city || '');
                        $('#phone').val(person.phone || '');
                        $('#husband_mobile').val(person.husband_mobile || '');
                        $('#wife_name').val(person.wife_name || '');
                        $('#wife_mobile').val(person.wife_mobile || '');
                        $('#updated_email').val(person.updated_email || '');
                        $('#husband_email').val(person.husband_email || '');
                        $('#wife_email').val(person.wife_email || '');
                        $('#receipts_to').val(person.receipts_to || '');
                        $('#alphon').val(person.alphon || '');
                        $('#send_messages').val(person.send_messages || '');
                        updateAmarchalFromGizbar(false);
                        modal.show();
                    } else {
                        alert('שגיאה בטעינת הנתונים');
                    }
                },
                error: function() { alert('שגיאה בטעינת הנתונים'); },
                dataType: 'json'
            });
        });

        $('#savePersonBtn').on('click', function() {
            if (!canEdit) { alert('אין הרשאה לעריכה'); return; }
            const formData = $('#personForm').serializeArray();
            const payload = {};
            formData.forEach(function(item) {
                payload[item.name] = item.value;
            });

            payload.action = isEditMode ? 'update_full' : 'add';
            if (isEditMode) {
                payload.id = $('#person_id').val();
            }
            payload.csrf_token = csrfToken;

            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: payload,
                success: function(response) {
                    if (response.success) {
                        modal.hide();
                        window.location.reload();
                    } else {
                        alert('שגיאה בשמירה: ' + (response.error || 'לא ידוע'));
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'שגיאה בשמירה';
                    alert(msg);
                },
                dataType: 'json'
            });
        });

        function updateDeleteSelectedState() {
            const anyChecked = $('.row-select:checked').length > 0;
            $('#deleteSelectedBtn').prop('disabled', !anyChecked);
        }

        $('#peopleTable').on('change', '.row-select', function() {
            updateDeleteSelectedState();
            const total = $('.row-select').length;
            const checked = $('.row-select:checked').length;
            $('#selectAllRows').prop('checked', total > 0 && total === checked);
        });

        $('#selectAllRows').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.row-select').prop('checked', isChecked);
            updateDeleteSelectedState();
        });

        function getExportIds(dtInstance) {
            if (!dtInstance || !dtInstance.rows) { return ''; }
            const ids = [];
            dtInstance.rows({ search: 'applied', order: 'applied' }).every(function () {
                const $row = $(this.node());
                const id = $row.data('id');
                if (id) { ids.push(id); }
            });
            return ids.join(',');
        }

        $('#exportPeopleForm').on('submit', function() {
            const ids = getExportIds(table);
            $(this).find('input[name="export_ids"]').val(ids);
        });

        $('#exportAmarchalForm').on('submit', function() {
            const ids = getExportIds(amarchalTable);
            $(this).find('input[name="export_ids"]').val(ids);
        });

        $('#exportGizbarForm').on('submit', function() {
            const ids = getExportIds(gizbarTable);
            $(this).find('input[name="export_ids"]').val(ids);
        });

        $('#deleteSelectedBtn').on('click', function() {
            if (!canEdit) { alert('אין הרשאה למחיקה'); return; }
            const ids = [];
            $('.row-select:checked').each(function() {
                ids.push($(this).data('id'));
            });

            if (ids.length === 0) {
                return;
            }

            if (!confirm('האם אתה בטוח שברצונך למחוק את הרשומות המסומנות?')) {
                return;
            }

            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: { action: 'delete_bulk', ids: ids, csrf_token: csrfToken },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('שגיאה במחיקה: ' + (response.error || 'לא ידוע'));
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'שגיאה במחיקה';
                    alert(msg);
                },
                dataType: 'json'
            });
        });

        let amarchalTable = null;
        let gizbarTable = null;
        if ($('#amarchalTable').length) {
            if ($.fn.DataTable.isDataTable('#amarchalTable')) { $('#amarchalTable').DataTable().clear().destroy(); }
            const amarchalStateKey = 'amarchalTableState';
            amarchalTable = $('#amarchalTable').DataTable({
                destroy: true,
                retrieve: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json' },
                pageLength: 25,
                order: [[0, 'asc']],
                dom: "<'row'<'col-md-6'l><'col-md-6'f>>Brt<'row'<'col-md-6'i><'col-md-6'p>>",
                buttons: [
                    { extend: 'colvis', text: 'הצג/הסתר עמודות' }
                ],
                stateSave: true,
                stateSaveCallback: function(settings, data) {
                    localStorage.setItem(amarchalStateKey, JSON.stringify(data));
                },
                stateLoadCallback: function() {
                    const raw = localStorage.getItem(amarchalStateKey);
                    return raw ? JSON.parse(raw) : null;
                }
            });
            applyColumnResize('#amarchalTable', amarchalTable);
            if (amarchalTable.buttons) {
                amarchalTable.buttons().container().appendTo('#amarchalActionBar');
            }
        }
        if ($('#gizbarTable').length) {
            if ($.fn.DataTable.isDataTable('#gizbarTable')) { $('#gizbarTable').DataTable().clear().destroy(); }
            const gizbarStateKey = 'gizbarTableState';
            gizbarTable = $('#gizbarTable').DataTable({
                destroy: true,
                retrieve: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json' },
                pageLength: 25,
                order: [[0, 'asc']],
                dom: "<'row'<'col-md-6'l><'col-md-6'f>>Brt<'row'<'col-md-6'i><'col-md-6'p>>",
                buttons: [
                    { extend: 'colvis', text: 'הצג/הסתר עמודות' }
                ],
                stateSave: true,
                stateSaveCallback: function(settings, data) {
                    localStorage.setItem(gizbarStateKey, JSON.stringify(data));
                },
                stateLoadCallback: function() {
                    const raw = localStorage.getItem(gizbarStateKey);
                    return raw ? JSON.parse(raw) : null;
                }
            });
            applyColumnResize('#gizbarTable', gizbarTable);
            if (gizbarTable.buttons) {
                gizbarTable.buttons().container().appendTo('#gizbarActionBar');
            }
        }

        let originalValue = '';
        let currentCell = null;
        let currentField = '';
        $('#peopleTable').on('dblclick', 'td.editable', function() {
            if (!canEdit) { return; }
            if (currentCell) { saveEdit(); }
            currentCell = $(this);
            currentField = currentCell.data('field');
            originalValue = currentCell.text();
            if (currentField === 'gizbar') {
                const select = $('<select class="form-select form-select-sm"></select>');
                select.append($('<option>', { value: '', text: '' }));
                (gizbarList || []).forEach(function(name) {
                    select.append($('<option>', { value: name, text: name }));
                });
                select.val(originalValue);
                currentCell.html(select).addClass('editing');
                select.focus();
                select.on('change', function(){ saveEdit(true); });
                select.on('blur', function(){ saveEdit(true); });
                select.on('keydown', function(e){ if (e.which === 27) { cancelEdit(); } });
            } else {
                const input = $('<input type="text" class="form-control form-control-sm">').val(originalValue).css({ 'width':'100%', 'box-sizing':'border-box' });
                currentCell.html(input).addClass('editing');
                input.focus().select();
                input.on('blur', saveEdit);
                input.on('keypress', function(e){ if (e.which === 13) { saveEdit(); } });
                input.on('keydown', function(e){ if (e.which === 27) { cancelEdit(); } });
            }
        });

        function saveEdit() {
            if (!currentCell) return;
            const input = currentCell.find('input');
            const select = currentCell.find('select');
            const editor = select.length ? select : input;
            if (!editor.length) return;
            const newValue = editor.val();
            const field = currentField || currentCell.data('field');
            const id = currentCell.closest('tr').data('id');
            if (newValue !== originalValue) {
                $.ajax({
                    url: 'people_api.php', method: 'POST', data: { action:'update', id, field, value:newValue, csrf_token: csrfToken },
                    success: function(response) {
                        if (response.success) {
                            currentCell.text(newValue).removeClass('editing');
                            currentCell.css('background-color', '#d4edda');
                            setTimeout(() => { currentCell.css('background-color', ''); }, 1000);
                            if (field === 'gizbar') {
                                const amarchalVal = gizbarToAmarchal[newValue] || '';
                                const $amarchalCell = currentCell.closest('tr').find("td[data-field='amarchal']");
                                if ($amarchalCell.length) {
                                    $amarchalCell.text(amarchalVal);
                                }
                                if (amarchalVal !== '') {
                                    $.ajax({
                                        url: 'people_api.php',
                                        method: 'POST',
                                        data: { action:'update', id, field:'amarchal', value: amarchalVal, csrf_token: csrfToken },
                                        dataType: 'json'
                                    });
                                }
                            }
                        } else { alert('שגיאה בעדכון: ' + response.error); currentCell.text(originalValue).removeClass('editing'); }
                    },
                    error: function(){ alert('שגיאה בעדכון'); currentCell.text(originalValue).removeClass('editing'); },
                    dataType:'json'
                });
            } else {
                currentCell.text(originalValue).removeClass('editing');
            }
            currentCell = null;
            currentField = '';
        }
        function cancelEdit(){ if (currentCell) { currentCell.text(originalValue).removeClass('editing'); currentCell = null; currentField = ''; } }

        $('input[name="reportType"]').on('change', function() {
            if ($(this).val() === 'amarchal') {
                $('#amarchalSelection').show();
                $('#gizbarSelection').hide();
                $('#gizbarSortOptions').hide();
            } else {
                $('#amarchalSelection').hide();
                $('#gizbarSelection').show();
                $('#gizbarSortOptions').show();
            }
        });

        $('#selectAllAmarchal').on('click', function() {
            $('.amarchal-checkbox').prop('checked', true);
        });

        $('#deselectAllAmarchal').on('click', function() {
            $('.amarchal-checkbox').prop('checked', false);
        });

        $('#selectAllGizbar').on('click', function() {
            $('.gizbar-checkbox').prop('checked', true);
        });

        $('#deselectAllGizbar').on('click', function() {
            $('.gizbar-checkbox').prop('checked', false);
        });

        $('#generatePdfBtn').on('click', function() {
            const reportType = $('input[name="reportType"]:checked').val();
            const outputType = $('input[name="outputType"]:checked').val();
            const sortBy = $('input[name="gizbarSort"]:checked').val() || 'gizbar';
            let selected = [];
            let selectedMonths = [];

            if (reportType === 'amarchal') {
                $('.amarchal-checkbox:checked').each(function() {
                    selected.push($(this).val());
                });
            } else {
                $('.gizbar-checkbox:checked').each(function() {
                    selected.push($(this).val());
                });
            }

            if (selected.length === 0) {
                alert('נא לבחור לפחות פריט אחד להדפסה');
                return;
            }

            $('.month-checkbox:checked').each(function() {
                selectedMonths.push($(this).val());
            });

            // Auto-split large label jobs
            if (outputType === 'labels') {
                const monthsCount = selectedMonths.length || 1;
                const itemsCount = selected.length;
                const estimatedLabels = itemsCount * monthsCount;
                const splitThreshold = 60;
                
                // Check if we should auto-split
                if ((itemsCount > splitThreshold && monthsCount > 1) || itemsCount > 100) {
                    const message = `בגלל כמות המדבקות הגדולה (${estimatedLabels} מדבקות),\n` +
                                  `המערכת תפצל את זה ל-2 קבצי PDF נפרדים.\n\n` +
                                  `כל קובץ יורד בנפרד. האם להמשיך?`;
                    if (!confirm(message)) {
                        return;
                    }
                    
                    // Split items into 2 halves
                    const half = Math.ceil(itemsCount / 2);
                    const firstHalf = selected.slice(0, half);
                    const secondHalf = selected.slice(half);
                    
                    // Submit first PDF
                    submitPdfForm(reportType, outputType, sortBy, firstHalf, selectedMonths, 'חלק 1 מתוך 2');
                    
                    // Submit second PDF after short delay
                    setTimeout(() => {
                        submitPdfForm(reportType, outputType, sortBy, secondHalf, selectedMonths, 'חלק 2 מתוך 2');
                    }, 1500);
                    
                    const modalElement = document.getElementById('printPdfModal');
                    if (modalElement) {
                        bootstrap.Modal.getInstance(modalElement)?.hide();
                    }
                    return;
                }
                
                // Warning for medium-large jobs
                if (estimatedLabels > 50) {
                    const minutes = Math.ceil(estimatedLabels / 10);
                    const message = `אתה עומד ליצור כ-${estimatedLabels} מדבקות.\n\n` +
                                  `זה עשוי לקחת ${minutes} דקות או יותר.\n\n` +
                                  `האם להמשיך?\n\n` +
                                  `טיפ: אם זה נכשל, פצל לחודשים נפרדים או בחר פחות גזברים בכל פעם (30-40 מקסימום).`;
                    if (!confirm(message)) {
                        return;
                    }
                }
            }

            submitPdfForm(reportType, outputType, sortBy, selected, selectedMonths, '');

            const modalElement = document.getElementById('printPdfModal');
            if (modalElement) {
                bootstrap.Modal.getInstance(modalElement)?.hide();
            }
        });
        
        function submitPdfForm(reportType, outputType, sortBy, selectedItems, selectedMonths, partLabel) {
            const form = $('<form>', {
                'method': 'POST',
                'action': 'print_people_pdf.php',
                'target': '_blank'
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'reportType',
                'value': reportType
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'outputType',
                'value': outputType
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'sortBy',
                'value': sortBy
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'selected',
                'value': JSON.stringify(selectedItems)
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'months',
                'value': JSON.stringify(selectedMonths)
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'partLabel',
                'value': partLabel
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'csrf_token',
                'value': csrfToken
            }));

            $('body').append(form);
            form.submit();
            form.remove();
        }

        $('#peopleTable').on('click', '.delete-btn', function() {
            if (!canEdit) { alert('אין הרשאה למחיקה'); return; }
            const id = $(this).data('id');
            const row = $(this).closest('tr');
            if (confirm('האם אתה בטוח שברצונך למחוק רשומה זו?')) {
                $.ajax({
                    url:'people_api.php', method:'POST', data:{ action:'delete', id, csrf_token: csrfToken },
                    success: function(response){ if (response.success) { table.row(row).remove().draw(); } else { alert('שגיאה במחיקה: ' + response.error); } },
                    error: function(){ alert('שגיאה במחיקה'); }, dataType:'json'
                });
            }
        });
    }

    function tryInit(attempts) {
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable && jQuery.fn.dataTable && jQuery.fn.dataTable.Buttons) {
            setupPeoplePage(jQuery);
            return;
        }
        if ((attempts||0) < 50) {
            setTimeout(function(){ tryInit((attempts||0)+1); }, 100);
        } else {
            console.warn('People page init: jQuery/DataTables not available');
        }
    }
    tryInit(0);
}());
