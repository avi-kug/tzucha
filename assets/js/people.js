
(function() {
    // jQuery debounce function
    $.debounce = function(wait, func, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };
    
    const dataEl = document.getElementById('peopleData');
    const gizbarToAmarchal = dataEl ? JSON.parse(dataEl.dataset.gizbarToAmarchal || '{}') : {};
    const gizbarList = dataEl ? JSON.parse(dataEl.dataset.gizbarList || '[]') : [];
    const canEdit = dataEl ? dataEl.dataset.canEdit === '1' : false;
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // Function to hide table loader and show content for specific tab
    function hideTableLoader(tabName) {
        const loader = document.getElementById(tabName + 'TableLoader');
        const content = document.getElementById(tabName + 'TableContent');
        
        if (loader && content) {
            loader.classList.add('fade-out');
            content.classList.remove('table-content-hidden');
            content.classList.add('table-content-visible');
            
            // Remove loader from DOM after animation
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 300);
        }
    }

    // Failsafe: Always hide loaders after maximum 3 seconds
    setTimeout(function() {
        hideTableLoader('full');
        hideTableLoader('amarchal');
        hideTableLoader('gizbar');
    }, 3000);

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

        const messageEl = document.getElementById('messageModal');
        if (messageEl) {
            const messageModal = new bootstrap.Modal(messageEl);
            messageModal.show();
        }

        if ($.fn.DataTable.isDataTable('#peopleTable')) {
            $('#peopleTable').DataTable().clear().destroy();
        }
        
        // Clear old column state after reordering columns (Feb 2026)
        const oldStateKeys = ['peopleTableState'];
        oldStateKeys.forEach(key => {
            if (localStorage.getItem(key)) {
                localStorage.removeItem(key);
            }
        });
        
        const tableStateKey = 'peopleTableState_v2';
        
        const table = $('#peopleTable').DataTable({
            destroy: true,
            retrieve: true,
            language: {
                url: '../assets/js/datatables-he.json',
                search: 'חיפוש:',
                lengthMenu: 'הצג _MENU_ רשומות',
                info: 'מציג _START_ עד _END_ מתוך _TOTAL_ רשומות',
                infoEmpty: 'אין רשומות להצגה',
                infoFiltered: '(מסונן מתוך _MAX_ רשומות)',
                paginate: { first: 'ראשון', last: 'אחרון', next: 'הבא', previous: 'הקודם' }
            },
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            order: [[5, 'asc']],
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
                { orderable: false, targets: 0, width: '28px', className: 'select-col' },
                { orderable: false, targets: 1, width: '45px', className: 'text-center' },
                { orderable: false, targets: -1, width: '100px', className: 'text-center' }
            ],
            drawCallback: function() {
                // הצג את הטבלה אחרי שהעמודות סודרו
                $('#peopleTable').addClass('dt-initialized');
                
                // הזז את השורה הראשונה ל-action bar
                const firstRow = $('#peopleTable_wrapper > .row').first();
                const actionBar = $('#peopleActionBar');
                if (firstRow.length && actionBar.length && !actionBar.has(firstRow).length) {
                    firstRow.prependTo(actionBar);
                }
                
                // הזז את pagination למטה מחוץ לאזור הגלילה
                const paginationRow = $('#peopleTable_wrapper > .row').last();
                const paginationContainer = $('#full-tab .table-pagination');
                if (paginationRow.length && paginationContainer.length && !paginationContainer.has(paginationRow).length) {
                    paginationRow.appendTo(paginationContainer);
                }
            },
            initComplete: function() {
                // גם ב-init complete להבטיח שהטבלה תוצג
                $('#peopleTable').addClass('dt-initialized');
                
                // הזז את השורה הראשונה (length + search) ל-action bar
                const firstRow = $('#peopleTable_wrapper > .row').first();
                const actionBar = $('#peopleActionBar');
                if (firstRow.length && actionBar.length) {
                    firstRow.prependTo(actionBar);
                }
                
                // הזז את pagination למטה מחוץ לאזור הגלילה
                const paginationRow = $('#peopleTable_wrapper > .row').last();
                const paginationContainer = $('#full-tab .table-pagination');
                if (paginationRow.length && paginationContainer.length) {
                    paginationRow.appendTo(paginationContainer);
                }
                
                // Hide table loader and show content
                hideTableLoader('full');
                
                // Add filter icons to headers
                addColumnFilterIcons(table, 'peopleTable');
                
                // Enable multi-term search with comma separation
                enableMultiTermSearch();
            }
        });
        
        // Multi-term search functionality (comma-separated)
        function enableMultiTermSearch() {
            // Add helper text
            const $searchWrapper = $('.dataTables_filter');
            if ($searchWrapper.length && !$searchWrapper.find('.search-help').length) {
                const $helpText = $('<small class="text-muted d-block mt-1 search-help" style="font-size: 0.75rem;">ניתן לחפש מספר ערכים בפסיק, למשל: "כהן, לוי, ישראל"</small>');
                $searchWrapper.append($helpText);
            }
            
            // Override search behavior
            const $searchInput = $('.dataTables_filter input');
            $searchInput.off('keyup.DT search.DT input.DT paste.DT cut.DT');
            
            $searchInput.on('keyup.multiSearch search.multiSearch input.multiSearch paste.multiSearch cut.multiSearch', $.debounce(400, function() {
                const searchValue = $(this).val();
                
                if (searchValue.includes(',')) {
                    // Multi-term search with OR logic
                    const terms = searchValue.split(',').map(t => t.trim()).filter(t => t !== '');
                    const regexPattern = terms.map(t => $.fn.dataTable.util.escapeRegex(t)).join('|');
                    table.search(regexPattern, true, false).draw();
                } else {
                    // Regular search
                    table.search(searchValue).draw();
                }
            }));
        }
        
        // Add filter icons to column headers
        function addColumnFilterIcons(tableInstance, tableId) {
            const $table = $('#' + tableId);
            const $headers = $table.find('thead tr:first th');
            
            // Skip first 2 columns (checkbox and #) and last column (actions)
            $headers.slice(2, -1).each(function(index) {
                const $th = $(this);
                const columnIndex = index + 2; // Adjust for skipped columns
                const columnName = $th.text().trim();
                
                // Don't add if already has icon
                if ($th.find('.filter-icon').length > 0) {
                    return;
                }
                
                // Add filter icon
                const $icon = $('<i class="bi bi-funnel filter-icon ms-1" style="cursor: pointer; font-size: 0.8em; opacity: 0.5;"></i>');
                $th.append($icon);
                
                // Create dropdown menu (hidden by default)
                const dropdownId = 'filter-dropdown-' + tableId + '-' + columnIndex;
                const $dropdown = $(`
                    <div class="filter-dropdown" id="${dropdownId}" style="display: none; position: absolute; z-index: 9999; background: white; border: 1px solid #ccc; border-radius: 4px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); min-width: 250px;">
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size: 0.85em; font-weight: bold;">סינון: ${columnName}</label>
                            <select class="form-select form-select-sm mb-2 filter-mode">
                                <option value="contains">מכיל</option>
                                <option value="not_contains">לא מכיל</option>
                                <option value="equals">שווה ל</option>
                                <option value="not_equals">לא שווה ל</option>
                                <option value="starts">מתחיל ב</option>
                                <option value="ends">מסתיים ב</option>
                                <option value="empty">ריק</option>
                                <option value="not_empty">לא ריק</option>
                            </select>
                            <input type="text" class="form-control form-control-sm filter-value" placeholder="הזן ערך לחיפוש...">
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn btn-sm btn-secondary filter-clear">נקה</button>
                            <button class="btn btn-sm btn-primary filter-apply">החל</button>
                        </div>
                    </div>
                `);
                
                $('body').append($dropdown);
                
                // Icon click handler
                $icon.on('click', function(e) {
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    $('.filter-dropdown').not('#' + dropdownId).hide();
                    
                    // Toggle this dropdown
                    const isVisible = $dropdown.is(':visible');
                    $dropdown.toggle();
                    
                    if (!isVisible) {
                        // Position dropdown near the icon
                        const iconOffset = $icon.offset();
                        $dropdown.css({
                            top: iconOffset.top + $icon.outerHeight() + 5,
                            left: iconOffset.left - $dropdown.outerWidth() + $icon.outerWidth()
                        });
                    }
                });
                
                // Apply filter
                $dropdown.find('.filter-apply').on('click', function() {
                    const mode = $dropdown.find('.filter-mode').val();
                    const value = $dropdown.find('.filter-value').val();
                    
                    applyColumnFilter(tableInstance, columnIndex, value, mode, tableId);
                    $icon.css('opacity', value || mode === 'empty' || mode === 'not_empty' ? '1' : '0.5');
                    $dropdown.hide();
                });
                
                // Clear filter
                $dropdown.find('.filter-clear').on('click', function() {
                    $dropdown.find('.filter-value').val('');
                    $dropdown.find('.filter-mode').val('contains');
                    clearColumnFilter(tableInstance, columnIndex, tableId);
                    $icon.css('opacity', '0.5');
                    $dropdown.hide();
                });
                
                // Handle mode change - hide input for empty/not_empty
                $dropdown.find('.filter-mode').on('change', function() {
                    const mode = $(this).val();
                    if (mode === 'empty' || mode === 'not_empty') {
                        $dropdown.find('.filter-value').hide();
                    } else {
                        $dropdown.find('.filter-value').show();
                    }
                });
                
                // Enter key to apply
                $dropdown.find('.filter-value').on('keypress', function(e) {
                    if (e.which === 13) {
                        $dropdown.find('.filter-apply').click();
                    }
                });
            });
            
            // Close dropdowns on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.filter-dropdown, .filter-icon').length) {
                    $('.filter-dropdown').hide();
                }
            });
        }
        
        // Apply column filter
        function applyColumnFilter(tableInstance, columnIndex, value, mode, tableId) {
            // Remove previous filter for this column
            const filterKey = tableId + '_col_' + columnIndex;
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return !fn.__filterKey || fn.__filterKey !== filterKey;
            });
            
            // Add new filter
            if (value || mode === 'empty' || mode === 'not_empty') {
                const filterFunc = function(settings, searchData, index, rowData, counter) {
                    if (settings.nTable.id !== tableId) {
                        return true;
                    }
                    
                    const cellData = (searchData[columnIndex] || '').toString().toLowerCase();
                    const searchValue = value.toLowerCase();
                    
                    switch(mode) {
                        case 'contains':
                            return cellData.includes(searchValue);
                        case 'not_contains':
                            return !cellData.includes(searchValue);
                        case 'equals':
                            return cellData === searchValue;
                        case 'not_equals':
                            return cellData !== searchValue;
                        case 'starts':
                            return cellData.startsWith(searchValue);
                        case 'ends':
                            return cellData.endsWith(searchValue);
                        case 'empty':
                            return cellData === '';
                        case 'not_empty':
                            return cellData !== '';
                        default:
                            return true;
                    }
                };
                filterFunc.__filterKey = filterKey;
                $.fn.dataTable.ext.search.push(filterFunc);
            }
            
            tableInstance.draw();
        }
        
        // Clear column filter
        function clearColumnFilter(tableInstance, columnIndex, tableId) {
            const filterKey = tableId + '_col_' + columnIndex;
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return !fn.__filterKey || fn.__filterKey !== filterKey;
            });
            tableInstance.draw();
        }

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
            $('#personModalLabel').text('הוסף איש קשר חדש');
            $('#personForm')[0].reset();
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

        $('#gizbar').on('change', function() {
            updateAmarchalFromGizbar(true);
        });

        $('#savePersonBtn').on('click', function() {
            if (!canEdit) { alert('אין הרשאה לעריכה'); return; }
            const formData = $('#personForm').serializeArray();
            const payload = {};
            formData.forEach(function(item) {
                payload[item.name] = item.value;
            });

            payload.action = 'add';
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
                language: { url: '../assets/js/datatables-he.json' },
                pageLength: 25,
                autoWidth: false,
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
                },
                drawCallback: function() {
                    $('#amarchalTable').addClass('dt-initialized');
                    
                    // הזז את השורה הראשונה ל-action bar
                    const firstRow = $('#amarchalTable_wrapper > .row').first();
                    const actionBar = $('#amarchalActionBar');
                    if (firstRow.length && actionBar.length && !actionBar.has(firstRow).length) {
                        firstRow.prependTo(actionBar);
                    }
                    
                    // הזז את pagination למטה מחוץ לאזור הגלילה
                    const paginationRow = $('#amarchalTable_wrapper > .row').last();
                    const paginationContainer = $('#amarchal-tab .table-pagination');
                    if (paginationRow.length && paginationContainer.length && !paginationContainer.has(paginationRow).length) {
                        paginationRow.appendTo(paginationContainer);
                    }
                },
                initComplete: function() {
                    $('#amarchalTable').addClass('dt-initialized');
                    
                    // הזז את השורה הראשונה (length + search) ל-action bar
                    const firstRow = $('#amarchalTable_wrapper > .row').first();
                    const actionBar = $('#amarchalActionBar');
                    if (firstRow.length && actionBar.length) {
                        firstRow.prependTo(actionBar);
                    }
                    
                    // הזז את pagination למטה מחוץ לאזור הגלילה
                    const paginationRow = $('#amarchalTable_wrapper > .row').last();
                    const paginationContainer = $('#amarchal-tab .table-pagination');
                    if (paginationRow.length && paginationContainer.length) {
                        paginationRow.appendTo(paginationContainer);
                    }
                    
                    // Hide table loader and show content
                    hideTableLoader('amarchal');
                    
                    // Add filter icons
                    addColumnFilterIcons(amarchalTable, 'amarchalTable');
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
                language: { url: '../assets/js/datatables-he.json' },
                pageLength: 25,
                autoWidth: false,
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
                },
                drawCallback: function() {
                    $('#gizbarTable').addClass('dt-initialized');
                    
                    // הזז את השורה הראשונה ל-action bar
                    const firstRow = $('#gizbarTable_wrapper > .row').first();
                    const actionBar = $('#gizbarActionBar');
                    if (firstRow.length && actionBar.length && !actionBar.has(firstRow).length) {
                        firstRow.prependTo(actionBar);
                    }
                    
                    // הזז את pagination למטה מחוץ לאזור הגלילה
                    const paginationRow = $('#gizbarTable_wrapper > .row').last();
                    const paginationContainer = $('#gizbar-tab .table-pagination');
                    if (paginationRow.length && paginationContainer.length && !paginationContainer.has(paginationRow).length) {
                        paginationRow.appendTo(paginationContainer);
                    }
                },
                initComplete: function() {
                    $('#gizbarTable').addClass('dt-initialized');
                    
                    // הזז את השורה הראשונה (length + search) ל-action bar
                    const firstRow = $('#gizbarTable_wrapper > .row').first();
                    const actionBar = $('#gizbarActionBar');
                    if (firstRow.length && actionBar.length) {
                        firstRow.prependTo(actionBar);
                    }
                    
                    // הזז את pagination למטה מחוץ לאזור הגלילה
                    const paginationRow = $('#gizbarTable_wrapper > .row').last();
                    const paginationContainer = $('#gizbar-tab .table-pagination');
                    if (paginationRow.length && paginationContainer.length) {
                        paginationRow.appendTo(paginationContainer);
                    }
                    
                    // Hide table loader and show content
                    hideTableLoader('gizbar');
                    
                    // Add filter icons
                    addColumnFilterIcons(gizbarTable, 'gizbarTable');
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

        // Person details modal handler
        $('#peopleTable').on('click', '.person-details-btn', function() {
            var softwareId = $(this).data('software-id');
            var personId = $(this).data('person-id');
            var personName = $(this).data('name');
            showPersonDetailsModal(softwareId, personId, personName);
        });
        
        // Edit button from person details modal - toggle edit mode
        $(document).on('click', '#editPersonFromDetailsBtn', function() {
            if (!canEdit) { 
                alert('אין הרשאה לעריכה'); 
                return; 
            }
            
            // Toggle to edit mode
            togglePersonDetailsEditMode(true);
        });
        
        // Cancel edit from person details modal
        $(document).on('click', '#cancelEditFromDetailsBtn', function() {
            var personId = $('#editPersonFromDetailsBtn').data('person-id');
            var softwareId = $('#editPersonFromDetailsBtn').data('software-id');
            var personName = $('#personName').text().replace('פרטים מלאים - ', '');
            
            // Reload data to cancel changes
            showPersonDetailsModal(softwareId, personId, personName);
        });
        
        // Save button from person details modal
        $(document).on('click', '#savePersonFromDetailsBtn', function() {
            if (!canEdit) { 
                alert('אין הרשאה לעריכה'); 
                return; 
            }
            
            var personId = $('#editPersonFromDetailsBtn').data('person-id');
            
            // Collect all input values
            var formData = {
                action: 'update_full',
                id: personId,
                csrf_token: csrfToken
            };
            
            // Get all edit inputs
            $('#basicInfoContent input, #basicInfoContent select, #basicInfoContent textarea').each(function() {
                var fieldName = $(this).data('field');
                if (fieldName) {
                    formData[fieldName] = $(this).val();
                }
            });
            
            // Save to server
            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('הפרטים עודכנו בהצלחה');
                        
                        // Close the modal
                        var detailsModal = bootstrap.Modal.getInstance(document.getElementById('personDetailsModal'));
                        if (detailsModal) {
                            detailsModal.hide();
                        }
                        
                        // Reload the table if we're on the people page
                        if (typeof table !== 'undefined' && table) {
                            $.ajax({
                                url: 'people_api.php?action=get_one&id=' + personId,
                                method: 'GET',
                                dataType: 'json',
                                success: function(resp) {
                                    if (resp.success && resp.data) {
                                        // Update the table row
                                        var rowNode = table.rows(function(idx, data, node) {
                                            return $(node).data('id') == personId;
                                        }).nodes()[0];
                                        
                                        if (rowNode) {
                                            $(rowNode).find('.editable').each(function() {
                                                var field = $(this).data('field');
                                                if (field && resp.data[field]) {
                                                    $(this).text(resp.data[field]);
                                                }
                                            });
                                        }
                                    }
                                }
                            });
                        }
                    } else {
                        alert('שגיאה בשמירה: ' + (response.error || 'לא ידוע'));
                    }
                },
                error: function() {
                    alert('שגיאה בשמירה');
                }
            });
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

