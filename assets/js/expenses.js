(function() {
    const dataEl = document.getElementById('expensesData');
    const expenseTypes = dataEl ? JSON.parse(dataEl.dataset.expenseTypes || '{}') : {};
    const deptRows = dataEl ? JSON.parse(dataEl.dataset.deptRows || '[]') : [];
    const categoryRows = dataEl ? JSON.parse(dataEl.dataset.categoryRows || '[]') : [];
    const typeRows = dataEl ? JSON.parse(dataEl.dataset.typeRows || '[]') : [];
    const fromAccountRows = dataEl ? JSON.parse(dataEl.dataset.fromAccountRows || '[]') : [];
    const fixedVal = dataEl ? Number(dataEl.dataset.fixed || 0) : 0;
    const regularVal = dataEl ? Number(dataEl.dataset.regular || 0) : 0;

    function showMessageModal() {
        const messageEl = document.getElementById('messageModal');
        if (messageEl && window.bootstrap) {
            const messageModal = new bootstrap.Modal(messageEl);
            messageModal.show();
        }
    }

    function setModal(type) {
        const actionEl = document.getElementById('action');
        if (actionEl) {
            actionEl.value = 'add_' + type;
        }
    }

    function setImportAction(action) {
        const importEl = document.getElementById('import_action');
        if (importEl) {
            importEl.value = action;
        }
    }

    function editExpense(button) {
        const type = button.getAttribute('data-type');
        const actionEl = document.getElementById('edit_action');
        if (actionEl) actionEl.value = 'edit_' + type;
        const idEl = document.getElementById('edit_id');
        if (idEl) idEl.value = button.getAttribute('data-id');
        const dateEl = document.getElementById('edit_date');
        if (dateEl) dateEl.value = button.getAttribute('data-date');
        const forWhatEl = document.getElementById('edit_for_what');
        if (forWhatEl) forWhatEl.value = button.getAttribute('data-for_what');
        const storeEl = document.getElementById('edit_store');
        if (storeEl) storeEl.value = button.getAttribute('data-store');
        const amountEl = document.getElementById('edit_amount');
        if (amountEl) amountEl.value = button.getAttribute('data-amount');
        const deptEl = document.getElementById('edit_department');
        if (deptEl) deptEl.value = button.getAttribute('data-department');
        const catEl = document.getElementById('edit_category');
        if (catEl) catEl.value = button.getAttribute('data-category');
        const paidEl = document.getElementById('edit_paid_by');
        if (paidEl) paidEl.value = button.getAttribute('data-paid_by');
        const fromEl = document.getElementById('edit_from_account');
        if (fromEl) fromEl.value = button.getAttribute('data-from_account');

        const invoicePath = button.getAttribute('data-invoice_copy') || '';
        const invoiceEl = document.getElementById('existing_invoice_copy');
        if (invoiceEl) invoiceEl.value = invoicePath;
        const preview = document.getElementById('edit_invoice_copy_preview');
        if (preview) {
            if (invoicePath) {
                preview.innerHTML = '<div class="d-flex align-items-center gap-2">' +
                    '<a href="' + invoicePath + '" target="_blank" rel="noopener">צפה בקובץ קיים</a>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" data-clear-invoice>מחק</button>' +
                    '</div>';
            } else {
                preview.innerHTML = '';
            }
        }
        const delChk = document.getElementById('delete_invoice_copy');
        if (delChk) delChk.checked = false;

        updateEditExpenseType();
        const typeEl = document.getElementById('edit_expense_type');
        if (typeEl) typeEl.value = button.getAttribute('data-expense_type');
    }

    function clearExistingInvoice() {
        const delChk = document.getElementById('delete_invoice_copy');
        if (delChk) delChk.checked = true;
        const preview = document.getElementById('edit_invoice_copy_preview');
        if (preview) preview.innerHTML = '<span class="text-muted">לא מצורף קובץ</span>';
        const fileInput = document.getElementById('edit_invoice_copy_file');
        if (fileInput) fileInput.value = '';
    }

    function updateExpenseType() {
        const categoryEl = document.getElementById('category');
        const expenseTypeSelect = document.getElementById('expense_type');
        if (!categoryEl || !expenseTypeSelect) return;
        const category = categoryEl.value;
        expenseTypeSelect.innerHTML = '<option value="">בחר סוג הוצאה</option>';
        if (expenseTypes[category]) {
            expenseTypes[category].forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.text = type;
                expenseTypeSelect.appendChild(option);
            });
        }
    }

    function updateEditExpenseType() {
        const categoryEl = document.getElementById('edit_category');
        const expenseTypeSelect = document.getElementById('edit_expense_type');
        if (!categoryEl || !expenseTypeSelect) return;
        const category = categoryEl.value;
        expenseTypeSelect.innerHTML = '<option value="">בחר סוג הוצאה</option>';
        if (expenseTypes[category]) {
            expenseTypes[category].forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.text = type;
                expenseTypeSelect.appendChild(option);
            });
        }
    }

    function initDataTables() {
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            const dtOpts = {
                language: {
                    search: 'חיפוש:',
                    lengthMenu: 'הצג _MENU_ רשומות בעמוד',
                    zeroRecords: 'לא נמצאו רשומות מתאימות',
                    info: 'מציג _START_ עד _END_ מתוך _TOTAL_ רשומות',
                    infoEmpty: 'אין רשומות להצגה',
                    infoFiltered: '(מסונן מתוך _MAX_ רשומות)',
                    paginate: { first: 'ראשון', last: 'אחרון', next: 'הבא', previous: 'קודם' }
                },
                order: [[0, 'desc']],
                pageLength: 25
            };

            if (jQuery('#combinedTable').length) {
                jQuery('#combinedTable').DataTable(dtOpts);
            }
            if (jQuery('#fixedTable').length) {
                jQuery('#fixedTable').DataTable(dtOpts);
            }
            if (jQuery('#regularTable').length) {
                jQuery('#regularTable').DataTable(dtOpts);
            }
        }
    }

    function bindFormTabPreserve() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const activePane = document.querySelector('.tab-pane.show.active');
                if (activePane) {
                    let tabInput = form.querySelector('input[name="current_tab"]');
                    if (!tabInput) {
                        tabInput = document.createElement('input');
                        tabInput.type = 'hidden';
                        tabInput.name = 'current_tab';
                        form.appendChild(tabInput);
                    }
                    tabInput.value = activePane.id;
                }
            });
        });

        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function () {
                const activePane = document.querySelector('.tab-pane.show.active');
                const tabInput = document.getElementById('filter_tab');
                if (activePane && tabInput) {
                    tabInput.value = activePane.id;
                }
            });
        }
    }

    function bindExportSorting() {
        const headerToCol = {
            'תאריך': 'date',
            'עבור': 'for_what',
            'חנות': 'store',
            'סכום': 'amount',
            'אגף': 'department',
            'קטגוריה': 'category',
            'סוג הוצאה': 'expense_type',
            "שולם ע'י": 'paid_by',
            'יצא מ': 'from_account',
            'העתק חשבונית': 'invoice_copy',
            'מקור': 'date'
        };

        function setOrderInputs(form) {
            const tableId = form.getAttribute('data-table-id');
            const orderByInput = form.querySelector('input[name="order_by"]');
            const orderDirInput = form.querySelector('input[name="order_dir"]');
            const searchInput = form.querySelector('input[name="search_term"]');
            let col = 'date';
            let dir = 'desc';
            let search = '';
            if (tableId) {
                const tableEl = document.getElementById(tableId);
                if (tableEl && window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
                    try {
                        const dt = jQuery(tableEl).DataTable();
                        const order = dt.order();
                        search = (typeof dt.search === 'function') ? dt.search() : '';
                        if (order && order.length) {
                            const idx = order[0][0];
                            dir = (order[0][1] || 'desc').toLowerCase();
                            const ths = tableEl.querySelectorAll('thead th');
                            const headerText = ths[idx] ? ths[idx].textContent.trim() : '';
                            if (headerText && headerToCol[headerText]) {
                                col = headerToCol[headerText];
                            }
                        }
                    } catch (e) {
                        // ignore
                    }
                }
            }
            if (orderByInput) orderByInput.value = col;
            if (orderDirInput) orderDirInput.value = (dir === 'asc' ? 'asc' : 'desc');
            if (searchInput) searchInput.value = search;
        }

        document.querySelectorAll('form[data-table-id]')
            .forEach(form => {
                form.addEventListener('submit', function() { setOrderInputs(form); });
            });
    }

    function renderPie(canvasId, rows, titleText) {
        const el = document.getElementById(canvasId);
        if (!el || !Array.isArray(rows) || !rows.length) return;
        const labels = rows.map(r => (r && r[0]) ? String(r[0] || 'לא מוגדר') : 'לא מוגדר');
        const data = rows.map(r => Number(r && r[1] ? r[1] : 0));
        const colors = [
            '#4dc9f6','#f67019','#f53794','#537bc4','#acc236','#166a8f',
            '#00a950','#58595b','#8549ba','#ffcd56','#36a2eb','#ff9f40'
        ];
        const bg = labels.map((_, i) => colors[i % colors.length]);
        new Chart(el, {
            type: 'pie',
            data: { labels, datasets: [{ data, backgroundColor: bg }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' }, title: { display: !!titleText, text: titleText } } }
        });
    }

    function renderDonut(canvasId, labels, values, titleText) {
        const el = document.getElementById(canvasId);
        if (!el) return;
        const data = Array.isArray(values) ? values.map(v => Number(v || 0)) : [];
        const colors = ['#36a2eb', '#ff6384'];
        new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors }] },
            options: { cutout: '55%', responsive: true, plugins: { legend: { position: 'bottom' }, title: { display: !!titleText, text: titleText } } }
        });
    }

    function renderCharts() {
        if (typeof Chart === 'undefined') return;
        renderPie('deptChart', deptRows, 'התפלגות אגפים');
        renderPie('categoryChart', categoryRows, 'התפלגות קטגוריות');
        renderPie('typeChart', typeRows, 'התפלגות סוגי הוצאה');
        renderPie('fromAccountChart', fromAccountRows, 'התפלגות מקורות תשלום');
        renderDonut('overallChart', ['קבועה','רגילה'], [fixedVal, regularVal], 'התפלגות כללית');
    }

    document.addEventListener('click', function(event) {
        const modalBtn = event.target.closest('[data-modal-type]');
        if (modalBtn) {
            event.preventDefault();
            setModal(modalBtn.getAttribute('data-modal-type'));
        }

        const importBtn = event.target.closest('[data-import-action]');
        if (importBtn) {
            setImportAction(importBtn.getAttribute('data-import-action'));
        }

        const editBtn = event.target.closest('.js-edit-expense');
        if (editBtn) {
            editExpense(editBtn);
        }

        const confirmBtn = event.target.closest('[data-confirm]');
        if (confirmBtn) {
            const msg = confirmBtn.getAttribute('data-confirm') || '';
            if (msg && !window.confirm(msg)) {
                event.preventDefault();
                event.stopPropagation();
            }
        }

        const clearBtn = event.target.closest('[data-clear-invoice]');
        if (clearBtn) {
            event.preventDefault();
            clearExistingInvoice();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const categoryEl = document.getElementById('category');
        if (categoryEl) {
            categoryEl.addEventListener('change', updateExpenseType);
            updateExpenseType();
        }
        const editCategoryEl = document.getElementById('edit_category');
        if (editCategoryEl) {
            editCategoryEl.addEventListener('change', updateEditExpenseType);
        }
        showMessageModal();
        initDataTables();
        bindFormTabPreserve();
        bindExportSorting();
        renderCharts();
    });

    window.setModal = setModal;
    window.setImportAction = setImportAction;
    window.editExpense = editExpense;
    window.clearExistingInvoice = clearExistingInvoice;
    window.updateExpenseType = updateExpenseType;
    window.updateEditExpenseType = updateEditExpenseType;
})();
