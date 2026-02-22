<?php
// Security: Only show errors in development
$isDevelopment = (getenv('ENVIRONMENT') === 'development' || 
                  (isset($_SERVER['SERVER_NAME']) && 
                   (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || 
                    strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false)));

if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once '../config/db.php';
require_once '../vendor/autoload.php';
require_once '../config/auth.php';

// Security: Require authentication and permissions
auth_require_login($pdo);
auth_require_permission('standing_orders');

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Normalise imported Excel date
function soNormalizeDate($value) {
    if ($value === null || $value === '') return null;
    if (is_numeric($value)) {
        try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
    $value = trim((string)$value);
    $normalized = str_replace(['.', '-'], '/', $value);
    $parts = explode('/', $normalized);
    if (count($parts) === 3) {
        if (strlen($parts[0]) === 4) { $y=(int)$parts[0]; $m=(int)$parts[1]; $d=(int)$parts[2]; }
        else { $d=(int)$parts[0]; $m=(int)$parts[1]; $y=(int)$parts[2]; }
        if ($y>0 && $m>=1 && $m<=12 && $d>=1 && $d<=31) return sprintf('%04d-%02d-%02d',$y,$m,$d);
    }
    $ts = strtotime($value);
    return $ts !== false ? date('Y-m-d', $ts) : null;
}

// Validate Excel file upload
function validateExcelUpload($file) {
    $allowedExt = ['xlsx','xls'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $name = $file['name'] ?? '';
    $tmp = $file['tmp_name'] ?? '';
    $size = $file['size'] ?? 0;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('מותר להעלות רק קבצי Excel (.xlsx/.xls).');
    }
    if ($size > $maxSize) {
        throw new Exception('הקובץ גדול מדי (מקסימום 10MB).');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowedMime = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/octet-stream'
    ];
    if (!in_array($mime, $allowedMime, true)) {
        throw new Exception('סוג הקובץ אינו נתמך.');
    }
}

// ── POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_validate()) {
        $_SESSION['message'] = 'פג תוקף הטופס, נסה שוב.';
        header('Location: standing_orders.php');
        exit;
    }
    $action = $_POST['action'];

    // ── ADD ────────────────────────────────────────────────────────────
    if ($action === 'add_koach' || $action === 'add_achim') {
        // Whitelist validation for SQL injection prevention
        $allowedTables = ['standing_orders_koach' => 'standing_orders_koach', 'standing_orders_achim' => 'standing_orders_achim'];
        $table = $action === 'add_koach' ? 'standing_orders_koach' : 'standing_orders_achim';
        if (!isset($allowedTables[$table])) {
            die('Invalid table');
        }
        $table = $allowedTables[$table];
        $stmt = $pdo->prepare("INSERT INTO $table (donation_date, full_name, amount, last4, method, notes, person_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['donation_date'] ?? null,
            $_POST['full_name'] ?? '',
            $_POST['amount'] ?? 0,
            $_POST['last4'] ?? '',
            $_POST['method'] ?? 'אשראי',
            $_POST['notes'] ?? '',
            $_POST['person_id'] ?: null,
        ]);
        $tab = $action === 'add_koach' ? 'koach' : 'achim';
        header('Location: standing_orders.php?tab='.$tab);
        exit;
    }

    // ── DELETE ─────────────────────────────────────────────────────────
    if ($action === 'delete_koach' || $action === 'delete_achim') {
        // Whitelist validation for SQL injection prevention
        $allowedTables = ['standing_orders_koach' => 'standing_orders_koach', 'standing_orders_achim' => 'standing_orders_achim'];
        $table = $action === 'delete_koach' ? 'standing_orders_koach' : 'standing_orders_achim';
        if (!isset($allowedTables[$table])) {
            die('Invalid table');
        }
        $table = $allowedTables[$table];
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['id']]);
        $tab = $action === 'delete_koach' ? 'koach' : 'achim';
        header('Location: standing_orders.php?tab='.$tab);
        exit;
    }

    // ── EDIT ──────────────────────────────────────────────────────────
    if ($action === 'edit_koach' || $action === 'edit_achim') {
        // Whitelist validation for SQL injection prevention
        $allowedTables = ['standing_orders_koach' => 'standing_orders_koach', 'standing_orders_achim' => 'standing_orders_achim'];
        $table = $action === 'edit_koach' ? 'standing_orders_koach' : 'standing_orders_achim';
        if (!isset($allowedTables[$table])) {
            die('Invalid table');
        }
        $table = $allowedTables[$table];
        $stmt = $pdo->prepare("UPDATE $table SET donation_date=?, full_name=?, amount=?, last4=?, method=?, notes=? WHERE id=?");
        $stmt->execute([
            $_POST['donation_date'] ?? null,
            $_POST['full_name'] ?? '',
            $_POST['amount'] ?? 0,
            $_POST['last4'] ?? '',
            $_POST['method'] ?? 'אשראי',
            $_POST['notes'] ?? '',
            $_POST['id'],
        ]);
        $tab = $action === 'edit_koach' ? 'koach' : 'achim';
        header('Location: standing_orders.php?tab='.$tab);
        exit;
    }

    // ── TOGGLE ACTIVE SO ──────────────────────────────────────────────
    if ($action === 'toggle_active_so') {
        // Whitelist validation for SQL injection prevention
        $allowedFields = ['active_so_koach' => 'active_so_koach', 'active_so_achim' => 'active_so_achim'];
        $field = $_POST['so_type'] === 'koach' ? 'active_so_koach' : 'active_so_achim';
        if (!isset($allowedFields[$field])) {
            die('Invalid field');
        }
        $field = $allowedFields[$field];
        $val = (int)$_POST['active_val'];
        $pdo->prepare("UPDATE people SET $field = ? WHERE id = ?")->execute([$val, $_POST['person_id']]);
        header('Location: standing_orders.php?tab=alphon');
        exit;
    }

    // ── IMPORT ────────────────────────────────────────────────────────
    if ($action === 'import_koach' || $action === 'import_achim') {
        // Whitelist validation for SQL injection prevention
        $allowedTables = ['standing_orders_koach' => 'standing_orders_koach', 'standing_orders_achim' => 'standing_orders_achim'];
        $table = $action === 'import_koach' ? 'standing_orders_koach' : 'standing_orders_achim';
        if (!isset($allowedTables[$table])) {
            die('Invalid table');
        }
        $table = $allowedTables[$table];
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            try {
                // Validate uploaded file
                validateExcelUpload($_FILES['excel_file']);
                
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($_FILES['excel_file']['tmp_name']);
                if (method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($_FILES['excel_file']['tmp_name']);
                $rows = $spreadsheet->getActiveSheet()->toArray();
                array_shift($rows);
                $count = 0; $skipped = 0;
                foreach ($rows as $row) {
                    $date = soNormalizeDate($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    if (!$date || !$name) { $skipped++; continue; }
                    $pdo->prepare("INSERT INTO $table (donation_date, full_name, amount, last4, method, notes) VALUES (?,?,?,?,?,?)")
                        ->execute([$date, $name, $row[2]??0, $row[3]??'', $row[4]??'אשראי', $row[5]??'']);
                    $count++;
                }
                $_SESSION['message'] = "ייבוא הסתיים: $count נוספו, $skipped נדחו.";
            } catch (\Exception $e) {
                $_SESSION['message'] = 'שגיאה בייבוא: '.$e->getMessage();
            }
        } else {
            $_SESSION['message'] = 'שגיאה בהעלאת הקובץ.';
        }
        $tab = $action === 'import_koach' ? 'koach' : 'achim';
        header('Location: standing_orders.php?tab='.$tab);
        exit;
    }

    // ── EXPORT ────────────────────────────────────────────────────────
    if ($action === 'export_koach' || $action === 'export_achim') {
        // Whitelist validation for SQL injection prevention
        $allowedTables = ['standing_orders_koach' => 'standing_orders_koach', 'standing_orders_achim' => 'standing_orders_achim'];
        $table = $action === 'export_koach' ? 'standing_orders_koach' : 'standing_orders_achim';
        if (!isset($allowedTables[$table])) {
            die('Invalid table');
        }
        $table = $allowedTables[$table];
        $label = $action === 'export_koach' ? 'כח_הרבים' : 'אחים_לחסד';

        $filterParts = []; $filterParams = [];
        $fy = (int)($_POST['filter_year'] ?? 0);
        $fm = (int)($_POST['filter_month'] ?? 0);
        if ($fy > 0) { $filterParts[] = "YEAR(donation_date)=?"; $filterParams[] = $fy; }
        if ($fm > 0) { $filterParts[] = "MONTH(donation_date)=?"; $filterParams[] = $fm; }
        $where = $filterParts ? ' WHERE '.implode(' AND ', $filterParts) : '';

        $stmt = $pdo->prepare("SELECT donation_date, full_name, amount, last4, method, notes FROM $table $where ORDER BY donation_date DESC");
        $stmt->execute($filterParams);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $headers = ['תאריך תרומה','שם ומשפחה','סכום','4 ספרות אחרונות','אמצעי','הערות'];
        foreach ($headers as $i => $h) $sheet->setCellValueByColumnAndRow($i+1, 1, $h);
        $r = 2;
        foreach ($data as $row) {
            $c = 1;
            foreach ($row as $v) $sheet->setCellValueByColumnAndRow($c++, $r, $v);
            $r++;
        }
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$label.'_'.date('Y-m-d').'.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}

// ── Filters ────────────────────────────────────────────────────────────
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterYear  = isset($_GET['year'])  ? (int)$_GET['year']  : 0;

$yearStmt = $pdo->query("SELECT DISTINCT y FROM (SELECT YEAR(donation_date) AS y FROM standing_orders_koach UNION SELECT YEAR(donation_date) FROM standing_orders_achim) t WHERE y IS NOT NULL ORDER BY y DESC");
$availableYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

$dateParts = []; $dateParams = [];
if ($filterYear  > 0) { $dateParts[] = "YEAR(donation_date)=?"; $dateParams[] = $filterYear; }
if ($filterMonth > 0) { $dateParts[] = "MONTH(donation_date)=?"; $dateParams[] = $filterMonth; }
$dateWhere = $dateParts ? ' WHERE '.implode(' AND ', $dateParts) : '';

// Tab handling
$allowedTabs = ['koach', 'achim', 'alphon'];
$activeTab = $_GET['tab'] ?? ($_COOKIE['so_tab'] ?? 'koach');
if (!in_array($activeTab, $allowedTabs, true)) $activeTab = 'koach';
@setcookie('so_tab', $activeTab, time()+31536000, '/');

// ── Fetch data ─────────────────────────────────────────────────────────
$koachStmt = $pdo->prepare("SELECT * FROM standing_orders_koach $dateWhere ORDER BY donation_date DESC");
$koachStmt->execute($dateParams);
$koachRows = $koachStmt->fetchAll(PDO::FETCH_ASSOC);

$achimStmt = $pdo->prepare("SELECT * FROM standing_orders_achim $dateWhere ORDER BY donation_date DESC");
$achimStmt->execute($dateParams);
$achimRows = $achimStmt->fetchAll(PDO::FETCH_ASSOC);

// People for alphon tab
$alphonStmt = $pdo->query("
    SELECT p.id, p.full_name, p.address, p.city, p.phone, p.husband_mobile, p.wife_name, p.wife_mobile,
           p.updated_email, p.husband_id, p.wife_id, p.active_so_koach, p.active_so_achim,
           COALESCE(k.koach_total,0) AS koach_total, COALESCE(a.achim_total,0) AS achim_total
    FROM people p
    LEFT JOIN (SELECT person_id, SUM(amount) AS koach_total FROM standing_orders_koach GROUP BY person_id) k ON k.person_id = p.id
    LEFT JOIN (SELECT person_id, SUM(amount) AS achim_total FROM standing_orders_achim GROUP BY person_id) a ON a.person_id = p.id
    ORDER BY p.full_name
");
$alphonRows = $alphonStmt->fetchAll(PDO::FETCH_ASSOC);

// People list for dropdown
$peopleList = $pdo->query("SELECT id, full_name FROM people ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>
<link rel="stylesheet" href="../assets/css/expenses.css">
<link rel="stylesheet" href="../assets/css/standing_orders.css?v=<?php echo @filemtime(__DIR__.'/../assets/css/standing_orders.css') ?: time(); ?>">

<h2>הוראות קבע</h2>

<?php if ($message): ?>
<div class="modal fade" id="messageModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">הודעה</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><?php echo h($message); ?></div>
<div class="modal-footer"><button class="btn btn-brand" data-bs-dismiss="modal">סגור</button></div>
</div></div></div>
<?php endif; ?>

<!-- Sticky toolbar with filters & tabs -->
<div class="sticky-toolbar">
    <form method="get" class="row g-2 align-items-end mb-1" id="filterForm">
        <input type="hidden" name="tab" id="filter_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="col-auto">
            <select class="form-select" id="filter_month" name="month">
                <option value="">כל החודשים</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($filterMonth===$m)?'selected':''; ?>><?php echo $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <select class="form-select" id="filter_year" name="year">
                <option value="">כל השנים</option>
                <?php foreach ($availableYears as $y): ?>
                    <option value="<?php echo (int)$y; ?>" <?php echo ((int)$y===$filterYear)?'selected':''; ?>><?php echo (int)$y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-brand">סנן</button>
            <a class="btn btn-brand-outline" href="standing_orders.php?tab=<?php echo urlencode($activeTab); ?>">נקה סינון</a>
        </div>
    </form>
    <div class="tabs-nav">
        <button class="tab-btn <?php echo $activeTab==='alphon'?'active':''; ?>" data-tab="alphon">אלפון</button>
        <button class="tab-btn <?php echo $activeTab==='achim'?'active':''; ?>" data-tab="achim">אחים לחסד</button>
        <button class="tab-btn <?php echo $activeTab==='koach'?'active':''; ?>" data-tab="koach">כח הרבים</button>
    </div>
</div>

<!-- ══════════════  TAB 1 : כח הרבים  ══════════════ -->
<div class="tab-panel <?php echo $activeTab==='koach'?'active':''; ?>" id="koach-tab">
<div class="table-action-bar">
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addModal" data-so-type="koach">הוסף תרומה</button>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importModal" data-import-action="import_koach">ייבא מ-Excel</button>
    <form method="post" class="inline-form">
        <input type="hidden" name="action" value="export_koach">
        <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
        <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
        <?php echo csrf_input(); ?>
        <button type="submit" class="btn btn-brand">ייצא ל-Excel</button>
    </form>
</div>
<div class="card"><div class="card-body"><div class="table-scroll">
    <table id="koachTable" class="table table-striped mb-0" style="width:100%">
        <thead><tr>
            <th>תאריך תרומה</th><th>שם ומשפחה</th><th>סכום</th><th>4 ספרות אחרונות</th><th>אמצעי</th><th>הערות</th><th>פעולות</th>
        </tr></thead>
        <tbody>
        <?php foreach ($koachRows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['donation_date']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['amount']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['last4']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['method']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['notes']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td>
                    <button class="btn btn-sm btn-brand js-edit-so" data-bs-toggle="modal" data-bs-target="#editModal"
                        data-so-type="koach"
                        data-id="<?php echo $row['id']; ?>"
                        data-donation_date="<?php echo htmlspecialchars($row['donation_date']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-full_name="<?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-amount="<?php echo htmlspecialchars($row['amount']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-last4="<?php echo htmlspecialchars($row['last4']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-method="<?php echo htmlspecialchars($row['method']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-notes="<?php echo htmlspecialchars($row['notes']??'', ENT_QUOTES,'UTF-8'); ?>"
                    >ערוך</button>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete_koach">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-sm btn-brand" data-confirm="האם למחוק תרומה זו?">מחק</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div></div>
</div>

<!-- ══════════════  TAB 2 : אחים לחסד  ══════════════ -->
<div class="tab-panel <?php echo $activeTab==='achim'?'active':''; ?>" id="achim-tab">
<div class="table-action-bar">
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addModal" data-so-type="achim">הוסף תרומה</button>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importModal" data-import-action="import_achim">ייבא מ-Excel</button>
    <form method="post" class="inline-form">
        <input type="hidden" name="action" value="export_achim">
        <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
        <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
        <?php echo csrf_input(); ?>
        <button type="submit" class="btn btn-brand">ייצא ל-Excel</button>
    </form>
</div>
<div class="card"><div class="card-body"><div class="table-scroll">
    <table id="achimTable" class="table table-striped mb-0" style="width:100%">
        <thead><tr>
            <th>תאריך תרומה</th><th>שם ומשפחה</th><th>סכום</th><th>4 ספרות אחרונות</th><th>אמצעי</th><th>הערות</th><th>פעולות</th>
        </tr></thead>
        <tbody>
        <?php foreach ($achimRows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['donation_date']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['amount']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['last4']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['method']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['notes']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td>
                    <button class="btn btn-sm btn-brand js-edit-so" data-bs-toggle="modal" data-bs-target="#editModal"
                        data-so-type="achim"
                        data-id="<?php echo $row['id']; ?>"
                        data-donation_date="<?php echo htmlspecialchars($row['donation_date']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-full_name="<?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-amount="<?php echo htmlspecialchars($row['amount']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-last4="<?php echo htmlspecialchars($row['last4']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-method="<?php echo htmlspecialchars($row['method']??'', ENT_QUOTES,'UTF-8'); ?>"
                        data-notes="<?php echo htmlspecialchars($row['notes']??'', ENT_QUOTES,'UTF-8'); ?>"
                    >ערוך</button>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete_achim">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-sm btn-brand" data-confirm="האם למחוק תרומה זו?">מחק</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div></div>
</div>

<!-- ══════════════  TAB 3 : אלפון  ══════════════ -->
<div class="tab-panel <?php echo $activeTab==='alphon'?'active':''; ?>" id="alphon-tab">
<div class="card"><div class="card-body"><div class="table-scroll">
    <table id="soAlphonTable" class="table table-striped mb-0" style="width:100%">
        <thead class="table-dark"><tr>
            <th>שם ומשפחה</th>
            <th>כתובת</th>
            <th>עיר</th>
            <th>טלפון</th>
            <th>נייד בעל</th>
            <th>שם האשה</th>
            <th>נייד אשה</th>
            <th>מייל</th>
            <th>ת.ז. בעל</th>
            <th>ת.ז. אשה</th>
            <th>הו"ק כח הרבים</th>
            <th>הו"ק אחים לחסד</th>
            <th>פעילה כח</th>
            <th>פעילה אחים</th>
            <th>פעולות</th>
        </tr></thead>
        <tbody>
        <?php foreach ($alphonRows as $row): ?>
            <tr data-person-id="<?php echo (int)$row['id']; ?>">
                <td><?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['address']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['city']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['phone']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['husband_mobile']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['wife_name']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['wife_mobile']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['updated_email']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['husband_id']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['wife_id']??'', ENT_QUOTES,'UTF-8'); ?></td>
                <td class="so-amount"><?php echo number_format((float)$row['koach_total'],2); ?></td>
                <td class="so-amount"><?php echo number_format((float)$row['achim_total'],2); ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="toggle_active_so">
                        <input type="hidden" name="so_type" value="koach">
                        <input type="hidden" name="person_id" value="<?php echo (int)$row['id']; ?>">
                        <input type="hidden" name="active_val" value="<?php echo $row['active_so_koach'] ? 0 : 1; ?>">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-sm <?php echo $row['active_so_koach'] ? 'btn-success' : 'btn-outline-secondary'; ?>">
                            <?php echo $row['active_so_koach'] ? '✓ פעילה' : '✗ לא'; ?>
                        </button>
                    </form>
                </td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="toggle_active_so">
                        <input type="hidden" name="so_type" value="achim">
                        <input type="hidden" name="person_id" value="<?php echo (int)$row['id']; ?>">
                        <input type="hidden" name="active_val" value="<?php echo $row['active_so_achim'] ? 0 : 1; ?>">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-sm <?php echo $row['active_so_achim'] ? 'btn-success' : 'btn-outline-secondary'; ?>">
                            <?php echo $row['active_so_achim'] ? '✓ פעילה' : '✗ לא'; ?>
                        </button>
                    </form>
                </td>
                <td>
                    <button class="btn btn-sm btn-brand js-view-history" data-person-id="<?php echo (int)$row['id']; ?>" data-person-name="<?php echo htmlspecialchars($row['full_name']??'', ENT_QUOTES,'UTF-8'); ?>">
                        היסטוריה
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div></div>
</div>

<!-- ══════════════  ADD MODAL  ══════════════ -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">הוסף תרומה</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="post">
        <div class="modal-body">
            <input type="hidden" name="action" id="add_action" value="add_koach">
            <?php echo csrf_input(); ?>
            <div class="mb-3">
                <label class="form-label">שם ומשפחה</label>
                <input list="peopleDatalist" name="full_name" class="form-control" required autocomplete="off">
                <datalist id="peopleDatalist">
                    <?php foreach ($peopleList as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['full_name'], ENT_QUOTES,'UTF-8'); ?>" data-id="<?php echo $p['id']; ?>">
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" name="person_id" id="add_person_id" value="">
            </div>
            <div class="mb-3">
                <label class="form-label">תאריך תרומה</label>
                <input type="date" name="donation_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">סכום</label>
                <input type="number" name="amount" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">4 ספרות אחרונות</label>
                <input type="text" name="last4" class="form-control" maxlength="4">
            </div>
            <div class="mb-3">
                <label class="form-label">אמצעי</label>
                <select name="method" class="form-select">
                    <option value="אשראי">אשראי</option>
                    <option value="בנקאי">בנקאי</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">הערות</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
            <button type="submit" class="btn btn-brand">שמור</button>
        </div>
    </form>
</div></div></div>

<!-- ══════════════  EDIT MODAL  ══════════════ -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">עריכת תרומה</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="post">
        <div class="modal-body">
            <input type="hidden" name="action" id="edit_action" value="edit_koach">
            <input type="hidden" name="id" id="edit_id">
            <?php echo csrf_input(); ?>
            <div class="mb-3">
                <label class="form-label">שם ומשפחה</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">תאריך תרומה</label>
                <input type="date" name="donation_date" id="edit_donation_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">סכום</label>
                <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">4 ספרות אחרונות</label>
                <input type="text" name="last4" id="edit_last4" class="form-control" maxlength="4">
            </div>
            <div class="mb-3">
                <label class="form-label">אמצעי</label>
                <select name="method" id="edit_method" class="form-select">
                    <option value="אשראי">אשראי</option>
                    <option value="בנקאי">בנקאי</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">הערות</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
            <button type="submit" class="btn btn-brand">שמור</button>
        </div>
    </form>
</div></div></div>

<!-- ══════════════  IMPORT MODAL  ══════════════ -->
<div class="modal fade" id="importModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">ייבוא מ-Excel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" name="action" id="import_action" value="import_koach">
            <?php echo csrf_input(); ?>
            <p>העמודות הנדרשות: תאריך תרומה, שם ומשפחה, סכום, 4 ספרות אחרונות, אמצעי, הערות</p>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.xlsm" required>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
            <button type="submit" class="btn btn-brand">ייבא</button>
        </div>
    </form>
</div></div></div>

<!-- ══════════════  HISTORY MODAL  ══════════════ -->
<div class="modal fade" id="historyModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">היסטוריית תרומות – <span id="historyPersonName"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-center fw-bold">כח הרבים</h6>
                <div id="historyKoach" class="history-section"></div>
                <div class="history-total" id="historyKoachTotal"></div>
            </div>
            <div class="col-md-6">
                <h6 class="text-center fw-bold">אחים לחסד</h6>
                <div id="historyAchim" class="history-section"></div>
                <div class="history-total" id="historyAchimTotal"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-brand" data-bs-dismiss="modal">סגור</button></div>
</div></div></div>

<!-- History data endpoint -->
<script>window.soHistoryUrl = 'standing_orders_api.php';</script>
<script src="../assets/js/standing_orders.js?v=<?php echo @filemtime(__DIR__.'/../assets/js/standing_orders.js') ?: time(); ?>"></script>
<?php include '../templates/footer.php'; ?>
