(function () {
    'use strict';

    // ── Tab switching ──────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.getAttribute('data-tab');
            // Update URL without reload
            var url = new URL(window.location);
            url.searchParams.set('tab', tab);
            history.replaceState(null, '', url);
            // Toggle active tab button
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            // Toggle panels
            document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
            var panel = document.getElementById(tab + '-tab');
            if (panel) panel.classList.add('active');
            // Update hidden filter tab
            var filterTab = document.getElementById('filter_tab');
            if (filterTab) filterTab.value = tab;
            // Cookie
            document.cookie = 'so_tab=' + tab + ';path=/;max-age=31536000';
        });
    });

    // ── DataTables init ────────────────────────────────────────────────
    function initDT() {
        if (!window.jQuery || !jQuery.fn.DataTable) return;
        var dtOpts = {
            language: {
                search: 'חיפוש:',
                lengthMenu: 'הצג _MENU_ רשומות',
                zeroRecords: 'לא נמצאו רשומות',
                info: '_START_–_END_ מתוך _TOTAL_',
                infoEmpty: 'אין רשומות',
                infoFiltered: '(מסונן מ-_MAX_)',
                paginate: { first: 'ראשון', last: 'אחרון', next: 'הבא', previous: 'קודם' }
            },
            pageLength: 25,
            order: [[0, 'desc']],
            dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rt<"d-flex justify-content-between align-items-center mt-2"ip>'
        };
        ['#koachTable', '#achimTable', '#soAlphonTable'].forEach(function (sel) {
            var el = document.querySelector(sel);
            if (el && !jQuery.fn.DataTable.isDataTable(el)) {
                jQuery(el).DataTable(Object.assign({}, dtOpts));
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDT);
    } else {
        initDT();
    }

    // ── Message modal ──────────────────────────────────────────────────
    var msgEl = document.getElementById('messageModal');
    if (msgEl && window.bootstrap) {
        new bootstrap.Modal(msgEl).show();
    }

    // ── Add modal – set action based on tab ────────────────────────────
    var addModal = document.getElementById('addModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function (e) {
            var soType = e.relatedTarget ? e.relatedTarget.getAttribute('data-so-type') : 'koach';
            document.getElementById('add_action').value = 'add_' + soType;
        });
    }

    // ── Import modal – set action ──────────────────────────────────────
    var importModal = document.getElementById('importModal');
    if (importModal) {
        importModal.addEventListener('show.bs.modal', function (e) {
            var action = e.relatedTarget ? e.relatedTarget.getAttribute('data-import-action') : 'import_koach';
            document.getElementById('import_action').value = action;
        });
    }

    // ── Edit modal – populate fields ───────────────────────────────────
    document.querySelectorAll('.js-edit-so').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_action').value = 'edit_' + btn.getAttribute('data-so-type');
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_full_name').value = btn.getAttribute('data-full_name');
            document.getElementById('edit_donation_date').value = btn.getAttribute('data-donation_date');
            document.getElementById('edit_amount').value = btn.getAttribute('data-amount');
            document.getElementById('edit_last4').value = btn.getAttribute('data-last4');
            document.getElementById('edit_method').value = btn.getAttribute('data-method');
            document.getElementById('edit_notes').value = btn.getAttribute('data-notes');
        });
    });

    // ── Confirm delete ─────────────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(btn.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ── Person ID from datalist ────────────────────────────────────────
    var nameInput = addModal ? addModal.querySelector('input[name="full_name"]') : null;
    if (nameInput) {
        nameInput.addEventListener('change', function () {
            var val = nameInput.value;
            var opt = document.querySelector('#peopleDatalist option[value="' + CSS.escape(val) + '"]');
            var hiddenId = document.getElementById('add_person_id');
            hiddenId.value = opt ? opt.getAttribute('data-id') : '';
        });
    }

    // ── History modal ──────────────────────────────────────────────────
    document.querySelectorAll('.js-view-history').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var personId = btn.getAttribute('data-person-id');
            var personName = btn.getAttribute('data-person-name');
            document.getElementById('historyPersonName').textContent = personName;

            var koachDiv = document.getElementById('historyKoach');
            var achimDiv = document.getElementById('historyAchim');
            var koachTotal = document.getElementById('historyKoachTotal');
            var achimTotal = document.getElementById('historyAchimTotal');

            koachDiv.innerHTML = '<div class="text-center text-muted">טוען...</div>';
            achimDiv.innerHTML = '<div class="text-center text-muted">טוען...</div>';
            koachTotal.textContent = '';
            achimTotal.textContent = '';

            var modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();

            fetch(window.soHistoryUrl + '?action=history&person_id=' + personId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        koachDiv.innerHTML = '<div class="text-danger">שגיאה</div>';
                        achimDiv.innerHTML = '<div class="text-danger">שגיאה</div>';
                        return;
                    }
                    koachDiv.innerHTML = buildHistoryTable(data.koach.months);
                    koachTotal.innerHTML = '<strong>סה"כ: ₪' + Number(data.koach.grand_total).toLocaleString('he-IL', { minimumFractionDigits: 2 }) + '</strong>';

                    achimDiv.innerHTML = buildHistoryTable(data.achim.months);
                    achimTotal.innerHTML = '<strong>סה"כ: ₪' + Number(data.achim.grand_total).toLocaleString('he-IL', { minimumFractionDigits: 2 }) + '</strong>';
                })
                .catch(function () {
                    koachDiv.innerHTML = '<div class="text-danger">שגיאה בטעינה</div>';
                    achimDiv.innerHTML = '<div class="text-danger">שגיאה בטעינה</div>';
                });
        });
    });

    function buildHistoryTable(months) {
        if (!months || months.length === 0) {
            return '<div class="text-muted text-center">אין תרומות</div>';
        }
        var html = '<table class="table table-sm table-striped">';
        html += '<thead><tr><th>חודש</th><th>סכום</th><th>מס\' תרומות</th></tr></thead><tbody>';
        months.forEach(function (m) {
            html += '<tr><td>' + m.month_label + '</td><td>₪' + Number(m.total).toLocaleString('he-IL', { minimumFractionDigits: 2 }) + '</td><td>' + m.cnt + '</td></tr>';
        });
        html += '</tbody></table>';
        return html;
    }
})();
