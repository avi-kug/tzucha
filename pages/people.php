<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once '../vendor/autoload.php';
session_start();
require_once '../config/auth.php';
auth_require_login($pdo);
auth_require_permission('people');
$canEdit = auth_role() !== 'viewer';

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

// Handle Import/Export for People before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_validate()) {
        $_SESSION['message'] = 'פג תוקף הטופס, נסה שוב.';
        header('Location: people.php');
        exit;
    }
    $action = $_POST['action'];
    if (!$canEdit && in_array($action, ['import_people', 'import_amarchal_list', 'import_gizbar_list'], true)) {
        $_SESSION['message'] = 'אין הרשאה לייבוא נתונים.';
        header('Location: people.php');
        exit;
    }
    if ($action === 'export_people') {
        // Export people to Excel (optionally filtered/ordered)
        $exportIds = $_POST['export_ids'] ?? '';
        if (!empty($exportIds)) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $exportIds))));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT * FROM people WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
                $stmt->execute(array_merge($ids, $ids));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = [];
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM people ORDER BY family_name, first_name");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $headersHe = [
            'אמרכל','גזבר','מזהה תוכנה','מס תורם','חתן הר\'ר','משפחה','שם','שם לדואר','שם ומשפחה ביחד',
            'תעודת זהות בעל','תעודת זהות אשה','כתובת','דואר ל','שכונה / אזור','קומה','עיר','טלפון',
            'נייד בעל','שם האשה','נייד אשה','כתובת מייל מעודכן','מייל בעל','מייל אשה','קבלות ל','אלפון','שליחת הודעות','שינוי אחרון'
        ];
        $dbCols = [
            'amarchal','gizbar','software_id','donor_number','chatan_harar','family_name','first_name','name_for_mail','full_name',
            'husband_id','wife_id','address','mail_to','neighborhood','floor','city','phone',
            'husband_mobile','wife_name','wife_mobile','updated_email','husband_email','wife_email','receipts_to','alphon','send_messages','last_change'
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Headers
        $colIndex = 1;
        foreach ($headersHe as $h) {
            $sheet->setCellValueByColumnAndRow($colIndex++, 1, $h);
        }
        // Data
        $r = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($dbCols as $c) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $r, $row[$c] ?? '');
            }
            $r++;
        }

        $filename = 'people_' . date('Y-m-d_H-i-s') . '.xlsx';
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    } elseif ($action === 'export_amarchal_list' || $action === 'export_gizbar_list') {
        $isAmarchal = $action === 'export_amarchal_list';
        $nameField = $isAmarchal ? 'amarchal' : 'gizbar';
        $titlePrefix = $isAmarchal ? 'amarchal_list_' : 'gizbar_list_';

        $exportIds = $_POST['export_ids'] ?? '';
        if (!empty($exportIds)) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $exportIds))));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT * FROM people WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
                $stmt->execute(array_merge($ids, $ids));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = [];
            }
        } else {
            $query = $isAmarchal
                ? "SELECT p.* FROM people p INNER JOIN (SELECT amarchal, MIN(id) AS min_id FROM people WHERE amarchal IS NOT NULL AND amarchal <> '' GROUP BY amarchal) t ON p.id = t.min_id ORDER BY p.amarchal"
                : "SELECT p.* FROM people p INNER JOIN (SELECT gizbar, MIN(id) AS min_id FROM people WHERE gizbar IS NOT NULL AND gizbar <> '' GROUP BY gizbar) t ON p.id = t.min_id ORDER BY p.gizbar";
            $rows = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        }

        $headersHe = [
            $isAmarchal ? 'אמרכל' : 'גזבר',
            'תעודת זהות בעל',
            'כתובת',
            'שכונה',
            'קומה',
            'עיר',
            'טלפון',
            'פלאפון',
            'מייל'
        ];
        if (!$isAmarchal) {
            $headersHe[] = 'אמרכל';
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $colIndex = 1;
        foreach ($headersHe as $h) {
            $sheet->setCellValueByColumnAndRow($colIndex++, 1, $h);
        }

        $r = 2;
        foreach ($rows as $row) {
            $email = $row['updated_email'] ?? '';
            if ($email === '') {
                $email = $row['husband_email'] ?? '';
            }
            $sheet->setCellValueByColumnAndRow(1, $r, $row[$nameField] ?? '');
            $sheet->setCellValueByColumnAndRow(2, $r, $row['husband_id'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $r, $row['address'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $r, $row['neighborhood'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $r, $row['floor'] ?? '');
            $sheet->setCellValueByColumnAndRow(6, $r, $row['city'] ?? '');
            $sheet->setCellValueByColumnAndRow(7, $r, $row['phone'] ?? '');
            $sheet->setCellValueByColumnAndRow(8, $r, $row['husband_mobile'] ?? '');
            $sheet->setCellValueByColumnAndRow(9, $r, $email);
            if (!$isAmarchal) {
                $sheet->setCellValueByColumnAndRow(10, $r, $row['amarchal'] ?? '');
            }
            $r++;
        }

        $filename = $titlePrefix . date('Y-m-d_H-i-s') . '.xlsx';
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    } elseif ($action === 'import_amarchal_list' || $action === 'import_gizbar_list') {
        $isAmarchal = $action === 'import_amarchal_list';
        $title = $isAmarchal ? 'אמרכלים' : 'גזברים';
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['message'] = 'שגיאה בהעלאת הקובץ. נא לנסות שוב.';
            header('Location: people.php');
            exit;
        }
        try {
            validateExcelUpload($_FILES['excel_file']);
        } catch (Exception $e) {
            $_SESSION['message'] = nl2br(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        }
        $tmp = $_FILES['excel_file']['tmp_name'];
        
        // Check file exists and is readable
        if (!file_exists($tmp) || !is_readable($tmp)) {
            $_SESSION['message'] = 'הקובץ לא נמצא או לא ניתן לקרוא אותו.';
            header('Location: people.php');
            exit;
        }
        
        // Log file info
        error_log("Import {$title} - File size: " . filesize($tmp) . " bytes, Memory limit: " . ini_get('memory_limit'));
        
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            if (method_exists($reader, 'setReadDataOnly')) { $reader->setReadDataOnly(true); }
            $spreadsheet = $reader->load($tmp);
            $ws = $spreadsheet->getActiveSheet();
            $rows = $ws->toArray();
            if (!$rows || count($rows) < 2) {
                $_SESSION['message'] = nl2br(htmlspecialchars('קובץ ריק או ללא שורות נתונים.', ENT_QUOTES, 'UTF-8'));
                header('Location: people.php');
                exit;
            }
            $header = array_map('trim', $rows[0]);
            $nameHeader = $isAmarchal ? 'אמרכל' : 'גזבר';
            $map = [
                $nameHeader => 'name',
                'תעודת זהות בעל' => 'husband_id',
                'כתובת' => 'address',
                'שכונה' => 'neighborhood',
                'שכונה / אזור' => 'neighborhood',
                'קומה' => 'floor',
                'עיר' => 'city',
                'טלפון' => 'phone',
                'פלאפון' => 'husband_mobile',
                'נייד בעל' => 'husband_mobile',
                'מייל' => 'updated_email',
                'כתובת מייל מעודכן' => 'updated_email'
            ];

            $indexes = [];
            foreach ($header as $i => $h) {
                if (isset($map[$h])) {
                    $indexes[$i] = $map[$h];
                }
            }

            $selectStmt = $pdo->prepare($isAmarchal
                ? "SELECT id FROM people WHERE amarchal = ? LIMIT 1"
                : "SELECT id FROM people WHERE gizbar = ? LIMIT 1"
            );
            $updateStmt = $pdo->prepare("UPDATE people SET husband_id = ?, address = ?, neighborhood = ?, floor = ?, city = ?, phone = ?, husband_mobile = ?, updated_email = ? WHERE id = ?");

            $updated = 0; $skipped = 0; $errors = [];
            for ($r = 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                $excelRow = $r + 1;
                if (!is_array($row) || count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: שורה ריקה.";
                    continue;
                }
                $data = [
                    'name' => '',
                    'husband_id' => '',
                    'address' => '',
                    'neighborhood' => '',
                    'floor' => '',
                    'city' => '',
                    'phone' => '',
                    'husband_mobile' => '',
                    'updated_email' => ''
                ];
                foreach ($indexes as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim((string)$row[$i]) : '';
                }
                if ($data['name'] === '') {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: חסר {$nameHeader}.";
                    continue;
                }

                $selectStmt->execute([$data['name']]);
                $foundId = $selectStmt->fetchColumn();
                if ($foundId) {
                    $updateStmt->execute([
                        $data['husband_id'],
                        $data['address'],
                        $data['neighborhood'],
                        $data['floor'],
                        $data['city'],
                        $data['phone'],
                        $data['husband_mobile'],
                        $data['updated_email'],
                        $foundId
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: {$nameHeader} לא נמצא במערכת.";
                }
            }

            $summaryMsg = "ייבוא {$title} הושלם: עודכנו {$updated}, דולגו {$skipped}.";
            if (!empty($errors)) {
                $summaryMsg .= "\n" . implode("\n", array_slice($errors, 0, 15));
                if (count($errors) > 15) {
                    $summaryMsg .= "\n(הוצגו 15 שגיאות ראשונות מתוך " . count($errors) . ")";
                }
            }
            $_SESSION['message'] = nl2br(htmlspecialchars($summaryMsg, ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        } catch (Exception $e) {
            error_log('Import Amarchal/Gizbar Error: ' . $e->getMessage());
            $_SESSION['message'] = nl2br(htmlspecialchars('שגיאה בקריאת הקובץ: ' . $e->getMessage(), ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        }
    } elseif ($action === 'import_people') {
        // Import people from Excel
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['message'] = 'שגיאה בהעלאת הקובץ. נא לנסות שוב.';
            header('Location: people.php');
            exit;
        }
        try {
            validateExcelUpload($_FILES['excel_file']);
        } catch (Exception $e) {
            $_SESSION['message'] = nl2br(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        }
        $tmp = $_FILES['excel_file']['tmp_name'];
        
        // Check file exists and is readable
        if (!file_exists($tmp) || !is_readable($tmp)) {
            $_SESSION['message'] = 'הקובץ לא נמצא או לא ניתן לקרוא אותו.';
            header('Location: people.php');
            exit;
        }
        
        // Check PHP limits and log for debugging
        $fileSize = filesize($tmp);
        $memoryLimit = ini_get('memory_limit');
        $uploadMaxSize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        error_log("Import People - File size: {$fileSize} bytes, Memory: {$memoryLimit}, Upload max: {$uploadMaxSize}, Post max: {$postMaxSize}");
        
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            if (method_exists($reader, 'setReadDataOnly')) { $reader->setReadDataOnly(true); }
            $spreadsheet = $reader->load($tmp);
            $ws = $spreadsheet->getActiveSheet();
            $rows = $ws->toArray();
            if (!$rows || count($rows) < 2) {
                $_SESSION['message'] = nl2br(htmlspecialchars('קובץ ריק או ללא שורות נתונים.', ENT_QUOTES, 'UTF-8'));
                header('Location: people.php');
                exit;
            }
            $header = array_map('trim', $rows[0]);
            // Map Hebrew headers to DB columns
            $map = [
                'אמרכל' => 'amarchal',
                'גזבר' => 'gizbar',
                'מזהה תוכנה' => 'software_id',
                'מס תורם' => 'donor_number',
                'חתן הר\'ר' => 'chatan_harar',
                'משפחה' => 'family_name',
                'שם' => 'first_name',
                'שם לדואר' => 'name_for_mail',
                'שם ומשפחה ביחד' => 'full_name',
                'תעודת זהות בעל' => 'husband_id',
                'תעודת זהות אשה' => 'wife_id',
                'כתובת' => 'address',
                'דואר ל' => 'mail_to',
                'שכונה / אזור' => 'neighborhood',
                'קומה' => 'floor',
                'עיר' => 'city',
                'טלפון' => 'phone',
                'נייד בעל' => 'husband_mobile',
                'שם האשה' => 'wife_name',
                'נייד אשה' => 'wife_mobile',
                'כתובת מייל מעודכן' => 'updated_email',
                'מייל בעל' => 'husband_email',
                'מייל אשה' => 'wife_email',
                'קבלות ל' => 'receipts_to',
                'אלפון' => 'alphon',
                'שליחת הודעות' => 'send_messages'
            ];

            $indexes = [];
            foreach ($header as $i => $h) {
                if (isset($map[$h])) {
                    $indexes[$i] = $map[$h];
                }
            }

            $select = $pdo->prepare("SELECT id FROM people WHERE full_name = ? LIMIT 1");
            $insert = $pdo->prepare("INSERT INTO people (
                amarchal, gizbar, software_id, donor_number, chatan_harar, family_name, first_name,
                name_for_mail, full_name, husband_id, wife_id, address, mail_to, neighborhood,
                floor, city, phone, husband_mobile, wife_name, wife_mobile, updated_email,
                husband_email, wife_email, receipts_to, alphon, send_messages
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $update = $pdo->prepare("UPDATE people SET
                amarchal = ?, gizbar = ?, software_id = ?, donor_number = ?, chatan_harar = ?, family_name = ?, first_name = ?,
                name_for_mail = ?, full_name = ?, husband_id = ?, wife_id = ?, address = ?, mail_to = ?, neighborhood = ?,
                floor = ?, city = ?, phone = ?, husband_mobile = ?, wife_name = ?, wife_mobile = ?, updated_email = ?,
                husband_email = ?, wife_email = ?, receipts_to = ?, alphon = ?, send_messages = ?
                WHERE id = ?
            ");

            $added = 0; $updated = 0; $skipped = 0; $errors = [];
            $processedNames = []; // Track names processed in this file to prevent duplicates
            
            for ($r = 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                $excelRow = $r + 1; // 1-based with header at row 1
                if (!is_array($row) || count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: שורה ריקה.";
                    continue;
                }
                $data = array_fill_keys(array_values($map), '');
                foreach ($indexes as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim((string)$row[$i]) : '';
                }
                $fullName = trim((string)($data['full_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = trim((string)($data['family_name'] ?? '') . ' ' . (string)($data['first_name'] ?? ''));
                }
                if ($fullName === '') {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: חסרים שדות חובה (שם ומשפחה ביחד).";
                    continue;
                }
                $data['full_name'] = $fullName;
                
                // Check for duplicate names within the same file
                $nameKey = mb_strtolower($fullName);
                if (isset($processedNames[$nameKey])) {
                    $skipped++;
                    $errors[] = "שורה {$excelRow}: שם '{$fullName}' כבר קיים בקובץ בשורה {$processedNames[$nameKey]} - דילוג";
                    continue;
                }
                $processedNames[$nameKey] = $excelRow;

                $select->execute([$fullName]);
                $existingId = $select->fetchColumn();
                
                try {
                    if ($existingId) {
                        $update->execute([
                            $data['amarchal'] ?? '', $data['gizbar'] ?? '', $data['software_id'] ?? '', $data['donor_number'] ?? '',
                            $data['chatan_harar'] ?? '', $data['family_name'] ?? '', $data['first_name'] ?? '', $data['name_for_mail'] ?? '',
                            $data['full_name'] ?? '', $data['husband_id'] ?? '', $data['wife_id'] ?? '', $data['address'] ?? '', $data['mail_to'] ?? '',
                            $data['neighborhood'] ?? '', $data['floor'] ?? '', $data['city'] ?? '', $data['phone'] ?? '', $data['husband_mobile'] ?? '',
                            $data['wife_name'] ?? '', $data['wife_mobile'] ?? '', $data['updated_email'] ?? '', $data['husband_email'] ?? '',
                            $data['wife_email'] ?? '', $data['receipts_to'] ?? '', $data['alphon'] ?? '', $data['send_messages'] ?? '',
                            $existingId
                        ]);
                        $updated++;
                    } else {
                        $insert->execute([
                            $data['amarchal'] ?? '', $data['gizbar'] ?? '', $data['software_id'] ?? '', $data['donor_number'] ?? '',
                            $data['chatan_harar'] ?? '', $data['family_name'] ?? '', $data['first_name'] ?? '', $data['name_for_mail'] ?? '',
                            $data['full_name'] ?? '', $data['husband_id'] ?? '', $data['wife_id'] ?? '', $data['address'] ?? '', $data['mail_to'] ?? '',
                            $data['neighborhood'] ?? '', $data['floor'] ?? '', $data['city'] ?? '', $data['phone'] ?? '', $data['husband_mobile'] ?? '',
                            $data['wife_name'] ?? '', $data['wife_mobile'] ?? '', $data['updated_email'] ?? '', $data['husband_email'] ?? '',
                            $data['wife_email'] ?? '', $data['receipts_to'] ?? '', $data['alphon'] ?? '', $data['send_messages'] ?? ''
                        ]);
                        $added++;
                    }
                } catch (PDOException $e) {
                    $skipped++;
                    if ($e->getCode() == 23000) {
                        // Duplicate entry error
                        if (strpos($e->getMessage(), 'PRIMARY') !== false) {
                            $errors[] = "שורה {$excelRow}: שגיאת מפתח ראשי - ייתכן שיש בעיה ב-AUTO_INCREMENT של הטבלה. נא לפנות למנהל מערכת.";
                        } else {
                            $errors[] = "שורה {$excelRow}: שם '{$fullName}' כבר קיים במערכת.";
                        }
                    } else {
                        error_log('Import People DB Error (row ' . $excelRow . '): ' . $e->getMessage());
                        $errors[] = "שורה {$excelRow}: שגיאת מסד נתונים - {$e->getMessage()}";
                    }
                }
            }
            $summaryMsg = "ייבוא הושלם: עודכנו {$updated}, נוספו {$added}, דולגו {$skipped}.";
            if (!empty($errors)) {
                $summaryMsg .= "\n" . implode("\n", array_slice($errors, 0, 15));
                if (count($errors) > 15) {
                    $summaryMsg .= "\n(הוצגו 15 שגיאות ראשונות מתוך " . count($errors) . ")";
                }
            }
            $_SESSION['message'] = nl2br(htmlspecialchars($summaryMsg, ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        } catch (Exception $e) {
            error_log('Import People Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $_SESSION['message'] = nl2br(htmlspecialchars('שגיאה בקריאת הקובץ: ' . $e->getMessage() . '\n\nאם הקובץ גדול, נסה להקטין אותו או להגדיל את גבול הזיכרון.', ENT_QUOTES, 'UTF-8'));
            header('Location: people.php');
            exit;
        }
    }
}

// Capture any flash message for modal display
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

include '../templates/header.php';
?>

<link rel="stylesheet" href="../assets/css/people.css">
<?php if (!empty($message)): ?>
<!-- Summary Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
    </div>
<?php endif; ?>
<?php
$allowedTabs = ['full','amarchal','gizbar'];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'full';
if (!in_array($activeTab, $allowedTabs, true)) { $activeTab = 'full'; }

// Preload amarchal/gizbar rows (one representative row per name)
$amarchalRows = $pdo->query("SELECT p.* FROM people p INNER JOIN (SELECT amarchal, MIN(id) AS min_id FROM people WHERE amarchal IS NOT NULL AND amarchal <> '' GROUP BY amarchal) t ON p.id = t.min_id ORDER BY p.amarchal")->fetchAll(PDO::FETCH_ASSOC);
$gizbarRows = $pdo->query("SELECT p.* FROM people p INNER JOIN (SELECT gizbar, MIN(id) AS min_id FROM people WHERE gizbar IS NOT NULL AND gizbar <> '' GROUP BY gizbar) t ON p.id = t.min_id ORDER BY p.gizbar")->fetchAll(PDO::FETCH_ASSOC);
$peopleList = $pdo->query("SELECT id, full_name, family_name, first_name FROM people ORDER BY family_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
$amarchalim = $pdo->query("SELECT DISTINCT amarchal FROM people WHERE amarchal IS NOT NULL AND amarchal <> '' ORDER BY amarchal")->fetchAll(PDO::FETCH_COLUMN);
$gizbarim = $pdo->query("SELECT DISTINCT gizbar FROM people WHERE gizbar IS NOT NULL AND gizbar <> '' ORDER BY gizbar")->fetchAll(PDO::FETCH_COLUMN);
$gizbarToAmarchal = [];
$gizbarMapRows = $pdo->query("SELECT gizbar, amarchal FROM people WHERE gizbar IS NOT NULL AND gizbar <> '' AND amarchal IS NOT NULL AND amarchal <> '' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($gizbarMapRows as $row) {
    $g = trim((string)($row['gizbar'] ?? ''));
    $a = trim((string)($row['amarchal'] ?? ''));
    if ($g !== '' && $a !== '' && !isset($gizbarToAmarchal[$g])) {
        $gizbarToAmarchal[$g] = $a;
    }
}
?>

<!-- Tabs Navigation -->
<div class="tabs-nav">
    <button class="tab-btn<?php echo ($activeTab==='full')?' active':''; ?>" data-tab="full">פרטים מלאים</button>
    <button class="tab-btn<?php echo ($activeTab==='amarchal')?' active':''; ?>" data-tab="amarchal">רשימת אמרכלים</button>
    <button class="tab-btn<?php echo ($activeTab==='gizbar')?' active':''; ?>" data-tab="gizbar">רשימת גזברים</button>
</div>

<div class="tab-panel<?php echo ($activeTab==='full')?' active' : ''; ?>" id="full-tab">
    <div id="full">
        <h2>רשימת אנשים</h2>
        <div class="card fixed-card">
            <div class="card-body">
                <div class="table-action-bar" id="peopleActionBar">
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-brand" id="addPersonBtn">הוסף חדש</button>
                        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importPeopleModal">ייבא אקסל</button>
                    <?php endif; ?>
                    <form method="post" style="display:inline; margin:0;" id="exportPeopleForm">
                        <input type="hidden" name="action" value="export_people">
                        <input type="hidden" name="export_ids" value="">
                        <button type="submit" class="btn btn-brand">ייצוא אקסל</button>
                    </form>
                    <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#printPdfModal"><i class="bi bi-file-pdf"></i> הדפסה ל-PDF</button>
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-danger" id="deleteSelectedBtn" disabled>מחיקת מסומנים</button>
                    <?php endif; ?>
                </div>
                <div class="table-scroll">
                    <table id="peopleTable" class="table table-striped mb-0" style="width:100%">
                        <colgroup>
                            <col style="width:40px">
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 28px;" class="text-center select-col">
                                    <?php if ($canEdit): ?>
                                        <input type="checkbox" id="selectAllRows">
                                    <?php else: ?>
                                        &nbsp;
                                    <?php endif; ?>
                                </th>
                                <th>אמרכל</th>
                                <th>גזבר</th>
                                <th>מזהה תוכנה</th>
                                <th>מס תורם</th>
                                <th>חתן הר\'ר</th>
                                <th>משפחה</th>
                                <th>שם</th>
                                <th>שם לדואר</th>
                                <th>שם ומשפחה ביחד</th>
                                <th>תעודת זהות בעל</th>
                                <th>תעודת זהות אשה</th>
                                <th>כתובת</th>
                                <th>דואר ל</th>
                                <th>שכונה / אזור</th>
                                <th>קומה</th>
                                <th>עיר</th>
                                <th>טלפון</th>
                                <th>נייד בעל</th>
                                <th>שם האשה</th>
                                <th>נייד אשה</th>
                                <th>כתובת מייל מעודכן</th>
                                <th>מייל בעל</th>
                                <th>מייל אשה</th>
                                <th>קבלות ל</th>
                                <th>אלפון</th>
                                <th>שליחת הודעות</th>
                                <th>שינוי אחרון</th>
                                <th class="action-col">פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php
                                $cellClass = $canEdit ? 'editable' : '';
                                $dupStmt = $pdo->query("SELECT full_name FROM people WHERE full_name IS NOT NULL AND full_name <> '' GROUP BY full_name HAVING COUNT(*) > 1");
                                $dupKeys = [];
                                foreach ($dupStmt->fetchAll(PDO::FETCH_ASSOC) as $dupRow) {
                                    $key = mb_strtolower(trim((string)$dupRow['full_name']));
                                $dupKeys[$key] = true;
                                }

                                $stmt = $pdo->query("SELECT * FROM people ORDER BY family_name, first_name");
                                while ($row = $stmt->fetch()) {
                                    $id = $row['id'];
                                    $rowKey = mb_strtolower(trim((string)($row['full_name'] ?? '')));
                                    $dupClass = isset($dupKeys[$rowKey]) ? ' duplicate-name' : '';
                                    echo "<tr data-id='$id' class='{$dupClass}'>";
                                    if ($canEdit) {
                                        echo "<td class='text-center select-col'><input type='checkbox' class='row-select' data-id='$id'></td>";
                                    } else {
                                        echo "<td class='text-center select-col'></td>";
                                    }
                                    echo "<td class='{$cellClass}' data-field='amarchal'>" . htmlspecialchars($row['amarchal'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='gizbar'>" . htmlspecialchars($row['gizbar'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='software_id'>" . htmlspecialchars($row['software_id'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='donor_number'>" . htmlspecialchars($row['donor_number'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='chatan_harar'>" . htmlspecialchars($row['chatan_harar'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='family_name'>" . htmlspecialchars($row['family_name'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='first_name'>" . htmlspecialchars($row['first_name'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='name_for_mail'>" . htmlspecialchars($row['name_for_mail'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='full_name'>" . htmlspecialchars($row['full_name'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='husband_id'>" . htmlspecialchars($row['husband_id'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='wife_id'>" . htmlspecialchars($row['wife_id'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='address'>" . htmlspecialchars($row['address'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='mail_to'>" . htmlspecialchars($row['mail_to'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='neighborhood'>" . htmlspecialchars($row['neighborhood'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='floor'>" . htmlspecialchars($row['floor'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='city'>" . htmlspecialchars($row['city'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='phone'>" . htmlspecialchars($row['phone'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='husband_mobile'>" . htmlspecialchars($row['husband_mobile'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='wife_name'>" . htmlspecialchars($row['wife_name'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='wife_mobile'>" . htmlspecialchars($row['wife_mobile'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='updated_email'>" . htmlspecialchars($row['updated_email'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='husband_email'>" . htmlspecialchars($row['husband_email'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='wife_email'>" . htmlspecialchars($row['wife_email'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='receipts_to'>" . htmlspecialchars($row['receipts_to'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='alphon'>" . htmlspecialchars($row['alphon'] ?? '') . "</td>";
                                    echo "<td class='{$cellClass}' data-field='send_messages'>" . htmlspecialchars($row['send_messages'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['last_change'] ?? '') . "</td>";
                                    echo "<td class='text-center action-cell'>";
                                    if ($canEdit) {
                                        echo "<div class='row-actions'>";
                                        echo "<button class='btn btn-sm btn-warning edit-btn me-1' data-id='$id' title='ערוך'><i class='bi bi-pencil'></i></button>";
                                        echo "<button class='btn btn-sm btn-danger delete-btn' data-id='$id' title='מחק'><i class='bi bi-trash'></i></button>";
                                        echo "</div>";
                                    } else {
                                        echo "<span class='text-muted'>צפייה בלבד</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-panel<?php echo ($activeTab==='amarchal')?' active' : ''; ?>" id="amarchal-tab">
    <div id="amarchal">
        <h2>רשימת אמרכלים</h2>
        <div class="card fixed-card">
            <div class="card-body">
                <div class="table-action-bar" id="amarchalActionBar">
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-brand" id="addAmarchalBtn">הוסף חדש</button>
                        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importAmarchalModal">ייבא אקסל</button>
                    <?php endif; ?>
                    <form method="post" style="display:inline; margin:0;" id="exportAmarchalForm">
                        <input type="hidden" name="action" value="export_amarchal_list">
                        <input type="hidden" name="export_ids" value="">
                        <button type="submit" class="btn btn-brand">ייצוא אקסל</button>
                    </form>
                </div>
                <div class="table-scroll">
                    <table id="amarchalTable" class="table table-striped mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>אמרכל</th>
                                <th>תעודת זהות בעל</th>
                                <th>כתובת</th>
                                <th>שכונה</th>
                                <th>קומה</th>
                                <th>עיר</th>
                                <th>טלפון</th>
                                <th>פלאפון</th>
                                <th>מייל</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($amarchalRows as $row):
                                $email = $row['updated_email'] ?? '';
                                if ($email === '') { $email = $row['husband_email'] ?? ''; }
                                $safeName = htmlspecialchars($row['amarchal'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeId = htmlspecialchars($row['husband_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeAddress = htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeNeighborhood = htmlspecialchars($row['neighborhood'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeFloor = htmlspecialchars($row['floor'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeCity = htmlspecialchars($row['city'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safePhone = htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeMobile = htmlspecialchars($row['husband_mobile'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                                echo "<tr><td>{$safeName}</td><td>{$safeId}</td><td>{$safeAddress}</td><td>{$safeNeighborhood}</td><td>{$safeFloor}</td><td>{$safeCity}</td><td>{$safePhone}</td><td>{$safeMobile}</td><td>{$safeEmail}</td></tr>";
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-panel<?php echo ($activeTab==='gizbar')?' active' : ''; ?>" id="gizbar-tab">
    <div id="gizbar">
        <h2>רשימת גזברים</h2>
        <div class="card fixed-card">
            <div class="card-body">
                <div class="table-action-bar" id="gizbarActionBar">
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-brand" id="addGizbarBtn">הוסף חדש</button>
                        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importGizbarModal">ייבא אקסל</button>
                    <?php endif; ?>
                    <form method="post" style="display:inline; margin:0;" id="exportGizbarForm">
                        <input type="hidden" name="action" value="export_gizbar_list">
                        <input type="hidden" name="export_ids" value="">
                        <button type="submit" class="btn btn-brand">ייצוא אקסל</button>
                    </form>
                </div>
                <div class="table-scroll">
                    <table id="gizbarTable" class="table table-striped mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>גזבר</th>
                                <th>תעודת זהות בעל</th>
                                <th>כתובת</th>
                                <th>שכונה</th>
                                <th>קומה</th>
                                <th>עיר</th>
                                <th>טלפון</th>
                                <th>פלאפון</th>
                                <th>מייל</th>
                                <th>אמרכל</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gizbarRows as $row):
                                $email = $row['updated_email'] ?? '';
                                if ($email === '') { $email = $row['husband_email'] ?? ''; }
                                $safeName = htmlspecialchars($row['gizbar'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeId = htmlspecialchars($row['husband_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeAddress = htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeNeighborhood = htmlspecialchars($row['neighborhood'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeFloor = htmlspecialchars($row['floor'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeCity = htmlspecialchars($row['city'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safePhone = htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeMobile = htmlspecialchars($row['husband_mobile'] ?? '', ENT_QUOTES, 'UTF-8');
                                $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                                $safeAmarchal = htmlspecialchars($row['amarchal'] ?? '', ENT_QUOTES, 'UTF-8');
                                echo "<tr><td>{$safeName}</td><td>{$safeId}</td><td>{$safeAddress}</td><td>{$safeNeighborhood}</td><td>{$safeFloor}</td><td>{$safeCity}</td><td>{$safePhone}</td><td>{$safeMobile}</td><td>{$safeEmail}</td><td>{$safeAmarchal}</td></tr>";
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Person -->
<div class="modal fade" id="personModal" tabindex="-1" aria-labelledby="personModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personModalLabel">הוסף איש קשר</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="personForm">
                    <input type="hidden" id="person_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="amarchal" class="form-label">אמרכל</label>
                            <select class="form-select" id="amarchal" name="amarchal">
                                <option value="">בחר...</option>
                                <?php foreach ($amarchalim as $name): ?>
                                    <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="gizbar" class="form-label">גזבר</label>
                            <select class="form-select" id="gizbar" name="gizbar">
                                <option value="">בחר...</option>
                                <?php foreach ($gizbarim as $name): ?>
                                    <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="software_id" class="form-label">מזהה תוכנה</label>
                            <input type="text" class="form-control" id="software_id" name="software_id">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="donor_number" class="form-label">מס תורם</label>
                            <input type="text" class="form-control" id="donor_number" name="donor_number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="chatan_harar" class="form-label">חתן הר\'ר</label>
                            <input type="text" class="form-control" id="chatan_harar" name="chatan_harar">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="family_name" class="form-label">משפחה <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="family_name" name="family_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">שם <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name_for_mail" class="form-label">שם לדואר</label>
                            <input type="text" class="form-control" id="name_for_mail" name="name_for_mail">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">שם ומשפחה ביחד</label>
                            <input type="text" class="form-control" id="full_name" name="full_name">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="husband_id" class="form-label">תעודת זהות בעל</label>
                            <input type="text" class="form-control" id="husband_id" name="husband_id">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="wife_id" class="form-label">תעודת זהות אשה</label>
                            <input type="text" class="form-control" id="wife_id" name="wife_id">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">כתובת</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="mail_to" class="form-label">דואר ל</label>
                            <input type="text" class="form-control" id="mail_to" name="mail_to">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="neighborhood" class="form-label">שכונה / אזור</label>
                            <input type="text" class="form-control" id="neighborhood" name="neighborhood">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="floor" class="form-label">קומה</label>
                            <input type="text" class="form-control" id="floor" name="floor">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="city" class="form-label">עיר</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">טלפון</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="husband_mobile" class="form-label">נייד בעל</label>
                            <input type="text" class="form-control" id="husband_mobile" name="husband_mobile">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="wife_name" class="form-label">שם האשה</label>
                            <input type="text" class="form-control" id="wife_name" name="wife_name">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="wife_mobile" class="form-label">נייד אשה</label>
                        <input type="text" class="form-control" id="wife_mobile" name="wife_mobile">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="updated_email" class="form-label">כתובת מייל מעודכן</label>
                            <input type="email" class="form-control" id="updated_email" name="updated_email">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="husband_email" class="form-label">מייל בעל</label>
                            <input type="email" class="form-control" id="husband_email" name="husband_email">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="wife_email" class="form-label">מייל אשה</label>
                            <input type="email" class="form-control" id="wife_email" name="wife_email">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="receipts_to" class="form-label">קבלות ל</label>
                            <input type="text" class="form-control" id="receipts_to" name="receipts_to">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="alphon" class="form-label">אלפון</label>
                            <input type="text" class="form-control" id="alphon" name="alphon">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="send_messages" class="form-label">שליחת הודעות</label>
                            <input type="text" class="form-control" id="send_messages" name="send_messages">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="savePersonBtn">שמור</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add Amarchal from full list -->
<div class="modal fade" id="addAmarchalModal" tabindex="-1" aria-labelledby="addAmarchalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAmarchalModalLabel">הוסף אמרכל מהרשימה הכללית</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body">
                <label for="amarchalPersonSelect" class="form-label">בחר שם ומשפחה ביחד</label>
                <select id="amarchalPersonSelect" class="form-select">
                    <option value="">בחר...</option>
                    <?php foreach ($peopleList as $p):
                        $displayName = trim((string)($p['full_name'] ?? ''));
                        if ($displayName === '') {
                            $displayName = trim((string)($p['family_name'] ?? '') . ' ' . (string)($p['first_name'] ?? ''));
                        }
                        $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
                        $safeId = htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8');
                        echo "<option value=\"{$safeId}\" data-name=\"{$safeName}\">{$safeName}</option>";
                    endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="saveAmarchalBtn">הוסף</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add Gizbar from full list -->
<div class="modal fade" id="addGizbarModal" tabindex="-1" aria-labelledby="addGizbarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGizbarModalLabel">הוסף גזבר מהרשימה הכללית</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body">
                <label for="gizbarPersonSelect" class="form-label">בחר שם ומשפחה ביחד</label>
                <select id="gizbarPersonSelect" class="form-select">
                    <option value="">בחר...</option>
                    <?php foreach ($peopleList as $p):
                        $displayName = trim((string)($p['full_name'] ?? ''));
                        if ($displayName === '') {
                            $displayName = trim((string)($p['family_name'] ?? '') . ' ' . (string)($p['first_name'] ?? ''));
                        }
                        $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
                        $safeId = htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8');
                        echo "<option value=\"{$safeId}\" data-name=\"{$safeName}\">{$safeName}</option>";
                    endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="saveGizbarBtn">הוסף</button>
            </div>
        </div>
    </div>
</div>

<div id="peopleData"
     data-gizbar-to-amarchal="<?php echo htmlspecialchars(json_encode($gizbarToAmarchal, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
     data-gizbar-list="<?php echo htmlspecialchars(json_encode($gizbarim, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
     data-can-edit="<?php echo $canEdit ? '1' : '0'; ?>"></div>

<script src="../assets/js/people.js"></script>

<!-- Import Excel Modal -->
<div class="modal fade" id="importPeopleModal" tabindex="-1" aria-labelledby="importPeopleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importPeopleModalLabel">ייבוא אנשים מקובץ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="import_people">
                    <div class="mb-3">
                        <label for="people_excel_file" class="form-label">בחר קובץ Excel (.xlsx/.xls)</label>
                        <input type="file" class="form-control" id="people_excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <small class="text-muted">השורה הראשונה צריכה להכיל כותרות עמודות בעברית כמו בטבלה.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">בטל</button>
                    <button type="submit" class="btn btn-brand">ייבא</button>
                </div>
            </form>
        </div>
    </div>
    </div>

<!-- Print PDF Modal -->
<div class="modal fade" id="printPdfModal" tabindex="-1" aria-labelledby="printPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printPdfModalLabel">הדפסת דוח PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">בחר סוג דוח:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="reportType" id="reportTypeAmarchal" value="amarchal" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="reportTypeAmarchal">אמרכל</label>
                        
                        <input type="radio" class="btn-check" name="reportType" id="reportTypeGizbar" value="gizbar" autocomplete="off">
                        <label class="btn btn-outline-primary" for="reportTypeGizbar">גזבר</label>
                    </div>
                </div>

                <div class="mb-3" id="gizbarSortOptions" style="display:none;">
                    <label class="form-label fw-bold">מיון גזברים:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gizbarSort" id="gizbarSortByName" value="gizbar" autocomplete="off" checked>
                        <label class="btn btn-outline-secondary" for="gizbarSortByName">לפי א' ב' גזברים</label>

                        <input type="radio" class="btn-check" name="gizbarSort" id="gizbarSortByAmarchal" value="amarchal" autocomplete="off">
                        <label class="btn btn-outline-secondary" for="gizbarSortByAmarchal">לפי אמרכל</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">בחר סוג פלט:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="outputType" id="outputTypeReport" value="report" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="outputTypeReport">דוח PDF</label>

                        <input type="radio" class="btn-check" name="outputType" id="outputTypeLabels" value="labels" autocomplete="off">
                        <label class="btn btn-outline-primary" for="outputTypeLabels">מדבקות</label>
                    </div>
                </div>

                <div id="amarchalSelection" class="selection-container">
                    <label class="form-label fw-bold">בחר אמרכלים להדפסה:</label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllAmarchal">בחר הכל</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllAmarchal">בטל הכל</button>
                    </div>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($amarchalim as $name): ?>
                        <div class="form-check">
                            <input class="form-check-input amarchal-checkbox" type="checkbox" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" id="amarchal_<?php echo md5($name); ?>">
                            <label class="form-check-label" for="amarchal_<?php echo md5($name); ?>">
                                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="gizbarSelection" class="selection-container" style="display:none;">
                    <label class="form-label fw-bold">בחר גזברים להדפסה:</label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllGizbar">בחר הכל</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllGizbar">בטל הכל</button>
                    </div>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($gizbarim as $name): ?>
                        <div class="form-check">
                            <input class="form-check-input gizbar-checkbox" type="checkbox" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" id="gizbar_<?php echo md5($name); ?>">
                            <label class="form-check-label" for="gizbar_<?php echo md5($name); ?>">
                                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-bold">בחר חודשי איסוף (מתשרי עד אלול):</label>
                    <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                        <?php
                        $months = ['תשרי','חשון','כסלו','טבת','שבט','אדר','ניסן','אייר','סיון','תמוז','אב','אלול'];
                        foreach ($months as $m):
                            $id = 'month_' . md5($m);
                        ?>
                        <div class="form-check form-check-inline mb-2">
                            <input class="form-check-input month-checkbox" type="checkbox" value="<?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo $id; ?>">
                            <label class="form-check-label" for="<?php echo $id; ?>"><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">בטל</button>
                <button type="button" class="btn btn-brand" id="generatePdfBtn"><i class="bi bi-file-pdf"></i> צור PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel Modal - Amarchal -->
<div class="modal fade" id="importAmarchalModal" tabindex="-1" aria-labelledby="importAmarchalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importAmarchalModalLabel">ייבוא אמרכלים מקובץ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="import_amarchal_list">
                    <div class="mb-3">
                        <label for="amarchal_excel_file" class="form-label">בחר קובץ Excel (.xlsx/.xls)</label>
                        <input type="file" class="form-control" id="amarchal_excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <small class="text-muted">עמודות דרושות: אמרכל, תעודת זהות בעל, כתובת, שכונה, קומה, עיר, טלפון, פלאפון, מייל.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">בטל</button>
                    <button type="submit" class="btn btn-brand">ייבא</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Excel Modal - Gizbar -->
<div class="modal fade" id="importGizbarModal" tabindex="-1" aria-labelledby="importGizbarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importGizbarModalLabel">ייבוא גזברים מקובץ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="import_gizbar_list">
                    <div class="mb-3">
                        <label for="gizbar_excel_file" class="form-label">בחר קובץ Excel (.xlsx/.xls)</label>
                        <input type="file" class="form-control" id="gizbar_excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <small class="text-muted">עמודות דרושות: גזבר, תעודת זהות בעל, כתובת, שכונה, קומה, עיר, טלפון, פלאפון, מייל.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">בטל</button>
                    <button type="submit" class="btn btn-brand">ייבא</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>