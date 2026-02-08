(function () {
    function bindDeleteConfirm() {
        document.querySelectorAll('.delete-user-btn').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                var message = btn.getAttribute('data-confirm') || 'למחוק משתמש?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function initUsersModal() {
        if (!window.bootstrap || !document.getElementById('userModal')) {
            return false;
        }
        var modalEl = document.getElementById('userModal');
        var modal = new bootstrap.Modal(modalEl);

        var addBtn = document.getElementById('addUserBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                document.getElementById('userModalTitle').textContent = 'הוסף משתמש';
                document.getElementById('userFormAction').value = 'create';
                document.getElementById('userId').value = '';
                document.getElementById('userUsername').value = '';
                document.getElementById('userEmail').value = '';
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = true;
                document.getElementById('userRole').value = 'viewer';
                document.querySelectorAll('.perm-check').forEach(function (cb) {
                    cb.checked = false;
                });
                modal.show();
            });
        }

        document.querySelectorAll('.edit-user-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('userModalTitle').textContent = 'ערוך משתמש';
                document.getElementById('userFormAction').value = 'update';
                document.getElementById('userId').value = btn.dataset.id;
                document.getElementById('userUsername').value = btn.dataset.username;
                document.getElementById('userEmail').value = btn.dataset.email;
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = btn.dataset.active === '1';
                document.getElementById('userRole').value = btn.dataset.role || 'viewer';
                document.querySelectorAll('.perm-check').forEach(function (cb) {
                    cb.checked = false;
                });
                try {
                    var perms = JSON.parse(btn.dataset.perms || '[]');
                    perms.forEach(function (p) {
                        var el = document.querySelector('input[name="permissions[]"][value="' + p + '"]');
                        if (el) {
                            el.checked = true;
                        }
                    });
                } catch (e) {
                    // Ignore invalid perms data.
                }
                modal.show();
            });
        });

        bindDeleteConfirm();
        return true;
    }

    function tryInit(attempts) {
        if (initUsersModal()) {
            return;
        }
        if ((attempts || 0) < 50) {
            setTimeout(function () {
                tryInit((attempts || 0) + 1);
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        tryInit(0);
    });
})();
