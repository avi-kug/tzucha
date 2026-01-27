<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper encoding headers
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: he');

include '../templates/header.php';
require_once '../config/db.php';
require_once '../vendor/autoload.php';

session_start();
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

function handleInvoiceUpload($fileInputName, $existingPath = '')
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }

    if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('שגיאה בהעלאת הקובץ.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $fileName = $_FILES[$fileInputName]['name'];
    $fileTmp = $_FILES[$fileInputName]['tmp_name'];
    $fileSize = $_FILES[$fileInputName]['size'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('מותר להעלות רק JPG, PNG או PDF.');
    }

    if ($fileSize > $maxSize) {
        throw new Exception('הקובץ גדול מדי (מקסימום 5MB).');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fileTmp);
    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($mime, $allowedMime, true)) {
        throw new Exception('סוג הקובץ אינו נתמך.');
    }

    $newName = uniqid('invoice_', true) . '.' . $ext;
    $uploadDirAbs = __DIR__ . '/../uploads/invoices/';
    $uploadDirRel = '/tzucha/uploads/invoices/';

    if (!is_dir($uploadDirAbs)) {
        mkdir($uploadDirAbs, 0775, true);
    }

    $dest = $uploadDirAbs . $newName;
    if (!move_uploaded_file($fileTmp, $dest)) {
        throw new Exception('לא ניתן לשמור את הקובץ שהועלה.');
    }

    return $uploadDirRel . $newName;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action == 'copy_fixed') {
            $currentMonth = date('Y-m');
            $stmt = $pdo->prepare("INSERT INTO summary_expenses (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) SELECT date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy FROM fixed_expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt->execute([$currentMonth]);
            echo "<script>alert('הוצאות הועתקו בהצלחה לסיכום השנתי!');</script>";
            echo "<script>window.location.href = 'expenses.php';</script>";
            exit;
        } elseif ($action == 'add_department') {
            $pdo->prepare("INSERT INTO departments (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name")->execute([$_POST['new_department']]);
        } elseif ($action == 'add_category') {
            $pdo->prepare("INSERT INTO categories (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name")->execute([$_POST['new_category']]);
        } elseif ($action == 'add_expense_type') {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$_POST['category_for_type']]);
            $catId = $stmt->fetch()['id'];
            $pdo->prepare("INSERT INTO expense_types (category_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name")->execute([$catId, $_POST['new_expense_type']]);
        } elseif ($action == 'add_paid_by') {
            $pdo->prepare("INSERT INTO paid_by_options (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name")->execute([$_POST['new_paid_by']]);
        } elseif ($action == 'add_from_account') {
            $pdo->prepare("INSERT INTO from_accounts (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name")->execute([$_POST['new_from_account']]);
        } elseif ($action == 'delete_department') {
            $pdo->prepare("DELETE FROM departments WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_category') {
            $pdo->prepare("DELETE FROM categories WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_expense_type') {
            $pdo->prepare("DELETE FROM expense_types WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_paid_by') {
            $pdo->prepare("DELETE FROM paid_by_options WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_from_account') {
            $pdo->prepare("DELETE FROM from_accounts WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_fixed') {
            $pdo->prepare("DELETE FROM fixed_expenses WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'delete_regular') {
            $pdo->prepare("DELETE FROM regular_expenses WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'delete_combined') {
            $table = ($_POST['source'] == 'קבועה') ? 'summary_expenses' : 'regular_expenses';
            $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'import_fixed' || $action == 'import_regular' || $action == 'import_summary') {
            $table = str_replace('import_', '', $action) . '_expenses';
            if ($table == 'summary_expenses') $table = 'summary_expenses';
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
                $file = $_FILES['excel_file']['tmp_name'];
                $fileName = $_FILES['excel_file']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Check file extension
                if (!in_array($fileExtension, ['xlsx', 'xls'])) {
                    echo "<script>alert('נא להעלות קובץ Excel בלבד (.xlsx או .xls)');</script>";
                    echo "<script>window.location.href = 'expenses.php';</script>";
                    exit;
                }
                
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    array_shift($rows); // Remove header
                    
                    $count = 0;
                    $skipped = 0;
                    $errors = [];
                    $rowIndex = 2; // Excel row number (after header)
                    foreach ($rows as $row) {
                        if (count($row) < 10) {
                            $skipped++;
                            $errors[] = "שורה {$rowIndex}: חסרות עמודות (נדרשות 10).";
                            $rowIndex++;
                            continue;
                        }

                        if (empty($row[0])) {
                            $skipped++;
                            $errors[] = "שורה {$rowIndex}: חסר תאריך.";
                            $rowIndex++;
                            continue;
                        }

                        $rawDate = $row[0];
                        $row[0] = normalizeImportedDate($row[0]);
                        if (!$row[0]) {
                            $skipped++;
                            $errors[] = "שורה {$rowIndex}: תאריך לא תקין ({$rawDate}).";
                            $rowIndex++;
                            continue;
                        }

                        $stmt = $pdo->prepare("INSERT INTO $table (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute($row);
                        $count++;
                        $rowIndex++;
                    }


                    echo "<script>alert('הוצאות הועלו בהצלחה מ-Excel! ({$count} רשומות נוספו, {$skipped} שורות נדחו)');</script>";
                    echo "<script>window.location.href = 'expenses.php';</script>";
                    exit;
                } catch (Exception $e) {
                    echo "<script>alert('שגיאה בטעינת הקובץ: הקובץ אינו תקין או פגום. נא להעלות קובץ Excel תקין.');</script>";
                    echo "<script>window.location.href = 'expenses.php';</script>";
                    exit;
                }
            }
        } elseif ($action == 'export_fixed' || $action == 'export_regular' || $action == 'export_summary') {
            $table = str_replace('export_', '', $action) . '_expenses';
            if ($table == 'summary_expenses') $table = 'summary_expenses';
            $stmt = $pdo->query("SELECT date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy FROM $table ORDER BY date DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $spreadsheet->getProperties()->setCreator('Tzucha System')->setTitle('Expenses Export');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'תאריך');
            $sheet->setCellValue('B1', 'עבור');
            $sheet->setCellValue('C1', 'חנות');
            $sheet->setCellValue('D1', 'סכום');
            $sheet->setCellValue('E1', 'אגף');
            $sheet->setCellValue('F1', 'קטגוריה');
            $sheet->setCellValue('G1', 'סוג הוצאה');
            $sheet->setCellValue('H1', 'שולם ע"י');
            $sheet->setCellValue('I1', 'יצא מ');
            $sheet->setCellValue('J1', 'העתק חשבונית');
            
            $rowNum = 2;
            foreach ($data as $row) {
                $sheet->setCellValue('A' . $rowNum, $row['date']);
                $sheet->setCellValue('B' . $rowNum, $row['for_what']);
                $sheet->setCellValue('C' . $rowNum, $row['store']);
                $sheet->setCellValue('D' . $rowNum, $row['amount']);
                $sheet->setCellValue('E' . $rowNum, $row['department']);
                $sheet->setCellValue('F' . $rowNum, $row['category']);
                $sheet->setCellValue('G' . $rowNum, $row['expense_type']);
                $sheet->setCellValue('H' . $rowNum, $row['paid_by']);
                $sheet->setCellValue('I' . $rowNum, $row['from_account']);
                $sheet->setCellValue('J' . $rowNum, $row['invoice_copy']);
                $rowNum++;
            }
            
            $filename = $table . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Clear any previous output
            if (ob_get_length()) ob_end_clean();
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');
            
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
        } else {
            $table = ($action == 'add_fixed' || $action == 'edit_fixed') ? 'fixed_expenses' : 'regular_expenses';

            try {
                $existingInvoice = isset($_POST['existing_invoice_copy']) ? $_POST['existing_invoice_copy'] : '';
                $invoicePath = handleInvoiceUpload('invoice_copy_file', $existingInvoice);
            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "');</script>";
                echo "<script>window.location.href = 'expenses.php?tab=" . (isset($_POST['current_tab']) ? $_POST['current_tab'] : '') . "';</script>";
                exit;
            }

            $data = [
                'date' => $_POST['date'],
                'for_what' => $_POST['for_what'],
                'store' => $_POST['store'],
                'amount' => $_POST['amount'],
                'department' => $_POST['department'],
                'category' => $_POST['category'],
                'expense_type' => $_POST['expense_type'],
                'paid_by' => $_POST['paid_by'],
                'from_account' => $_POST['from_account'],
                'invoice_copy' => $invoicePath
            ];
            if ($action == 'edit_fixed' || $action == 'edit_regular') {
                $stmt = $pdo->prepare("UPDATE $table SET date=?, for_what=?, store=?, amount=?, department=?, category=?, expense_type=?, paid_by=?, from_account=?, invoice_copy=? WHERE id=?");
                $stmt->execute(array_merge(array_values($data), [$_POST['id']]));
            } else {
                $stmt = $pdo->prepare("INSERT INTO $table (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array_values($data));
            }
        }
        
        // Determine which tab to return to based on the action or current_tab
        $dataActions = ['add_department', 'add_category', 'add_expense_type', 'add_paid_by', 'add_from_account', 
                       'delete_department', 'delete_category', 'delete_expense_type', 'delete_paid_by', 'delete_from_account'];
        
        // Get the current tab from POST or determine by action
        $currentTab = isset($_POST['current_tab']) ? $_POST['current_tab'] : '';
        
        if ($currentTab) {
            header('Location: expenses.php?tab=' . $currentTab);
        } elseif (in_array($action, $dataActions)) {
            header('Location: expenses.php?tab=data');
        } elseif (strpos($action, 'regular') !== false) {
            header('Location: expenses.php?tab=regular');
        } elseif (strpos($action, 'fixed') !== false) {
            header('Location: expenses.php?tab=fixed');
        } elseif (strpos($action, 'combined') !== false) {
            header('Location: expenses.php?tab=combined');
        } else {
            header('Location: expenses.php');
        }
        exit;
    }
}

// Fetch options
$departments = $pdo->query("SELECT name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$paid_by_options = $pdo->query("SELECT name FROM paid_by_options ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$from_accounts = $pdo->query("SELECT name FROM from_accounts ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

// Fetch expense types grouped by category
$expenseTypesQuery = $pdo->query("SELECT c.name as category, et.name as type FROM categories c LEFT JOIN expense_types et ON c.id = et.category_id ORDER BY c.name, et.name");
$expenseTypes = [];
while ($row = $expenseTypesQuery->fetch()) {
    $expenseTypes[$row['category']][] = $row['type'];
}

function renderImportExportButtons($importAction, $exportAction)
{
    return "<button type=\"button\" class=\"btn btn-success mb-3\" data-bs-toggle=\"modal\" data-bs-target=\"#importModal\" onclick=\"setImportAction('{$importAction}')\">ייבא מ-Excel</button>\n" .
        "<form method=\"post\" style=\"display: inline;\">\n" .
        "    <input type=\"hidden\" name=\"action\" value=\"{$exportAction}\">\n" .
        "    <button type=\"submit\" class=\"btn btn-info mb-3\">ייצא ל-Excel</button>\n" .
        "</form>";
}

function renderExpensesTableHead($includeSource = false)
{
    $cols = ['תאריך', 'עבור', 'חנות', 'סכום', 'אגף', 'קטגוריה', 'סוג הוצאה', 'שולם ע"י', 'יצא מ', 'העתק חשבונית'];
    if ($includeSource) {
        $cols[] = 'מקור';
    }
    $cols[] = 'פעולות';

    $head = "<thead><tr>";
    foreach ($cols as $col) {
        $head .= "<th>{$col}</th>";
    }
    $head .= "</tr></thead>";

    return $head;
}

function renderDeleteForm($action, $id, $currentTab, $extraFields = [])
{
    $form = "<form method='post' style='display: inline;'>";
    $form .= "<input type='hidden' name='action' value='{$action}'>";
    $form .= "<input type='hidden' name='id' value='{$id}'>";
    foreach ($extraFields as $name => $value) {
        $form .= "<input type='hidden' name='{$name}' value='{$value}'>";
    }
    if ($currentTab) {
        $form .= "<input type='hidden' name='current_tab' value='{$currentTab}'>";
    }
    $form .= "<button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק הוצאה זו?\")'>מחק</button>";
    $form .= "</form>";

    return $form;
}

function renderSimpleTable($headers, $rows, $tableClass = 'table table-striped')
{
    $thead = '<thead><tr>';
    foreach ($headers as $header) {
        $thead .= '<th>' . $header . '</th>';
    }
    $thead .= '</tr></thead>';

    $tbody = '<tbody>';
    foreach ($rows as $row) {
        $tbody .= '<tr>';
        foreach ($row as $cell) {
            $tbody .= '<td>' . $cell . '</td>';
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    return '<div class="table-scroll" style="max-height: 300px;"><table class="' . $tableClass . ' mb-0">' . $thead . $tbody . '</table></div>';
}

function renderEditButton($type, $row)
{
    return "<button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editExpenseModal' data-type='{$type}' data-id='{$row['id']}' data-date='{$row['date']}' data-for_what='{$row['for_what']}' data-store='{$row['store']}' data-amount='{$row['amount']}' data-department='{$row['department']}' data-category='{$row['category']}' data-expense_type='{$row['expense_type']}' data-paid_by='{$row['paid_by']}' data-from_account='{$row['from_account']}' data-invoice_copy='{$row['invoice_copy']}' onclick='editExpense(this)'>ערוך</button>";
}

function formatInvoiceCopy($path)
{
    if (!$path) {
        return '';
    }
    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    return "<a href=\"{$safePath}\" target=\"_blank\" rel=\"noopener\">צפה</a>";
}

function renderExpenseRow($row, $type, $deleteForm, $includeSource = false, $includeEdit = true)
{
    $cells = [
        $row['date'],
        $row['for_what'],
        $row['store'],
        $row['amount'],
        $row['department'],
        $row['category'],
        $row['expense_type'],
        $row['paid_by'],
        $row['from_account'],
        formatInvoiceCopy($row['invoice_copy'])
    ];

    if ($includeSource) {
        $cells[] = $row['source'];
    }

    $actions = '';
    if ($includeEdit) {
        $actions .= renderEditButton($type, $row);
    }
    $actions .= $deleteForm;
    $cells[] = $actions;

    $html = '<tr>';
    foreach ($cells as $cell) {
        $html .= '<td>' . $cell . '</td>';
    }
    $html .= '</tr>';

    return $html;
}

function normalizeImportedDate($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    // Excel serialized number
    if (is_numeric($value)) {
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    $value = trim((string)$value);

    // Handle common formats: d/m/Y, d.m.Y, Y-m-d
    $normalized = str_replace(['.', '-'], '/', $value);
    $parts = explode('/', $normalized);
    if (count($parts) === 3) {
        // If format is Y/m/d
        if (strlen($parts[0]) === 4) {
            $y = (int)$parts[0];
            $m = (int)$parts[1];
            $d = (int)$parts[2];
        } else {
            $d = (int)$parts[0];
            $m = (int)$parts[1];
            $y = (int)$parts[2];
        }
        if ($y > 0 && $m >= 1 && $m <= 12 && $d >= 1 && $d <= 31) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }

    $ts = strtotime($value);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return null;
}
?>
<h2>הוצאות</h2>
<?php if ($message): ?>
<div class="alert alert-info" role="alert">
    <?php echo $message; ?>
</div>
<?php endif; ?>
<?php
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$yearStmt = $pdo->query("SELECT DISTINCT YEAR(date) as y FROM fixed_expenses UNION SELECT DISTINCT YEAR(date) FROM regular_expenses UNION SELECT DISTINCT YEAR(date) FROM summary_expenses ORDER BY y DESC");
$availableYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

$dateFilterParts = [];
$dateFilterParams = [];
if ($filterYear > 0) {
    $dateFilterParts[] = "YEAR(date) = ?";
    $dateFilterParams[] = $filterYear;
}
if ($filterMonth > 0) {
    $dateFilterParts[] = "MONTH(date) = ?";
    $dateFilterParams[] = $filterMonth;
}
$dateFilterSql = $dateFilterParts ? " WHERE " . implode(" AND ", $dateFilterParts) : "";
$combinedDateFilterParams = array_merge($dateFilterParams, $dateFilterParams);

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'fixed';
?>
<div class="sticky-toolbar">
    <form method="get" class="row g-2 align-items-end mb-3" id="filterForm">
        <input type="hidden" name="tab" id="filter_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="col-auto">
            <label for="filter_month" class="form-label">חודש</label>
            <select class="form-select" id="filter_month" name="month">
                <option value="">כל החודשים</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($filterMonth === $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <label for="filter_year" class="form-label">שנה</label>
            <select class="form-select" id="filter_year" name="year">
                <option value="">כל השנים</option>
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?php echo (int)$year; ?>" <?php echo ((int)$year === $filterYear) ? 'selected' : ''; ?>><?php echo (int)$year; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">סנן</button>
            <a class="btn btn-outline-secondary" href="expenses.php<?php echo $activeTab ? '?tab=' . urlencode($activeTab) : ''; ?>">נקה סינון</a>
        </div>
    </form>
    <ul class="nav nav-tabs" id="expensesTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'fixed') ? 'active' : ''; ?>" id="fixed-tab" data-bs-toggle="tab" data-bs-target="#fixed" type="button" role="tab" aria-controls="fixed" aria-selected="<?php echo ($activeTab === 'fixed') ? 'true' : 'false'; ?>">הוצאות קבועות</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'regular') ? 'active' : ''; ?>" id="regular-tab" data-bs-toggle="tab" data-bs-target="#regular" type="button" role="tab" aria-controls="regular" aria-selected="<?php echo ($activeTab === 'regular') ? 'true' : 'false'; ?>">הוצאות רגילות</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'combined') ? 'active' : ''; ?>" id="combined-tab" data-bs-toggle="tab" data-bs-target="#combined" type="button" role="tab" aria-controls="combined" aria-selected="<?php echo ($activeTab === 'combined') ? 'true' : 'false'; ?>">סיכום שנתי</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'dashboard') ? 'active' : ''; ?>" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="<?php echo ($activeTab === 'dashboard') ? 'true' : 'false'; ?>">דשבורד</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo ($activeTab === 'data') ? 'active' : ''; ?>" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab" aria-controls="data" aria-selected="<?php echo ($activeTab === 'data') ? 'true' : 'false'; ?>">נתונים</button>
    </li>
</ul>
</div>

<div class="content-body">
<div class="tab-content" id="expensesTabsContent">
    <div class="tab-pane fade <?php echo ($activeTab === 'fixed') ? 'show active' : ''; ?>" id="fixed" role="tabpanel" aria-labelledby="fixed-tab">
        <h3>הוצאות קבועות</h3>
        <div class="tab-action-bar">
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addExpenseModal" onclick="setModal('fixed')">הוסף הוצאה</button>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="copy_fixed">
                <button type="submit" class="btn btn-secondary mb-3">העתק הוצאות קבועות לחודש זה לסיכום</button>
            </form>
            <?php echo renderImportExportButtons('import_fixed', 'export_fixed'); ?>
        </div>
        <div class="card">
            <div class="card-body p-0 table-scroll">
                <table class="table table-striped mb-0">
                    <?php echo renderExpensesTableHead(); ?>
                    <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM fixed_expenses{$dateFilterSql} ORDER BY date DESC");
                $stmt->execute($dateFilterParams);
                while ($row = $stmt->fetch()) {
                    $deleteForm = renderDeleteForm('delete_fixed', $row['id'], 'fixed');
                    echo renderExpenseRow($row, 'fixed', $deleteForm, false, true);
                }
                ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'regular') ? 'show active' : ''; ?>" id="regular" role="tabpanel" aria-labelledby="regular-tab">
        <h3>הוצאות רגילות</h3>
        <div class="tab-action-bar">
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addExpenseModal" onclick="setModal('regular')">הוסף הוצאה</button>
            <?php echo renderImportExportButtons('import_regular', 'export_regular'); ?>
        </div>
        <div class="card">
            <div class="card-body p-0 table-scroll">
                <table class="table table-striped mb-0">
                    <?php echo renderExpensesTableHead(); ?>
                    <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM regular_expenses{$dateFilterSql} ORDER BY date DESC");
                $stmt->execute($dateFilterParams);
                while ($row = $stmt->fetch()) {
                    $deleteForm = renderDeleteForm('delete_regular', $row['id'], 'regular');
                    echo renderExpenseRow($row, 'regular', $deleteForm, false, true);
                }
                ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'data') ? 'show active' : ''; ?>" id="data" role="tabpanel" aria-labelledby="data-tab">
        <h3>נתונים</h3>
        <div class="pane-scroll">
        <!-- אגפים -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>אגפים</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>שם אגף</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($departments as $dept) {
                                echo "<tr>
                                    <td>{$dept}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_department'>
                                            <input type='hidden' name='delete_item' value='{$dept}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את האגף \\\"{$dept}\\\"\")'>מחק</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_department">
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_department" placeholder="שם אגף חדש" required>
                        <button class="btn btn-success" type="submit">הוסף אגף</button>
                    </div>
                </form>
            </div>

            <!-- קטגוריות -->
            <div class="col-md-6">
                <h5>קטגוריות</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>שם קטגוריה</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($categories as $cat) {
                                echo "<tr>
                                    <td>{$cat}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_category'>
                                            <input type='hidden' name='delete_item' value='{$cat}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את הקטגוריה \\\"{$cat}\\\"\")'>מחק</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_category">
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_category" placeholder="שם קטגוריה חדשה" required>
                        <button class="btn btn-success" type="submit">הוסף קטגוריה</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- סוגי הוצאות -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>סוגי הוצאות</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>קטגוריה</th>
                                <th>סוג הוצאה</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $expenseTypesQuery = $pdo->query("SELECT c.name as category, et.name as type FROM categories c LEFT JOIN expense_types et ON c.id = et.category_id ORDER BY c.name, et.name");
                            while ($row = $expenseTypesQuery->fetch()) {
                                if ($row['type']) {
                                    echo "<tr>
                                        <td>{$row['category']}</td>
                                        <td>{$row['type']}</td>
                                        <td>
                                            <form method='post' style='display: inline;'>
                                                <input type='hidden' name='action' value='delete_expense_type'>
                                                <input type='hidden' name='delete_item' value='{$row['type']}'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את סוג ההוצאה \\\"{$row['type']}\\\"\")'>מחק</button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_expense_type">
                    <div class="mb-2">
                        <select class="form-control" name="category_for_type" required>
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $cat) echo "<option value=\"$cat\">$cat</option>"; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_expense_type" placeholder="שם סוג הוצאה חדש" required>
                        <button class="btn btn-success" type="submit">הוסף סוג הוצאה</button>
                    </div>
                </form>
            </div>

            <!-- שולם ע"י -->
            <div class="col-md-6">
                <h5>שולם ע"י</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($paid_by_options as $option) {
                                echo "<tr>
                                    <td>{$option}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_paid_by'>
                                            <input type='hidden' name='delete_item' value='{$option}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את האפשרות \\\"{$option}\\\"\")'>מחק</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_paid_by">
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_paid_by" placeholder="שם חדש" required>
                        <button class="btn btn-success" type="submit">הוסף אפשרות</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- יצא מ -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>יצא מ</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($from_accounts as $account) {
                                echo "<tr>
                                    <td>{$account}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_from_account'>
                                            <input type='hidden' name='delete_item' value='{$account}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את החשבון \\\"{$account}\\\"\")'>מחק</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_from_account">
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_from_account" placeholder="שם חדש" required>
                        <button class="btn btn-success" type="submit">הוסף חשבון</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'combined') ? 'show active' : ''; ?>" id="combined" role="tabpanel" aria-labelledby="combined-tab">
        <h3>סיכום שנתי כל ההוצאות</h3>
        <div class="tab-action-bar">
            <?php echo renderImportExportButtons('import_summary', 'export_summary'); ?>
        </div>
        <div class="card">
            <div class="card-body p-0 table-scroll">
                <table id="combinedTable" class="table table-striped mb-0">
                    <?php echo renderExpensesTableHead(true); ?>
                    <tbody>
                <?php
                $combinedSql = "
                    SELECT id, date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy, 'קבועה' as source
                    FROM summary_expenses{$dateFilterSql}
                    UNION ALL
                    SELECT id, date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy, 'רגילה' as source
                    FROM regular_expenses{$dateFilterSql}
                    ORDER BY date DESC
                ";
                $stmt = $pdo->prepare($combinedSql);
                $stmt->execute($combinedDateFilterParams);
                while ($row = $stmt->fetch()) {
                    $deleteForm = renderDeleteForm('delete_combined', $row['id'], 'combined', ['source' => $row['source']]);
                    echo renderExpenseRow($row, '', $deleteForm, true, false);
                }
                ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'dashboard') ? 'show active' : ''; ?>" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
        <h3>דשבורד הוצאות</h3>
        <div class="pane-scroll">
        <div class="row">
            <div class="col-md-6">
                <h4>סיכום לפי אגפים</h4>
                <?php
                $stmt = $pdo->prepare("
                    SELECT department, SUM(amount) as total
                    FROM (
                        SELECT department, amount FROM summary_expenses{$dateFilterSql}
                        UNION ALL
                        SELECT department, amount FROM regular_expenses{$dateFilterSql}
                    ) combined
                    GROUP BY department
                    ORDER BY total DESC
                ");
                $stmt->execute($combinedDateFilterParams);
                $deptRows = [];
                while ($row = $stmt->fetch()) {
                    $deptRows[] = [$row['department'], $row['total']];
                }
                echo renderSimpleTable(['אגף', 'סכום כולל'], $deptRows);
                ?>
            </div>
            <div class="col-md-6">
                <h4>סיכום לפי קטגוריות</h4>
                <?php
                $stmt = $pdo->prepare("
                    SELECT category, SUM(amount) as total
                    FROM (
                        SELECT category, amount FROM summary_expenses{$dateFilterSql}
                        UNION ALL
                        SELECT category, amount FROM regular_expenses{$dateFilterSql}
                    ) combined
                    GROUP BY category
                    ORDER BY total DESC
                ");
                $stmt->execute($combinedDateFilterParams);
                $categoryRows = [];
                while ($row = $stmt->fetch()) {
                    $categoryRows[] = [$row['category'], $row['total']];
                }
                echo renderSimpleTable(['קטגוריה', 'סכום כולל'], $categoryRows);
                ?>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <h4>סיכום לפי סוג הוצאה</h4>
                <?php
                $stmt = $pdo->prepare("
                    SELECT expense_type, SUM(amount) as total
                    FROM (
                        SELECT expense_type, amount FROM summary_expenses{$dateFilterSql}
                        UNION ALL
                        SELECT expense_type, amount FROM regular_expenses{$dateFilterSql}
                    ) combined
                    WHERE expense_type IS NOT NULL AND expense_type != ''
                    GROUP BY expense_type
                    ORDER BY total DESC
                ");
                $stmt->execute($combinedDateFilterParams);
                $typeRows = [];
                while ($row = $stmt->fetch()) {
                    $typeRows[] = [$row['expense_type'], $row['total']];
                }
                echo renderSimpleTable(['סוג הוצאה', 'סכום כולל'], $typeRows);
                ?>
            </div>
            <div class="col-md-6">
                <h4>סיכום לפי מקור תשלום</h4>
                <?php
                $stmt = $pdo->prepare("
                    SELECT from_account, SUM(amount) as total
                    FROM (
                        SELECT from_account, amount FROM summary_expenses{$dateFilterSql}
                        UNION ALL
                        SELECT from_account, amount FROM regular_expenses{$dateFilterSql}
                    ) combined
                    GROUP BY from_account
                    ORDER BY total DESC
                ");
                $stmt->execute($combinedDateFilterParams);
                $fromAccountRows = [];
                while ($row = $stmt->fetch()) {
                    $fromAccountRows[] = [$row['from_account'], $row['total']];
                }
                echo renderSimpleTable(['מקור תשלום', 'סכום כולל'], $fromAccountRows);
                ?>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <h4>סיכום כללי</h4>
                <div class="row">
                    <?php
                    $totalStmt = $pdo->prepare("
                        SELECT SUM(amount) as total, COUNT(*) as count
                        FROM (
                            SELECT amount FROM summary_expenses{$dateFilterSql}
                            UNION ALL
                            SELECT amount FROM regular_expenses{$dateFilterSql}
                        ) combined
                    ");
                    $totalStmt->execute($combinedDateFilterParams);
                    $total = $totalStmt->fetch();
                    ?>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">סכום כולל</h5>
                                <p class="card-text h4"><?php echo $total['total']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">מספר הוצאות</h5>
                                <p class="card-text h4"><?php echo $total['count']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                    $fixedStmt = $pdo->prepare("SELECT SUM(amount) as total FROM summary_expenses{$dateFilterSql}");
                    $fixedStmt->execute($dateFilterParams);
                    $fixed = $fixedStmt->fetch()['total'] ?? 0;
                    $regularStmt = $pdo->prepare("SELECT SUM(amount) as total FROM regular_expenses{$dateFilterSql}");
                    $regularStmt->execute($dateFilterParams);
                    $regular = $regularStmt->fetch()['total'] ?? 0;
                    ?>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">הוצאות קבועות</h5>
                                <p class="card-text h4"><?php echo $fixed; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">הוצאות רגילות</h5>
                                <p class="card-text h4"><?php echo $regular; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExpenseModalLabel">הוסף הוצאה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action">
                    <input type="hidden" name="current_tab" id="current_tab">
                    <div class="mb-3">
                        <label for="date" class="form-label">תאריך</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="for_what" class="form-label">עבור</label>
                        <input type="text" class="form-control" id="for_what" name="for_what">
                    </div>
                    <div class="mb-3">
                        <label for="store" class="form-label">חנות</label>
                        <select class="form-control" id="store" name="store">
                            <option value="">בחר חנות</option>
                            <?php
                            $stores = $pdo->query("SELECT name FROM stores ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($stores as $store) {
                                echo "<option value=\"$store\">$store</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">סכום</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">אגף</label>
                        <select class="form-control" id="department" name="department">
                            <option value="">בחר אגף</option>
                            <?php foreach ($departments as $dep) echo "<option value=\"$dep\">$dep</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">קטגוריה</label>
                        <select class="form-control" id="category" name="category" onchange="updateExpenseType()">
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $cat) echo "<option value=\"$cat\">$cat</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expense_type" class="form-label">סוג הוצאה</label>
                        <select class="form-control" id="expense_type" name="expense_type">
                            <option value="">בחר סוג הוצאה</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="paid_by" class="form-label">שולם ע"י</label>
                        <select class="form-control" id="paid_by" name="paid_by">
                            <option value="">בחר שולם ע&quot;י</option>
                            <?php foreach ($paid_by_options as $pbo) echo "<option value=\"$pbo\">$pbo</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="from_account" class="form-label">יצא מ</label>
                        <select class="form-control" id="from_account" name="from_account">
                            <option value="">בחר יצא מ</option>
                            <?php foreach ($from_accounts as $fa) echo "<option value=\"$fa\">$fa</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_copy_file" class="form-label">העתק חשבונית (תמונה או PDF)</label>
                        <input type="file" class="form-control" id="invoice_copy_file" name="invoice_copy_file" accept=".jpg,.jpeg,.png,.pdf">
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

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExpenseModalLabel">ערוך הוצאה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="edit_action">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="current_tab" id="edit_current_tab">
                    <input type="hidden" name="existing_invoice_copy" id="existing_invoice_copy">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">תאריך</label>
                        <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_for_what" class="form-label">עבור</label>
                        <input type="text" class="form-control" id="edit_for_what" name="for_what">
                    </div>
                    <div class="mb-3">
                        <label for="edit_store" class="form-label">חנות</label>
                        <select class="form-control" id="edit_store" name="store">
                            <option value="">בחר חנות</option>
                            <?php
                            $stores = $pdo->query("SELECT name FROM stores ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($stores as $store) {
                                echo "<option value=\"$store\">$store</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label">סכום</label>
                        <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_department" class="form-label">אגף</label>
                        <select class="form-control" id="edit_department" name="department">
                            <option value="">בחר אגף</option>
                            <?php foreach ($departments as $dep) echo "<option value=\"$dep\">$dep</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">קטגוריה</label>
                        <select class="form-control" id="edit_category" name="category" onchange="updateEditExpenseType()">
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $cat) echo "<option value=\"$cat\">$cat</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_expense_type" class="form-label">סוג הוצאה</label>
                        <select class="form-control" id="edit_expense_type" name="expense_type">
                            <option value="">בחר סוג הוצאה</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_paid_by" class="form-label">שולם ע"י</label>
                        <select class="form-control" id="edit_paid_by" name="paid_by">
                            <option value="">בחר שולם ע&quot;י</option>
                            <?php foreach ($paid_by_options as $pbo) echo "<option value=\"$pbo\">$pbo</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_from_account" class="form-label">יצא מ</label>
                        <select class="form-control" id="edit_from_account" name="from_account">
                            <option value="">בחר יצא מ</option>
                            <?php foreach ($from_accounts as $fa) echo "<option value=\"$fa\">$fa</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_invoice_copy_file" class="form-label">העתק חשבונית (תמונה או PDF)</label>
                        <input type="file" class="form-control" id="edit_invoice_copy_file" name="invoice_copy_file" accept=".jpg,.jpeg,.png,.pdf">
                        <div id="edit_invoice_copy_preview" style="margin-top: 6px;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-primary">עדכן</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">ייבא מ-Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="import_action">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">בחר קובץ Excel</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <p class="text-muted">הקובץ צריך להכיל עמודות: תאריך, עבור, חנות, סכום, אגף, קטגוריה, סוג הוצאה, שולם ע"י, יצא מ, העתק חשבונית</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-success">ייבא</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
// Initialize page-specific code
console.log('Expenses page loaded');

let expenseTypes = {};
try {
    expenseTypes = <?php echo json_encode($expenseTypes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
} catch(e) {
    console.error('Failed to parse expenseTypes:', e);
}

function setModal(type) {
    document.getElementById('action').value = 'add_' + type;
}

function setImportAction(action) {
    document.getElementById('import_action').value = action;
}

function editExpense(button) {
    const type = button.getAttribute('data-type');
    document.getElementById('edit_action').value = 'edit_' + type;
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('edit_date').value = button.getAttribute('data-date');
    document.getElementById('edit_for_what').value = button.getAttribute('data-for_what');
    document.getElementById('edit_store').value = button.getAttribute('data-store');
    document.getElementById('edit_amount').value = button.getAttribute('data-amount');
    document.getElementById('edit_department').value = button.getAttribute('data-department');
    document.getElementById('edit_category').value = button.getAttribute('data-category');
    document.getElementById('edit_paid_by').value = button.getAttribute('data-paid_by');
    document.getElementById('edit_from_account').value = button.getAttribute('data-from_account');
    const invoicePath = button.getAttribute('data-invoice_copy') || '';
    document.getElementById('existing_invoice_copy').value = invoicePath;
    const preview = document.getElementById('edit_invoice_copy_preview');
    if (preview) {
        preview.innerHTML = invoicePath ? `<a href="${invoicePath}" target="_blank" rel="noopener">צפה בקובץ קיים</a>` : '';
    }
    document.getElementById('edit_department').value = button.getAttribute('data-department');
    document.getElementById('edit_category').value = button.getAttribute('data-category');
    updateEditExpenseType();
    document.getElementById('edit_expense_type').value = button.getAttribute('data-expense_type');
    document.getElementById('edit_paid_by').value = button.getAttribute('data-paid_by');
    document.getElementById('edit_from_account').value = button.getAttribute('data-from_account');
}

function updateExpenseType() {
    const category = document.getElementById('category').value;
    const expenseTypeSelect = document.getElementById('expense_type');
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
    const category = document.getElementById('edit_category').value;
    const expenseTypeSelect = document.getElementById('edit_expense_type');
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

// Initialize DataTable
if (window.jQuery && $.fn && $.fn.DataTable && $('#combinedTable').length) {
    $('#combinedTable').DataTable({
        "language": {
            "search": "חיפוש:",
            "lengthMenu": "הצג _MENU_ רשומות בעמוד",
            "zeroRecords": "לא נמצאו רשומות מתאימות",
            "info": "מציג _START_ עד _END_ מתוך _TOTAL_ רשומות",
            "infoEmpty": "אין רשומות להצגה",
            "infoFiltered": "(מסונן מתוך _MAX_ רשומות)",
            "paginate": {
                "first": "ראשון",
                "last": "אחרון",
                "next": "הבא",
                "previous": "קודם"
            }
        },
        "order": [[ 0, "desc" ]],
        "pageLength": 25
    });
}

// Add current_tab to all forms on submit
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

// Preserve active tab on filter submit
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
</script>

<?php include '../templates/footer.php'; ?>