// Show person details in modal
function showPersonDetailsModal(softwareId, personId, personName) {
    $('#personName').text(personName || 'פרטים');
    
    // Store personId and softwareId for edit button
    $('#editPersonFromDetailsBtn').data('person-id', personId);
    $('#editPersonFromDetailsBtn').data('software-id', softwareId);
    
    // Show/hide edit button based on permissions
    const dataEl = document.getElementById('peopleData');
    const canEdit = dataEl ? dataEl.dataset.canEdit === '1' : false;
    if (canEdit) {
        $('#editPersonFromDetailsBtn').show();
    } else {
        $('#editPersonFromDetailsBtn').hide();
    }
    
    // Reset to view mode
    togglePersonDetailsEditMode(false);
    
    var detailsModal = new bootstrap.Modal(document.getElementById('personDetailsModal'));
    detailsModal.show();
    
    // Hebrew field name mapping
    var hebrewFields = {
        'amarchal': 'אמרכל',
        'gizbar': 'גזבר',
        'software_id': 'מזהה קופות',
        'phone_id': 'מזהה מלבושי כבוד',
        'donor_number': 'מס תורם',
        'chatan_harar': "חתן הר'ר",
        'family_name': 'משפחה',
        'first_name': 'שם',
        'name_for_mail': 'שם לדואר',
        'full_name': 'שם ומשפחה ביחד',
        'husband_id': 'תעודת זהות בעל',
        'wife_id': 'תעודת זהות אשה',
        'address': 'כתובת',
        'mail_to': 'דואר ל',
        'neighborhood': 'שכונה / אזור',
        'floor': 'קומה',
        'city': 'עיר',
        'phone': 'טלפון',
        'husband_mobile': 'נייד בעל',
        'wife_name': 'שם האשה',
        'wife_mobile': 'נייד אשה',
        'updated_email': 'כתובת מייל מעודכן',
        'husband_email': 'מייל בעל',
        'wife_email': 'מייל אשה',
        'receipts_to': 'קבלות ל',
        'alphon': 'אלפון',
        'send_messages': 'שליחת הודעות',
        'last_change': 'שינוי אחרון',
        'foreign_id': 'מזהה מלבושי כבוד',
        'kavod_id': 'מזהה מלבושי כבוד'
    };
    
    // Load person details from API
    $.ajax({
        url: 'person_details_api.php',
        method: 'GET',
        data: { 
            software_id: softwareId,
            person_id: personId
        },
        dataType: 'json',
        success: function(data) {
            // Populate basic info - organized display order
            var basicHtml = '';
            if (data.person) {
                var p = data.person;
                
                // פרטים אישיים
                basicHtml += '<div class="col-12"><h6 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>פרטים אישיים</h6></div>';
                
                var personalFields = [
                    {key: 'family_name', label: 'משפחה'},
                    {key: 'first_name', label: 'שם'},
                    {key: 'name_for_mail', label: 'שם לדואר'},
                    {key: 'software_id', label: 'מזהה קופות'},
                    {key: 'phone_id', label: 'מזהה מלבושי כבוד'},
                    {key: 'foreign_id', label: 'מזהה מלבושי כבוד'},
                    {key: 'kavod_id', label: 'מזהה מלבושי כבוד'},
                    {key: 'wife_name', label: 'שם האשה'},
                    {key: 'husband_mobile', label: 'נייד בעל'},
                    {key: 'wife_mobile', label: 'נייד אשה'},
                    {key: 'phone', label: 'טלפון'},
                    {key: 'husband_id', label: 'תעודת זהות בעל'},
                    {key: 'wife_id', label: 'תעודת זהות אשה'},
                    {key: 'address', label: 'כתובת'},
                    {key: 'mail_to', label: 'דואר ל'},
                    {key: 'neighborhood', label: 'שכונה / אזור'},
                    {key: 'floor', label: 'קומה'},
                    {key: 'city', label: 'עיר'},
                    {key: 'updated_email', label: 'כתובת מייל מעודכן'},
                    {key: 'husband_email', label: 'מייל בעל'},
                    {key: 'wife_email', label: 'מייל אשה'}
                ];
                
                // Display personal fields
                personalFields.forEach(function(field) {
                    if (p[field.key]) {
                        basicHtml += '<div class="col-md-6 mb-2"><strong>' + field.label + ':</strong> ' + p[field.key] + '</div>';
                    }
                });
                
                // Add separator
                basicHtml += '<div class="col-12"><hr class="my-3"></div>';
                
                // פרטים נוספים
                basicHtml += '<div class="col-12"><h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>פרטים נוספים</h6></div>';
                
                // Display remaining fields (exclude already shown fields and system fields)
                var displayedKeys = personalFields.map(function(f) { return f.key; });
                displayedKeys.push('id', 'created_at'); // Don't show these
                
                for (var key in p) {
                    if (p[key] && displayedKeys.indexOf(key) === -1) {
                        var label = hebrewFields[key] || key;
                        basicHtml += '<div class="col-md-6 mb-2"><strong>' + label + ':</strong> ' + p[key] + '</div>';
                    }
                }
            } else {
                basicHtml = '<div class="col-12 text-muted">לא נמצאו פרטים בסיסיים</div>';
            }
            $('#basicInfoContent').html(basicHtml);
            
            // Store person data for edit mode
            $('#basicInfoContent').data('person-data', p);
            
            // Populate cash donations - single table with all details
            var cashHtml = '';
            if (data.cash_donations_all && data.cash_donations_all.length > 0) {
                cashHtml = '<table class="table table-sm table-striped">';
                cashHtml += '<thead><tr><th>תאריך</th><th>פרויקט</th><th>סכום</th><th>הערות</th></tr></thead><tbody>';
                data.cash_donations_all.forEach(function(item) {
                    cashHtml += '<tr>';
                    cashHtml += '<td>' + (item.date || '-') + '</td>';
                    cashHtml += '<td>' + (item.project || '-') + '</td>';
                    cashHtml += '<td>' + (item.amount || '0') + ' ש"ח</td>';
                    cashHtml += '<td>' + (item.notes || '-') + '</td>';
                    cashHtml += '</tr>';
                });
                cashHtml += '</tbody></table>';
            } else {
                cashHtml = '<p class="text-muted text-center">אין תרומות מזומן</p>';
            }
            $('#cashDonationsContent').html(cashHtml);
            
            // Populate standing orders
            var soHtml = '<h6>הוראת קבע כח הרבים:</h6>';
            if (data.standing_orders_koach && data.standing_orders_koach.length > 0) {
                soHtml += '<table class="table table-sm"><thead><tr><th>סכום</th><th>תאריך</th><th>הערות</th></tr></thead><tbody>';
                data.standing_orders_koach.forEach(function(item) {
                    soHtml += '<tr><td>' + (item.amount || '-') + '</td><td>' + (item.date || '-') + '</td><td>' + (item.notes || '-') + '</td></tr>';
                });
                soHtml += '</tbody></table>';
            } else {
                soHtml += '<p class="text-muted">אין הוראות קבע כח הרבים</p>';
            }
            
            soHtml += '<h6 class="mt-3">הוראת קבע אחים לחסד:</h6>';
            if (data.standing_orders_achim && data.standing_orders_achim.length > 0) {
                soHtml += '<table class="table table-sm"><thead><tr><th>סכום</th><th>תאריך</th><th>הערות</th></tr></thead><tbody>';
                data.standing_orders_achim.forEach(function(item) {
                    soHtml += '<tr><td>' + (item.amount || '-') + '</td><td>' + (item.date || '-') + '</td><td>' + (item.notes || '-') + '</td></tr>';
                });
                soHtml += '</tbody></table>';
            } else {
                soHtml += '<p class="text-muted">אין הוראות קבע אחים לחסד</p>';
            }
            $('#standingOrdersContent').html(soHtml);
            
            // Populate approved supports (תמיכות שאושרו)
            var supportsHtml = '';
            if (data.approved_supports && data.approved_supports.length > 0) {
                supportsHtml = '<div class="table-responsive">';
                supportsHtml += '<table class="table table-sm table-striped table-hover">';
                supportsHtml += '<thead class="table-light">';
                supportsHtml += '<tr>';
                supportsHtml += '<th>מס\' תורם</th>';
                supportsHtml += '<th>שם</th>';
                supportsHtml += '<th>משפחה</th>';
                supportsHtml += '<th>סכום</th>';
                supportsHtml += '<th>חודש תמיכה</th>';
                supportsHtml += '<th>תאריך אישור</th>';
                supportsHtml += '<th>אושר ע"י</th>';
                supportsHtml += '</tr>';
                supportsHtml += '</thead><tbody>';
                
                var totalAmount = 0;
                data.approved_supports.forEach(function(item) {
                    var amount = parseFloat(item.amount) || 0;
                    totalAmount += amount;
                    
                    supportsHtml += '<tr>';
                    supportsHtml += '<td>' + (item.donor_number || '-') + '</td>';
                    supportsHtml += '<td>' + (item.first_name || '-') + '</td>';
                    supportsHtml += '<td>' + (item.last_name || '-') + '</td>';
                    supportsHtml += '<td class="text-end">' + amount.toFixed(2) + ' ש"ח</td>';
                    supportsHtml += '<td>' + (item.support_month || '-') + '</td>';
                    supportsHtml += '<td>' + (item.approved_at ? new Date(item.approved_at).toLocaleDateString('he-IL') : '-') + '</td>';
                    supportsHtml += '<td>' + (item.approved_by_name || '-') + '</td>';
                    supportsHtml += '</tr>';
                });
                
                supportsHtml += '</tbody>';
                supportsHtml += '<tfoot class="table-light">';
                supportsHtml += '<tr>';
                supportsHtml += '<td colspan="3"><strong>סה"כ:</strong></td>';
                supportsHtml += '<td class="text-end"><strong>' + totalAmount.toFixed(2) + ' ש"ח</strong></td>';
                supportsHtml += '<td colspan="3"></td>';
                supportsHtml += '</tr>';
                supportsHtml += '</tfoot>';
                supportsHtml += '</table>';
                supportsHtml += '</div>';
            } else {
                supportsHtml = '<div class="alert alert-info text-center" role="alert">';
                supportsHtml += '<i class="bi bi-info-circle me-2"></i>';
                supportsHtml += 'לא נמצאו תמיכות שאושרו עבור תורם זה';
                supportsHtml += '</div>';
            }
            $('#supportsContent').html(supportsHtml);
            
            // Load children data
            if (p && p.husband_id) {
                loadChildrenData(p.husband_id);
            } else {
                $('#childrenCountBadge').text('0');
                $('#childrenSummaryContent').html('<div class="text-center text-muted">לא נמצאו פרטי ילדים</div>');
            }
        },
        error: function() {
            alert('שגיאה בטעינת הפרטים');
        }
    });
}

