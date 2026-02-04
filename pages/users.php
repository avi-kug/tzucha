<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once '../config/auth.php';
$navItems = require __DIR__ . '/../config/nav.php';

// Ensure access
if (!auth_has_permission('users')) {
    http_response_code(403);
    exit('אין הרשאה');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if (!csrf_validate()) {
            throw new Exception('פג תוקף הטופס, נסה שוב.');
        }
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'viewer';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $isAdmin = $role === 'admin' ? 1 : 0;
            $permissions = $_POST['permissions'] ?? [];

            $policyError = '';

            if ($username === '' || $email === '' || $password === '') {
                $message = 'יש למלא את כל השדות החובה.';
            } elseif (!password_policy_validate($password, $policyError)) {
                $message = $policyError;
            } else {
                $hash = hash_password($password);
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, is_active, is_admin, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$username, $email, $hash, $role, $isActive, $isAdmin]);
                $userId = (int)$pdo->lastInsertId();

                $permStmt = $pdo->prepare('INSERT INTO user_permissions (user_id, permission_key) VALUES (?, ?)');
                foreach ($permissions as $perm) {
                    $permStmt->execute([$userId, $perm]);
                }
                $message = 'משתמש נוסף.';
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'viewer';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $isAdmin = $role === 'admin' ? 1 : 0;
            $permissions = $_POST['permissions'] ?? [];

            $policyError = '';

            if ($id <= 0 || $username === '' || $email === '') {
                $message = 'נתונים חסרים.';
            } else {
                if ($password !== '') {
                    if (!password_policy_validate($password, $policyError)) {
                        $message = $policyError;
                    } else {
                        $hash = hash_password($password);
                        $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, role = ?, is_active = ?, is_admin = ? WHERE id = ?');
                        $stmt->execute([$username, $email, $hash, $role, $isActive, $isAdmin, $id]);
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ?, is_active = ?, is_admin = ? WHERE id = ?');
                    $stmt->execute([$username, $email, $role, $isActive, $isAdmin, $id]);
                }

                $pdo->prepare('DELETE FROM user_permissions WHERE user_id = ?')->execute([$id]);
                $permStmt = $pdo->prepare('INSERT INTO user_permissions (user_id, permission_key) VALUES (?, ?)');
                foreach ($permissions as $perm) {
                    $permStmt->execute([$id, $perm]);
                }
                $message = 'משתמש עודכן.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0 && $id !== (int)($_SESSION['user_id'] ?? 0)) {
                $pdo->prepare('DELETE FROM user_permissions WHERE user_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                $message = 'משתמש נמחק.';
            } else {
                $message = 'לא ניתן למחוק משתמש זה.';
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $message = 'שם משתמש כבר קיים. בחר שם אחר.';
        } else {
            $message = 'שגיאה בשמירה. בדוק נתונים ונסה שוב.';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

$users = $pdo->query('SELECT id, username, email, role, is_active, is_admin, created_at FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$permRows = $pdo->query('SELECT user_id, permission_key FROM user_permissions')->fetchAll(PDO::FETCH_ASSOC);
$attempts = $pdo->query('SELECT username, ip_address, attempted_at, success, geo_city, geo_country FROM login_attempts ORDER BY attempted_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$permMap = [];
foreach ($permRows as $row) {
    $permMap[$row['user_id']][] = $row['permission_key'];
}

include '../templates/header.php';
?>

<h2>ניהול משתמשים</h2>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#usersTab" type="button" role="tab" aria-controls="usersTab" aria-selected="true">משתמשים</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="attempts-tab" data-bs-toggle="tab" data-bs-target="#attemptsTab" type="button" role="tab" aria-controls="attemptsTab" aria-selected="false">ניסיונות כניסה</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="usersTab" role="tabpanel" aria-labelledby="users-tab">

<div class="card">
    <div class="card-body">
        <button type="button" class="btn btn-brand mb-3" id="addUserBtn">הוסף משתמש</button>
        <div class="table-responsive">
            <table id="usersTable" class="table table-striped mb-0" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>שם משתמש</th>
                        <th>מייל</th>
                        <th>פעיל</th>
                        <th>תפקיד</th>
                        <th>הרשאות</th>
                        <th>נוצר</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $uPerms = $permMap[$u['id']] ?? [];
                        $roleKey = $u['role'] ?? ((int)$u['is_admin'] === 1 ? 'admin' : 'viewer');
                        $roleLabels = ['admin' => 'מנהל מערכת', 'manager' => 'מנהל', 'viewer' => 'צופה'];
                        $roleLabel = $roleLabels[$roleKey] ?? $roleKey;
                    ?>
                        <tr>
                            <td><?php echo (int)$u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$u['is_active'] === 1 ? 'כן' : 'לא'; ?></td>
                            <td><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $uPerms), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($u['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-user-btn"
                                    data-id="<?php echo (int)$u['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-active="<?php echo (int)$u['is_active']; ?>"
                                    data-role="<?php echo htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-perms="<?php echo htmlspecialchars(json_encode($uPerms, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                                >ערוך</button>
                                <form method="post" style="display:inline">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('למחוק משתמש?')">מחק</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    </div>
    <div class="tab-pane fade" id="attemptsTab" role="tabpanel" aria-labelledby="attempts-tab">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">ניסיונות כניסה אחרונים</h5>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>שם משתמש</th>
                                <th>IP</th>
                                <th>זמן</th>
                                <th>סטטוס</th>
                                <th>מיקום</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attempts)): ?>
                                <tr><td colspan="5">אין ניסיונות</td></tr>
                            <?php else: ?>
                                <?php foreach ($attempts as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($a['ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($a['attempted_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int)$a['success'] === 1 ? 'הצלחה' : 'כשלון'; ?></td>
                                        <td><?php echo htmlspecialchars(trim(($a['geo_city'] ?? '') . ' ' . ($a['geo_country'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">הוסף משתמש</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="userForm">
                <div class="modal-body">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="create" id="userFormAction">
                    <input type="hidden" name="id" id="userId">
                    <div class="mb-3">
                        <label class="form-label">שם משתמש</label>
                        <input type="text" class="form-control" name="username" id="userUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">מייל</label>
                        <input type="email" class="form-control" name="email" id="userEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">סיסמה (רק בהוספה/עדכון)</label>
                        <input type="password" class="form-control" name="password" id="userPassword">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="userActive" checked>
                        <label class="form-check-label" for="userActive">פעיל</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="userRole">תפקיד</label>
                        <select class="form-select" name="role" id="userRole">
                            <option value="admin">מנהל מערכת</option>
                            <option value="manager">מנהל</option>
                            <option value="viewer" selected>צופה</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">הרשאות</label>
                        <?php foreach ($navItems as $item): ?>
                            <div class="form-check">
                                <input class="form-check-input perm-check" type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8'); ?>" id="perm_<?php echo htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8'); ?>">
                                <label class="form-check-label" for="perm_<?php echo htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-primary">שמור</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    function initUsersModal(){
        if (!window.bootstrap || !document.getElementById('userModal')) { return false; }
        const modalEl = document.getElementById('userModal');
        const modal = new bootstrap.Modal(modalEl);

        const addBtn = document.getElementById('addUserBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function(){
                document.getElementById('userModalTitle').textContent = 'הוסף משתמש';
                document.getElementById('userFormAction').value = 'create';
                document.getElementById('userId').value = '';
                document.getElementById('userUsername').value = '';
                document.getElementById('userEmail').value = '';
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = true;
                document.getElementById('userRole').value = 'viewer';
                document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
                modal.show();
            });
        }

        document.querySelectorAll('.edit-user-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                document.getElementById('userModalTitle').textContent = 'ערוך משתמש';
                document.getElementById('userFormAction').value = 'update';
                document.getElementById('userId').value = btn.dataset.id;
                document.getElementById('userUsername').value = btn.dataset.username;
                document.getElementById('userEmail').value = btn.dataset.email;
                document.getElementById('userPassword').value = '';
                document.getElementById('userActive').checked = btn.dataset.active === '1';
                document.getElementById('userRole').value = btn.dataset.role || 'viewer';
                document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
                try {
                    const perms = JSON.parse(btn.dataset.perms || '[]');
                    perms.forEach(p => {
                        const el = document.querySelector('input[name="permissions[]"][value="' + p + '"]');
                        if (el) { el.checked = true; }
                    });
                } catch (e) {}
                modal.show();
            });
        });
        return true;
    }

    function tryInit(attempts){
        if (initUsersModal()) { return; }
        if ((attempts || 0) < 50) {
            setTimeout(function(){ tryInit((attempts || 0) + 1); }, 100);
        }
    }
    tryInit(0);
})();
</script>

<?php include '../templates/footer.php'; ?>
