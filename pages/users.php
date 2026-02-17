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

$users = [];
$permRows = [];
$attempts = [];

try {
    $users = $pdo->query('SELECT id, username, email, role, is_active, is_admin, created_at FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error fetching users: ' . $e->getMessage());
    $users = [];
}

try {
    $permRows = $pdo->query('SELECT user_id, permission_key FROM user_permissions')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error fetching permissions: ' . $e->getMessage());
    $permRows = [];
}

// Safely query login attempts with error handling
try {
    $result = $pdo->query('SELECT username, ip_address, attempted_at, success, geo_city, geo_country FROM login_attempts ORDER BY attempted_at DESC LIMIT 50');
    if ($result) {
        $attempts = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Error fetching login attempts: ' . $e->getMessage());
    $attempts = [];
}

$permMap = [];
foreach ($permRows as $row) {
    $permMap[$row['user_id']][] = $row['permission_key'];
}

include '../templates/header.php';
?>
<link rel="stylesheet" href="../assets/css/people.css">

<h2>ניהול משתמשים</h2>
<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="tabs-nav">
    <button class="tab-btn active" data-tab="users">משתמשים</button>
    <button class="tab-btn" data-tab="attempts">ניסיונות כניסה</button>
    <button class="tab-btn" data-tab="security-logs">לוגי אבטחה</button>
    <?php if (auth_is_admin()): ?>
    <button class="tab-btn" data-tab="system-monitor">ניטור מערכת</button>
    <?php endif; ?>
</div>

<div class="tab-panel active" id="users-tab">
    <div id="users">

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
                                    <button type="submit" class="btn btn-sm btn-danger delete-user-btn" data-confirm="למחוק משתמש?">מחק</button>
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
</div>

<div class="tab-panel" id="attempts-tab">
    <div id="attempts">
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

<div class="tab-panel" id="security-logs-tab">
    <div id="security-logs">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">לוגי אבטחה</h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">סינון לפי סוג אירוע:</label>
                        <select id="eventTypeFilter" class="form-select">
                            <option value="">הכל</option>
                            <option value="LOGIN_SUCCESS">כניסה מוצלחת</option>
                            <option value="LOGIN_SUCCESS_SAME_IP_TODAY">כניסה מאותו IP</option>
                            <option value="LOGIN_FAILED">כניסה נכשלה</option>
                            <option value="LOGIN_RATE_LIMIT">הגבלת קצב</option>
                            <option value="LOGIN_OTP_SENT">OTP נשלח</option>
                            <option value="LOGIN_OTP_VERIFY_FAILED">OTP נכשל</option>
                            <option value="LOGIN_INACTIVE_USER">משתמש לא פעיל</option>
                            <option value="LOGOUT">יציאה</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">חיפוש (משתמש/IP):</label>
                        <input type="text" id="logSearch" class="form-control" placeholder="הקלד לחיפוש...">
                    </div>
                </div>

                <div id="logStats" class="mb-3 p-3 bg-light rounded" style="display: none;">
                    <div class="row">
                        <div class="col-md-3"><strong>סה"כ אירועים:</strong> <span id="statTotal">0</span></div>
                        <div class="col-md-3"><strong>כניסות מוצלחות:</strong> <span id="statSuccess">0</span></div>
                        <div class="col-md-3"><strong>כניסות נכשלות:</strong> <span id="statFailed">0</span></div>
                        <div class="col-md-3"><strong>הגבלות קצב:</strong> <span id="statRateLimit">0</span></div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0" id="securityLogsTable">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="width: 15%;">זמן</th>
                                <th style="width: 15%;">משתמש</th>
                                <th style="width: 12%;">IP</th>
                                <th style="width: 18%;">סוג אירוע</th>
                                <th style="width: 40%;">פרטים</th>
                            </tr>
                        </thead>
                        <tbody id="securityLogsBody">
                            <tr><td colspan="5" class="text-center">טוען לוגים...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (auth_is_admin()): ?>
<div class="tab-panel" id="system-monitor-tab">
    <div id="system-monitor">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">🖥️ ניטור מערכת בזמן אמת</h5>
                    <div>
                        <span class="badge bg-success" id="statusBadge">פעיל</span>
                        <button class="btn btn-sm btn-primary" onclick="refreshMonitoring()">
                            <i class="bi bi-arrow-clockwise"></i> רענן
                        </button>
                    </div>
                </div>
                <small class="text-muted">עודכן: <span id="lastMonitorUpdate">-</span></small>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-database fs-1 text-primary"></i>
                        <h6 class="mt-2">מסד נתונים</h6>
                        <div class="fs-3" id="dbStatus">-</div>
                        <small class="text-muted" id="dbDetails">בודק...</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-hdd fs-1 text-success"></i>
                        <h6 class="mt-2">מקום בדיסק</h6>
                        <div class="fs-3" id="diskSpace">-</div>
                        <small class="text-muted" id="diskDetails">בודק...</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-memory fs-1 text-info"></i>
                        <h6 class="mt-2">זיכרון</h6>
                        <div class="fs-3" id="memoryUsage">-</div>
                        <small class="text-muted" id="memoryDetails">בודק...</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-activity fs-1 text-warning"></i>
                        <h6 class="mt-2">פעילות</h6>
                        <div class="fs-3" id="activityCount">-</div>
                        <small class="text-muted">כניסות בשעה</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Checks -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">📊 בדיקות מערכת</h5>
                <div class="row" id="healthChecks">
                    <div class="col-12 text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">טוען...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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

<?php $jsV = @filemtime(__DIR__ . '/../assets/js/users.js') ?: '20260209'; ?>
<script src="../assets/js/users.js?v=<?php echo $jsV; ?>"></script>

<?php include '../templates/footer.php'; ?>