// Load children data for a parent
function loadChildrenData(parentHusbandId) {
    $.ajax({
        url: 'children_api.php',
        method: 'GET',
        data: { 
            action: 'get_by_parent',
            parent_id: parentHusbandId
        },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.children) {
                const children = data.children;
                const summary = data.summary;
                
                // Update badge
                $('#childrenCountBadge').text(summary.total);
                
                // Build summary HTML
                let html = '<div class="mb-3">';
                html += '<h6><i class="bi bi-info-circle me-2"></i>סיכום</h6>';
                html += '<div class="summary-stats">';
                html += '<div class="stat-item">';
                html += '<span class="stat-label">בנים:</span>';
                html += '<span class="stat-value">' + summary.boys + '</span>';
                html += '</div>';
                html += '<div class="stat-item">';
                html += '<span class="stat-label">בנות:</span>';
                html += '<span class="stat-value">' + summary.girls + '</span>';
                html += '</div>';
                html += '<div class="stat-item">';
                html += '<span class="stat-label">נשואים:</span>';
                html += '<span class="stat-value">' + summary.married + '</span>';
                html += '</div>';
                html += '<div class="stat-item">';
                html += '<span class="stat-label">סה"כ:</span>';
                html += '<span class="stat-value">' + summary.total + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                
                if (children.length > 0) {
                    html += '<h6><i class="bi bi-list-ul me-2"></i>רשימת ילדים</h6>';
                    html += '<div class="children-list">';
                    
                    children.forEach(function(child) {
                        const statusClass = child.status === 'נשוי' ? 'married' : 'single';
                        html += '<div class="child-item">';
                        html += '<div class="child-info">';
                        html += '<div class="child-name">' + escapeHtml(child.child_name) + '</div>';
                        html += '<div class="child-details">';
                        html += escapeHtml(child.gender || '') + ' | ';
                        html += 'גיל: ' + (child.age || '-') + ' | ';
                        if (child.birth_month && child.birth_year) {
                            html += child.birth_month + ' ' + child.birth_year;
                        }
                        if (child.notes) {
                            html += ' | ' + escapeHtml(child.notes);
                        }
                        html += '</div>';
                        html += '</div>';
                        html += '<span class="child-status ' + statusClass + '">' + escapeHtml(child.status || 'רווק') + '</span>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                } else {
                    html += '<div class="text-center text-muted">אין ילדים</div>';
                }
                
                $('#childrenSummaryContent').html(html);
            } else {
                $('#childrenCountBadge').text('0');
                $('#childrenSummaryContent').html('<div class="text-center text-muted">אין ילדים</div>');
            }
        },
        error: function() {
            $('#childrenCountBadge').text('0');
            $('#childrenSummaryContent').html('<div class="text-center text-danger">שגיאה בטעינת פרטי ילדים</div>');
        }
    });
}

