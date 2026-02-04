<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper encoding headers
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: he');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

require_once '../config/db.php';
require_once '../vendor/autoload.php';
require_once '../config/auth.php';

session_start();
$message = '';
if (isset($_SESSION['message'])) {

$message = $_SESSION['message'];
    unset($_SESSION['message']);
}

function getInvoiceStorageDir()
{
    $root = dirname(__DIR__, 2); // c:\xampp\htdocs
    $base = dirname($root) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tzucha' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
    return $base;
}

function handleInvoiceUpload($fileInputName, $existingPath = '')
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath ? basename((string)$existingPath) : '';
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
    $uploadDirAbs = getInvoiceStorageDir();
    if (!is_dir($uploadDirAbs)) {
        mkdir($uploadDirAbs, 0775, true);
    }

    $dest = $uploadDirAbs . $newName;
    if (!move_uploaded_file($fileTmp, $dest)) {
        throw new Exception('לא ניתן לשמור את הקובץ שהועלה.');
    }

    return $newName;
}

// Safely delete an existing invoice file from uploads/invoices
function deleteInvoiceFile($path)
{
    if (!$path) {
        return;
    }
    $uploadDirAbs = getInvoiceStorageDir();
    $file = $uploadDirAbs . basename((string)$path);
    if (is_file($file) && file_exists($file)) {
        @unlink($file);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if (!csrf_validate()) {
            $_SESSION['message'] = 'פג תוקף הטופס, נסה שוב.';
            header('Location: expenses.php');
            exit;
        }
        $action = $_POST['action'];
        if ($action == 'copy_fixed') {
            $currentMonth = date('Y-m');
            $stmt = $pdo->prepare("INSERT INTO summary_expenses (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) SELECT date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy FROM fixed_expenses WHERE DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt->execute([$currentMonth]);
            echo "<script>alert('הוצאות הועתקו בהצלחה לסיכום השנתי!');</script>";
            echo "<script>window.location.href = 'expenses.php';</script>";
            exit;
        } elseif ($action == 'bulk_set_fixed_month') {
            $targetMonth = isset($_POST['target_month']) ? trim($_POST['target_month']) : '';
            if (!preg_match('/^\\d{4}-\\d{2}$/', $targetMonth)) {
                $_SESSION['message'] = 'חודש יעד לא תקין. יש לבחור לפי פורמט YYYY-MM.';
                header('Location: expenses.php?tab=fixed');
                exit;
            }

            // Build update to set all fixed_expenses dates into the selected month,
            // clamping the day to the last day of that month when needed.
            $updateSql = "UPDATE fixed_expenses SET date = DATE_ADD(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d'), INTERVAL (LEAST(DAY(date), DAY(LAST_DAY(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d')))) - 1) DAY)";
            $params = [$targetMonth, $targetMonth];

            // Respect current filters if provided (month/year in GET) to affect only visible rows
            $dateFilterParts = [];
            $dateFilterParams = [];
            $filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
            $filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
            if ($filterYear > 0) {
                $dateFilterParts[] = "YEAR(date) = ?";
                $dateFilterParams[] = $filterYear;
            }
            if ($filterMonth > 0) {
                $dateFilterParts[] = "MONTH(date) = ?";
                $dateFilterParams[] = $filterMonth;
            }
            $dateFilterSqlLocal = $dateFilterParts ? (" WHERE " . implode(" AND ", $dateFilterParts)) : '';
            if ($dateFilterSqlLocal) {
                $updateSql .= $dateFilterSqlLocal;
                $params = array_merge($params, $dateFilterParams);
            }

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);

            // Redirect back to fixed tab, presetting filters to target month for convenience
            $y = (int)substr($targetMonth, 0, 4);
            $m = (int)substr($targetMonth, 5, 2);
            header('Location: expenses.php?tab=fixed&year=' . $y . '&month=' . $m);
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
        } elseif ($action == 'add_store') {
            $pdo->prepare("INSERT INTO stores (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name")->execute([$_POST['new_store']]);
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
        } elseif ($action == 'delete_store') {
            $pdo->prepare("DELETE FROM stores WHERE name = ?")->execute([$_POST['delete_item']]);
        } elseif ($action == 'delete_fixed') {
            // Delete invoice file if exists, then remove row
            $stmt = $pdo->prepare("SELECT invoice_copy FROM fixed_expenses WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $inv = $stmt->fetchColumn();
            if ($inv) { deleteInvoiceFile($inv); }
            $pdo->prepare("DELETE FROM fixed_expenses WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'delete_regular') {
            // Delete invoice file if exists, then remove row
            $stmt = $pdo->prepare("SELECT invoice_copy FROM regular_expenses WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $inv = $stmt->fetchColumn();
            if ($inv) { deleteInvoiceFile($inv); }
            $pdo->prepare("DELETE FROM regular_expenses WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'delete_combined') {
            $table = ($_POST['source'] == 'קבועה') ? 'summary_expenses' : 'regular_expenses';
            if (!in_array($table, ['summary_expenses','regular_expenses'], true)) {
                http_response_code(400);
                exit('Invalid table');
            }
            // Delete invoice file if exists, then remove row from the resolved table
            $stmt = $pdo->prepare("SELECT invoice_copy FROM $table WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $inv = $stmt->fetchColumn();
            if ($inv) { deleteInvoiceFile($inv); }
            $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['id']]);
        } elseif ($action == 'import_fixed' || $action == 'import_regular' || $action == 'import_summary') {
            $table = str_replace('import_', '', $action) . '_expenses';
            if (!in_array($table, ['fixed_expenses','regular_expenses','summary_expenses'], true)) {
                http_response_code(400);
                exit('Invalid table');
            }
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
                $file = $_FILES['excel_file']['tmp_name'];
                $fileName = $_FILES['excel_file']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Check file extension
                if (!in_array($fileExtension, ['xlsx', 'xls', 'xlsm'])) {
                    echo "<script>alert('נא להעלות קובץ Excel בלבד (.xlsx או .xls)');</script>";
                    echo "<script>window.location.href = 'expenses.php';</script>";
                    exit;
                }
                
                try {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
                    if (method_exists($reader, 'setReadDataOnly')) {
                        $reader->setReadDataOnly(true);
                    }
                    $spreadsheet = $reader->load($file);
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

                        // Ensure expense type is registered for the category before saving the row
                        ensureExpenseTypeExists($pdo, $row[5] ?? '', $row[6] ?? '');

                        $stmt = $pdo->prepare("INSERT INTO $table (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute($row);
                        $count++;
                        $rowIndex++;
                    }

                    $summaryMsg = "הוצאות הועלו בהצלחה מ-Excel! ({$count} רשומות נוספו, {$skipped} שורות נדחו)";
                    if (!empty($errors)) {
                        $summaryMsg .= "\n" . implode("\n", array_slice($errors, 0, 15));
                        if (count($errors) > 15) {
                            $summaryMsg .= "\n(הוצגו 15 שגיאות ראשונות מתוך " . count($errors) . ")";
                        }
                    }
                    $_SESSION['message'] = nl2br(htmlspecialchars($summaryMsg, ENT_QUOTES, 'UTF-8'));
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
            if (!in_array($table, ['fixed_expenses','regular_expenses','summary_expenses'], true)) {
                http_response_code(400);
                exit('Invalid table');
            }

            // Build filters from current GET (month/year)
            $dateFilterParts = [];
            $dateFilterParams = [];
            $filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
            $filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
            if ($filterYear > 0) {
                $dateFilterParts[] = "YEAR(date) = ?";
                $dateFilterParams[] = $filterYear;
            }
            if ($filterMonth > 0) {
                $dateFilterParts[] = "MONTH(date) = ?";
                $dateFilterParams[] = $filterMonth;
            }
            $whereSql = $dateFilterParts ? (" WHERE " . implode(" AND ", $dateFilterParts)) : '';

            // Apply search term across columns if provided
            $searchTerm = isset($_POST['search_term']) ? trim((string)$_POST['search_term']) : '';
            if ($searchTerm !== '') {
                $likeClauses = [];
                foreach (['date','for_what','store','amount','department','category','expense_type','paid_by','from_account','invoice_copy'] as $col) {
                    $likeClauses[] = "$col LIKE ?";
                    $dateFilterParams[] = '%' . $searchTerm . '%';
                }
                $searchSql = '(' . implode(' OR ', $likeClauses) . ')';
                $whereSql .= ($whereSql ? ' AND ' : ' WHERE ') . $searchSql;
            }

            // Sorting from posted order_by/order_dir (whitelisted)
            $allowedCols = ['date','for_what','store','amount','department','category','expense_type','paid_by','from_account','invoice_copy'];
            $orderBy = isset($_POST['order_by']) ? $_POST['order_by'] : 'date';
            $orderDir = isset($_POST['order_dir']) ? strtolower($_POST['order_dir']) : 'desc';
            if (!in_array($orderBy, $allowedCols, true)) {
                $orderBy = 'date';
            }
            if (!in_array($orderDir, ['asc','desc'], true)) {
                $orderDir = 'desc';
            }

            $sql = "SELECT date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy FROM $table" . $whereSql . " ORDER BY $orderBy $orderDir";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($dateFilterParams);
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
            if ($action == 'add_fixed' || $action == 'edit_fixed') {
                $table = 'fixed_expenses';
            } elseif ($action == 'edit_summary') {
                $table = 'summary_expenses';
            } else {
                $table = 'regular_expenses';
            }
            if (!in_array($table, ['fixed_expenses','regular_expenses','summary_expenses'], true)) {
                http_response_code(400);
                exit('Invalid table');
            }

            try {
                $existingInvoice = isset($_POST['existing_invoice_copy']) ? $_POST['existing_invoice_copy'] : '';
                $invoicePath = handleInvoiceUpload('invoice_copy_file', $existingInvoice);
            } catch (Exception $e) {
                echo "<script>alert('" . addslashes($e->getMessage()) . "');</script>";
                echo "<script>window.location.href = 'expenses.php?tab=" . (isset($_POST['current_tab']) ? $_POST['current_tab'] : '') . "';</script>";
                exit;
            }

            // If a new file was uploaded (path changed), delete the old one
            if (!empty($existingInvoice) && $invoicePath !== $existingInvoice) {
                deleteInvoiceFile($existingInvoice);
            }

            // If requested to delete the existing invoice and no new file replaces it
            $deleteInvoice = isset($_POST['delete_invoice_copy']) && $_POST['delete_invoice_copy'] === '1';
            if ($deleteInvoice) {
                if (!empty($existingInvoice) && $invoicePath === $existingInvoice) {
                    deleteInvoiceFile($existingInvoice);
                }
                if ($invoicePath === $existingInvoice) {
                    $invoicePath = '';
                }
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
            // Make sure the selected expense type is associated with the chosen category
            ensureExpenseTypeExists($pdo, $data['category'] ?? '', $data['expense_type'] ?? '');
            if ($action == 'edit_fixed' || $action == 'edit_regular') {
                $stmt = $pdo->prepare("UPDATE $table SET date=?, for_what=?, store=?, amount=?, department=?, category=?, expense_type=?, paid_by=?, from_account=?, invoice_copy=? WHERE id=?");
                $stmt->execute(array_merge(array_values($data), [$_POST['id']]));
            } else {
                $stmt = $pdo->prepare("INSERT INTO $table (date, for_what, store, amount, department, category, expense_type, paid_by, from_account, invoice_copy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(array_values($data));
            }
        }
        
        // Determine which tab to return to based on the action or current_tab
        $dataActions = ['add_department', 'add_category', 'add_expense_type', 'add_paid_by', 'add_from_account', 'add_store',
                   'delete_department', 'delete_category', 'delete_expense_type', 'delete_paid_by', 'delete_from_account', 'delete_store'];
        
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
// טען רשימת חנויות פעם אחת עם טיפול בשגיאות (אם הטבלה לא קיימת)
$stores = [];
try {
    $stores = $pdo->query("SELECT name FROM stores ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $stores = [];
}

// Fetch expense types grouped by category
$expenseTypesQuery = $pdo->query("SELECT c.name as category, et.name as type FROM categories c LEFT JOIN expense_types et ON c.id = et.category_id ORDER BY c.name, et.name");
$expenseTypes = [];
while ($row = $expenseTypesQuery->fetch()) {
    $expenseTypes[$row['category']][] = $row['type'];
}

function renderImportExportButtons($importAction, $exportAction, $tableId = '')
{
    $tableAttr = $tableId ? " data-table-id=\"{$tableId}\"" : '';
    return "<button type=\"button\" class=\"btn btn-brand\" data-bs-toggle=\"modal\" data-bs-target=\"#importModal\" onclick=\"setImportAction('{$importAction}')\">ייבא מ-Excel</button>\n" .
        "<form method=\"post\" style=\"display: inline; margin: 0;\"{$tableAttr}>\n" .
        "    <input type=\"hidden\" name=\"action\" value=\"{$exportAction}\">\n" .
        "    <input type=\"hidden\" name=\"order_by\" value=\"\">\n" .
        "    <input type=\"hidden\" name=\"order_dir\" value=\"\">\n" .
        "    <input type=\"hidden\" name=\"search_term\" value=\"\">\n" .
        "    <button type=\"submit\" class=\"btn btn-brand\">ייצא ל-Excel</button>\n" .
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
    $form .= "<button type='submit' class='btn btn-sm btn-brand' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק הוצאה זו?\")'>מחק</button>";
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
            $tbody .= '<td>' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    return '<div class="table-scroll" style="max-height: 300px;"><table class="' . $tableClass . ' mb-0">' . $thead . $tbody . '</table></div>';
}

function renderEditButton($type, $row)
{
    $attrs = [
        'type' => $type,
        'id' => $row['id'],
        'date' => $row['date'],
        'for_what' => $row['for_what'],
        'store' => $row['store'],
        'amount' => $row['amount'],
        'department' => $row['department'],
        'category' => $row['category'],
        'expense_type' => $row['expense_type'],
        'paid_by' => $row['paid_by'],
        'from_account' => $row['from_account'],
        'invoice_copy' => $row['invoice_copy']
    ];
    foreach ($attrs as $k => $v) {
        $attrs[$k] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
    return "<button class='btn btn-sm btn-brand' data-bs-toggle='modal' data-bs-target='#editExpenseModal' data-type='{$attrs['type']}' data-id='{$attrs['id']}' data-date='{$attrs['date']}' data-for_what='{$attrs['for_what']}' data-store='{$attrs['store']}' data-amount='{$attrs['amount']}' data-department='{$attrs['department']}' data-category='{$attrs['category']}' data-expense_type='{$attrs['expense_type']}' data-paid_by='{$attrs['paid_by']}' data-from_account='{$attrs['from_account']}' data-invoice_copy='{$attrs['invoice_copy']}' onclick='editExpense(this)'>ערוך</button>";
}

function formatInvoiceCopy($path)
{
    if (!$path) {
        return '';
    }
    $safeFile = htmlspecialchars(basename((string)$path), ENT_QUOTES, 'UTF-8');
    return "<a href=\"/tzucha/pages/download_invoice.php?file={$safeFile}\" target=\"_blank\" rel=\"noopener\">צפה</a>";
}

function renderExpenseRow($row, $type, $deleteForm, $includeSource = false, $includeEdit = true)
{
    $cells = [
        htmlspecialchars((string)$row['date'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['for_what'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['store'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['amount'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['category'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['expense_type'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['paid_by'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$row['from_account'], ENT_QUOTES, 'UTF-8'),
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

// Ensure the expense type exists for the given category (creates if missing)
function ensureExpenseTypeExists(PDO $pdo, $categoryName, $expenseTypeName)
{
    if (!$categoryName || !$expenseTypeName) {
        return;
    }
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$categoryName]);
    $cat = $stmt->fetch();
    if (!$cat || empty($cat['id'])) {
        return;
    }
    $catId = $cat['id'];

    $check = $pdo->prepare("SELECT 1 FROM expense_types WHERE category_id = ? AND name = ? LIMIT 1");
    $check->execute([$catId, $expenseTypeName]);
    if (!$check->fetch()) {
        $insert = $pdo->prepare("INSERT INTO expense_types (category_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        $insert->execute([$catId, $expenseTypeName]);
    }
}
?>
<?php include '../templates/header.php'; ?>
<h2>הוצאות</h2>
<?php if ($message): ?>
<!-- Summary Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">תוצאת ייבוא</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body">
                <?php echo $message; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">סגור</button>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('messageModal');
        if (el) {
            var modal = new bootstrap.Modal(el);
            modal.show();
        }
    });
    </script>
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

$allowedTabs = ['fixed','regular','combined','dashboard','data'];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_COOKIE['expenses_tab']) ? $_COOKIE['expenses_tab'] : 'fixed');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'fixed';
}
// Keep cookie in sync server-side so refresh lands on the same tab
@setcookie('expenses_tab', $activeTab, time() + 31536000, '/');
?>
<div class="sticky-toolbar">
    <form method="get" class="row g-2 align-items-end mb-1" id="filterForm">
        <input type="hidden" name="tab" id="filter_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="col-auto">
            <label for="filter_month" class="form-label visually-hidden">חודש</label>
            <select class="form-select" id="filter_month" name="month">
                <option value="">כל החודשים</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($filterMonth === $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <label for="filter_year" class="form-label visually-hidden">שנה</label>
            <select class="form-select" id="filter_year" name="year">
                <option value="">כל השנים</option>
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?php echo (int)$year; ?>" <?php echo ((int)$year === $filterYear) ? 'selected' : ''; ?>><?php echo (int)$year; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-brand">סנן</button>
            <a id="clearFiltersLink" class="btn btn-brand-outline" href="expenses.php<?php echo $activeTab ? '?tab=' . urlencode($activeTab) : ''; ?>">נקה סינון</a>
        </div>
    </form>
    <ul class="nav nav-tabs" id="expensesTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo ($activeTab === 'fixed') ? 'active' : ''; ?>" id="fixed-tab" data-bs-toggle="tab" href="expenses.php?tab=fixed" data-bs-target="#fixed" role="tab" aria-controls="fixed" aria-selected="<?php echo ($activeTab === 'fixed') ? 'true' : 'false'; ?>">הוצאות קבועות</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo ($activeTab === 'regular') ? 'active' : ''; ?>" id="regular-tab" data-bs-toggle="tab" href="expenses.php?tab=regular" data-bs-target="#regular" role="tab" aria-controls="regular" aria-selected="<?php echo ($activeTab === 'regular') ? 'true' : 'false'; ?>">הוצאות רגילות</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo ($activeTab === 'combined') ? 'active' : ''; ?>" id="combined-tab" data-bs-toggle="tab" href="expenses.php?tab=combined" data-bs-target="#combined" role="tab" aria-controls="combined" aria-selected="<?php echo ($activeTab === 'combined') ? 'true' : 'false'; ?>">סיכום שנתי</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo ($activeTab === 'dashboard') ? 'active' : ''; ?>" id="dashboard-tab" data-bs-toggle="tab" href="expenses.php?tab=dashboard" data-bs-target="#dashboard" role="tab" aria-controls="dashboard" aria-selected="<?php echo ($activeTab === 'dashboard') ? 'true' : 'false'; ?>">דשבורד</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo ($activeTab === 'data') ? 'active' : ''; ?>" id="data-tab" data-bs-toggle="tab" href="expenses.php?tab=data" data-bs-target="#data" role="tab" aria-controls="data" aria-selected="<?php echo ($activeTab === 'data') ? 'true' : 'false'; ?>">נתונים</a>
    </li>
</ul>
</div>

<div class="content-body">
<div class="tab-content" id="expensesTabsContent">
    <div class="tab-pane fade <?php echo ($activeTab === 'fixed') ? 'show active' : ''; ?>" id="fixed" role="tabpanel" aria-labelledby="fixed-tab">
        <div class="table-action-bar">
            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addExpenseModal" onclick="setModal('fixed')">הוסף הוצאה</button>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="copy_fixed">
                <button type="submit" class="btn btn-brand">העתק הוצאות לסיכום</button>
            </form>
            <form method="post" style="display: inline; margin-inline-start: 8px;">
                <input type="hidden" name="action" value="bulk_set_fixed_month">
                <input type="hidden" name="current_tab" value="fixed">
                <label class="form-label" for="bulk_fixed_month" style="margin-inline-end:6px;"></label>
                <input type="month" id="bulk_fixed_month" name="target_month" class="form-control d-inline-block" style="width:auto; display:inline-block; vertical-align:middle;" required>
                <button type="submit" class="btn btn-brand" onclick="return confirm('האם לעדכן את חודש התאריך לכל השורות המוצגות בטבלת הוצאות קבועות?');">עדכן חודש</button>
            </form>
            <?php echo renderImportExportButtons('import_fixed', 'export_fixed', 'fixedTable'); ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-scroll">
                    <table id="fixedTable" class="table table-striped mb-0">
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
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'regular') ? 'show active' : ''; ?>" id="regular" role="tabpanel" aria-labelledby="regular-tab">
        <div class="table-action-bar">
            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addExpenseModal" onclick="setModal('regular')">הוסף הוצאה</button>
            <?php echo renderImportExportButtons('import_regular', 'export_regular', 'regularTable'); ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-scroll">
                    <table id="regularTable" class="table table-striped mb-0">
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
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'data') ? 'show active' : ''; ?>" id="data" role="tabpanel" aria-labelledby="data-tab">
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
                                $safeDept = htmlspecialchars($dept, ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td>{$safeDept}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_department'>
                                            <input type='hidden' name='delete_item' value='{$safeDept}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את האגף \\\"{$safeDept}\\\"\")'>מחק</button>
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
                        <button class="btn btn-brand" type="submit">הוסף אגף</button>
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
                                $safeCat = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td>{$safeCat}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_category'>
                                            <input type='hidden' name='delete_item' value='{$safeCat}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את הקטגוריה \\\"{$safeCat}\\\"\")'>מחק</button>
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
                        <button class="btn btn-brand" type="submit">הוסף קטגוריה</button>
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
                                    $catSafe = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
                                    $typeSafe = htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8');
                                    echo "<tr>
                                        <td>{$catSafe}</td>
                                        <td>{$typeSafe}</td>
                                        <td>
                                            <form method='post' style='display: inline;'>
                                                <input type='hidden' name='action' value='delete_expense_type'>
                                                <input type='hidden' name='delete_item' value='{$typeSafe}'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את סוג ההוצאה \\\"{$typeSafe}\\\"\")'>מחק</button>
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
                        <button class="btn btn-brand" type="submit">הוסף סוג הוצאה</button>
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
                                $safeOpt = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td>{$safeOpt}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_paid_by'>
                                            <input type='hidden' name='delete_item' value='{$safeOpt}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את האפשרות \\\"{$safeOpt}\\\"\")'>מחק</button>
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
                        <button class="btn btn-brand" type="submit">הוסף אפשרות</button>
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
                                $safeAcc = htmlspecialchars($account, ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td>{$safeAcc}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_from_account'>
                                            <input type='hidden' name='delete_item' value='{$safeAcc}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את החשבון \\\"{$safeAcc}\\\"\")'>מחק</button>
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
                        <button class="btn btn-brand" type="submit">הוסף חשבון</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <h5>חנויות</h5>
                <div class="table-scroll" style="max-height: 300px;">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>שם חנות</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($stores as $store) {
                                $safeStore = htmlspecialchars($store, ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td>{$safeStore}</td>
                                    <td>
                                        <form method='post' style='display: inline;'>
                                            <input type='hidden' name='action' value='delete_store'>
                                            <input type='hidden' name='delete_item' value='{$safeStore}'>
                                            <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"האם אתה בטוח שברצונך למחוק את החנות \\\"{$safeStore}\\\"\")'>מחק</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="add_store">
                    <div class="input-group">
                        <input type="text" class="form-control" name="new_store" placeholder="שם חנות חדשה" required>
                        <button class="btn btn-brand" type="submit">הוסף חנות</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'combined') ? 'show active' : ''; ?>" id="combined" role="tabpanel" aria-labelledby="combined-tab">
        <div class="table-action-bar">
            <?php echo renderImportExportButtons('import_summary', 'export_summary', 'combinedTable'); ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-scroll">
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
                    $typeForEdit = ($row['source'] === 'קבועה') ? 'summary' : 'regular';
                    echo renderExpenseRow($row, $typeForEdit, $deleteForm, true, true);
                }
                ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade <?php echo ($activeTab === 'dashboard') ? 'show active' : ''; ?>" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
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
                ?>
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0"><canvas id="deptChart" height="220"></canvas></div>
                    <div class="col-md-6"><?php echo renderSimpleTable(['אגף', 'סכום כולל'], $deptRows); ?></div>
                </div>
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
                ?>
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0"><canvas id="categoryChart" height="220"></canvas></div>
                    <div class="col-md-6"><?php echo renderSimpleTable(['קטגוריה', 'סכום כולל'], $categoryRows); ?></div>
                </div>
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
                ?>
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0"><canvas id="typeChart" height="220"></canvas></div>
                    <div class="col-md-6"><?php echo renderSimpleTable(['סוג הוצאה', 'סכום כולל'], $typeRows); ?></div>
                </div>
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
                ?>
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0"><canvas id="fromAccountChart" height="220"></canvas></div>
                    <div class="col-md-6"><?php echo renderSimpleTable(['מקור תשלום', 'סכום כולל'], $fromAccountRows); ?></div>
                </div>
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
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5>התפלגות כללית</h5>
                        <canvas id="overallChart" height="260"></canvas>
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
                            <?php foreach ($stores as $store) { $s = htmlspecialchars($store, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$s}\">{$s}</option>"; } ?>
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
                            <?php foreach ($departments as $dep) { $d = htmlspecialchars($dep, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$d}\">{$d}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">קטגוריה</label>
                        <select class="form-control" id="category" name="category" onchange="updateExpenseType()">
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $cat) { $c = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$c}\">{$c}</option>"; } ?>
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
                            <?php foreach ($paid_by_options as $pbo) { $p = htmlspecialchars($pbo, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$p}\">{$p}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="from_account" class="form-label">יצא מ</label>
                        <select class="form-control" id="from_account" name="from_account">
                            <option value="">בחר יצא מ</option>
                            <?php foreach ($from_accounts as $fa) { $f = htmlspecialchars($fa, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$f}\">{$f}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_copy_file" class="form-label">העתק חשבונית (תמונה או PDF)</label>
                        <input type="file" class="form-control" id="invoice_copy_file" name="invoice_copy_file" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-brand">שמור</button>
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
                            <?php foreach ($stores as $store) { $s = htmlspecialchars($store, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$s}\">{$s}</option>"; } ?>
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
                            <?php foreach ($departments as $dep) { $d = htmlspecialchars($dep, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$d}\">{$d}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">קטגוריה</label>
                        <select class="form-control" id="edit_category" name="category" onchange="updateEditExpenseType()">
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $cat) { $c = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$c}\">{$c}</option>"; } ?>
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
                            <?php foreach ($paid_by_options as $pbo) { $p = htmlspecialchars($pbo, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$p}\">{$p}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_from_account" class="form-label">יצא מ</label>
                        <select class="form-control" id="edit_from_account" name="from_account">
                            <option value="">בחר יצא מ</option>
                            <?php foreach ($from_accounts as $fa) { $f = htmlspecialchars($fa, ENT_QUOTES, 'UTF-8'); echo "<option value=\"{$f}\">{$f}</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_invoice_copy_file" class="form-label">העתק חשבונית (תמונה או PDF)</label>
                        <input type="file" class="form-control" id="edit_invoice_copy_file" name="invoice_copy_file" accept=".jpg,.jpeg,.png,.pdf">
                        <div id="edit_invoice_copy_preview" style="margin-top: 6px;"></div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="delete_invoice_copy" name="delete_invoice_copy" value="1">
                            <label class="form-check-label" for="delete_invoice_copy">מחק העתק חשבונית</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-brand">עדכן</button>
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
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">ביטול</button>
                    <button type="submit" class="btn btn-brand">ייבא</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        if (invoicePath) {
            preview.innerHTML = `<div class="d-flex align-items-center gap-2">
                <a href="${invoicePath}" target="_blank" rel="noopener">צפה בקובץ קיים</a>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearExistingInvoice()">מחק</button>
            </div>`;
        } else {
            preview.innerHTML = '';
        }
    }
    const delChk = document.getElementById('delete_invoice_copy');
    if (delChk) delChk.checked = false;
    document.getElementById('edit_department').value = button.getAttribute('data-department');
    document.getElementById('edit_category').value = button.getAttribute('data-category');
    updateEditExpenseType();
    document.getElementById('edit_expense_type').value = button.getAttribute('data-expense_type');
    document.getElementById('edit_paid_by').value = button.getAttribute('data-paid_by');
    document.getElementById('edit_from_account').value = button.getAttribute('data-from_account');
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

// Initialize DataTable when DOM and jQuery are ready
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn && $.fn.DataTable) {
        const dtOpts = {
            language: {
                search: "חיפוש:",
                lengthMenu: "הצג _MENU_ רשומות בעמוד",
                zeroRecords: "לא נמצאו רשומות מתאימות",
                info: "מציג _START_ עד _END_ מתוך _TOTAL_ רשומות",
                infoEmpty: "אין רשומות להצגה",
                infoFiltered: "(מסונן מתוך _MAX_ רשומות)",
                paginate: { first: "ראשון", last: "אחרון", next: "הבא", previous: "קודם" }
            },
            order: [[0, 'desc']],
            pageLength: 25
        };

        if ($('#combinedTable').length) {
            $('#combinedTable').DataTable(dtOpts);
        }
        if ($('#fixedTable').length) {
            $('#fixedTable').DataTable(dtOpts);
        }
        if ($('#regularTable').length) {
            $('#regularTable').DataTable(dtOpts);
        }
    }
});

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

// Capture current DataTables sort and pass to export forms
(function() {
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
                    // No DataTable instance; keep defaults
                }
            }
        }
        if (orderByInput) orderByInput.value = col;
        if (orderDirInput) orderDirInput.value = (dir === 'asc' ? 'asc' : 'desc');
        if (searchInput) searchInput.value = search;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form[data-table-id]')
            .forEach(form => {
                form.addEventListener('submit', function() { setOrderInputs(form); });
            });
    });
})();

// Dashboard charts
(function() {
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

    document.addEventListener('DOMContentLoaded', function() {
        const deptRows = <?php echo json_encode(isset($deptRows)?$deptRows:[], JSON_UNESCAPED_UNICODE); ?>;
        const categoryRows = <?php echo json_encode(isset($categoryRows)?$categoryRows:[], JSON_UNESCAPED_UNICODE); ?>;
        const typeRows = <?php echo json_encode(isset($typeRows)?$typeRows:[], JSON_UNESCAPED_UNICODE); ?>;
        const fromAccountRows = <?php echo json_encode(isset($fromAccountRows)?$fromAccountRows:[], JSON_UNESCAPED_UNICODE); ?>;
        const fixedVal = <?php echo json_encode(isset($fixed)?(float)$fixed:0, JSON_UNESCAPED_UNICODE); ?>;
        const regularVal = <?php echo json_encode(isset($regular)?(float)$regular:0, JSON_UNESCAPED_UNICODE); ?>;

        renderPie('deptChart', deptRows, 'התפלגות אגפים');
        renderPie('categoryChart', categoryRows, 'התפלגות קטגוריות');
        renderPie('typeChart', typeRows, 'התפלגות סוגי הוצאה');
        renderPie('fromAccountChart', fromAccountRows, 'התפלגות מקורות תשלום');
        renderDonut('overallChart', ['קבועה','רגילה'], [fixedVal, regularVal], 'התפלגות כללית');
    });
})();
</script>

<?php include '../templates/footer.php'; ?>