// Toggle edit mode in person details modal
function togglePersonDetailsEditMode(editMode) {
    if (editMode) {
        // Switch to edit mode
        $('#editPersonFromDetailsBtn').addClass('d-none');
        $('#savePersonFromDetailsBtn').removeClass('d-none');
        $('#cancelEditFromDetailsBtn').removeClass('d-none');
        
        // Convert text to inputs
        var personData = $('#basicInfoContent').data('person-data');
        if (!personData) return;
        
        var hebrewFields = {
            'amarchal': 'אמרכל',
            'gizbar': 'גזבר',
            'software_id': 'מזהה קופות',
            'phone_id': 'מזהה מלבושי כבוד',
            'donor_number': 'מס תורם',
            'chatan_harar': "חתן הר'ר",
            'family_name': 'משפחה',
            'first_name': 'שם',
            'name_for_mail': 'שם לדואר',
            'full_name': 'שם ומשפחה ביחד',
            'husband_id': 'תעודת זהות בעל',
            'wife_id': 'תעודת זהות אשה',
            'address': 'כתובת',
            'mail_to': 'דואר ל',
            'neighborhood': 'שכונה / אזור',
            'floor': 'קומה',
            'city': 'עיר',
            'phone': 'טלפון',
            'husband_mobile': 'נייד בעל',
            'wife_name': 'שם האשה',
            'wife_mobile': 'נייד אשה',
            'updated_email': 'כתובת מייל מעודכן',
            'husband_email': 'מייל בעל',
            'wife_email': 'מייל אשה',
            'receipts_to': 'קבלות ל',
            'alphon': 'אלפון',
            'send_messages': 'שליחת הודעות',
            'last_change': 'שינוי אחרון',
            'foreign_id': 'מזמדה מלבושי כבוד',
            'kavod_id': 'מזהה מלבושי כבוד'
        };
        
        var editHtml = '';
        var p = personData;
        
        // פרטים אישיים
        editHtml += '<div class="col-12"><h6 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>פרטים אישיים</h6></div>';
        
        var personalFields = [
            {key: 'family_name', label: 'משפחה'},
            {key: 'first_name', label: 'שם'},
            {key: 'name_for_mail', label: 'שם לדואר'},
            {key: 'software_id', label: 'מזהה קופות'},
            {key: 'phone_id', label: 'מזהה מלבושי כבוד'},
            {key: 'foreign_id', label: 'מזהה מלבושי כבוד'},
            {key: 'kavod_id', label: 'מזהה מלבושי כבוד'},
            {key: 'wife_name', label: 'שם האשה'},
            {key: 'husband_mobile', label: 'נייד בעל'},
            {key: 'wife_mobile', label: 'נייד אשה'},
            {key: 'phone', label: 'טלפון'},
            {key: 'husband_id', label: 'תעודת זהות בעל'},
            {key: 'wife_id', label: 'תעודת זהות אשה'},
            {key: 'address', label: 'כתובת'},
            {key: 'mail_to', label: 'דואר ל'},
            {key: 'neighborhood', label: 'שכונה / אזור'},
            {key: 'floor', label: 'קומה'},
            {key: 'city', label: 'עיר'},
            {key: 'updated_email', label: 'כתובת מייל מעודכן'},
            {key: 'husband_email', label: 'מייל בעל'},
            {key: 'wife_email', label: 'מייל אשה'}
        ];
        
        // Display editable personal fields
        personalFields.forEach(function(field) {
            editHtml += '<div class="col-md-6 mb-2">';
            editHtml += '<label class="form-label fw-bold">' + field.label + ':</label>';
            editHtml += '<input type="text" class="form-control form-control-sm" data-field="' + field.key + '" value="' + (p[field.key] || '') + '">';
            editHtml += '</div>';
        });
        
        // Add separator
        editHtml += '<div class="col-12"><hr class="my-3"></div>';
        
        // פרטים נוספים
        editHtml += '<div class="col-12"><h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>פרטים נוספים</h6></div>';
        
        // Display remaining editable fields
        var displayedKeys = personalFields.map(function(f) { return f.key; });
        displayedKeys.push('id', 'created_at');
        
        for (var key in p) {
            if (displayedKeys.indexOf(key) === -1 && p[key]) {
                var label = hebrewFields[key] || key;
                editHtml += '<div class="col-md-6 mb-2">';
                editHtml += '<label class="form-label fw-bold">' + label + ':</label>';
                editHtml += '<input type="text" class="form-control form-control-sm" data-field="' + key + '" value="' + (p[key] || '') + '">';
                editHtml += '</div>';
            }
        }
        
        $('#basicInfoContent').html(editHtml);
        
    } else {
        // Switch to view mode
        $('#editPersonFromDetailsBtn').removeClass('d-none');
        $('#savePersonFromDetailsBtn').addClass('d-none');
        $('#cancelEditFromDetailsBtn').addClass('d-none');
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}
