<?php
/**
 * API לניהול מאגר ילדים
 */

require_once '../config/db.php';
require_once '../vendor/autoload.php';
session_start();
require_once '../config/auth.php';

// דרישת התחברות והרשאה
auth_require_login($pdo);
auth_require_permission('people');
$canEdit = auth_role() !== 'viewer';

header('Content-Type: application/json; charset=utf-8');

// בדיקת CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        echo json_encode(['success' => false, 'message' => 'פג תוקף הטופס']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // רשימת כל הילדים
            $status = $_GET['status'] ?? 'all';
            $parentId = $_GET['parent_id'] ?? '';
            
            $sql = "SELECT c.*, 
                    p.full_name as parent_name, 
                    p.family_name as parent_family_name,
                    p.first_name as parent_first_name,
                    p.wife_name as mother_name
                    FROM children c
                    LEFT JOIN people p ON c.parent_husband_id = p.husband_id
                    WHERE 1=1";
            
            $params = [];
            
            if ($status !== 'all' && $status !== '') {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
            
            // אם מבקשים ילדים שאינם נשואים (למאגר הילדים הרגיל)
            if ($status === 'not_married') {
                $sql = "SELECT c.*, 
                        p.full_name as parent_name, 
                        p.family_name as parent_family_name,
                        p.first_name as parent_first_name,
                        p.wife_name as mother_name
                        FROM children c
                        LEFT JOIN people p ON c.parent_husband_id = p.husband_id
                        WHERE c.status != 'נשוי'";
                $params = [];
            }
            
            if ($parentId !== '') {
                $sql .= " AND c.parent_husband_id = ?";
                $params[] = $parentId;
            }
            
            $sql .= " ORDER BY p.family_name, c.child_name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // חישוב גיל עברי לכל ילד
            foreach ($children as &$child) {
                $child['age'] = calculateHebrewAge($child);
            }
            
            echo json_encode(['success' => true, 'children' => $children]);
            break;
            
        case 'get':
            // קבלת פרטי ילד בודד
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM children WHERE id = ?");
            $stmt->execute([$id]);
            $child = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($child) {
                $child['age'] = calculateHebrewAge($child);
                echo json_encode(['success' => true, 'child' => $child]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ילד לא נמצא']);
            }
            break;
            
        case 'save':
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה לעריכה']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            $parentId = trim($_POST['parent_husband_id'] ?? '');
            $childName = trim($_POST['child_name'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birthDay = $_POST['birth_day'] ?? null;
            $birthMonth = trim($_POST['birth_month'] ?? '');
            $birthYear = $_POST['birth_year'] ?? null;
            $birthDateGregorian = $_POST['birth_date_gregorian'] ?? null;
            $childId = trim($_POST['child_id'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $status = $_POST['status'] ?? 'רווק';
            
            // ולידציה
            if (empty($parentId)) {
                echo json_encode(['success' => false, 'message' => 'חובה לבחור הורה']);
                exit;
            }
            if (empty($childName)) {
                echo json_encode(['success' => false, 'message' => 'חובה להזין שם ילד']);
                exit;
            }
            if (empty($gender)) {
                echo json_encode(['success' => false, 'message' => 'חובה לבחור מין']);
                exit;
            }
            
            // בדיקה שההורה קיים
            $checkParent = $pdo->prepare("SELECT id FROM people WHERE husband_id = ? LIMIT 1");
            $checkParent->execute([$parentId]);
            if (!$checkParent->fetch()) {
                echo json_encode(['success' => false, 'message' => 'הורה לא נמצא במערכת']);
                exit;
            }
            
            if ($id > 0) {
                // עדכון
                $stmt = $pdo->prepare("UPDATE children SET 
                    parent_husband_id = ?,
                    child_name = ?,
                    gender = ?,
                    birth_day = ?,
                    birth_month = ?,
                    birth_year = ?,
                    birth_date_gregorian = ?,
                    child_id = ?,
                    notes = ?,
                    status = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $parentId, $childName, $gender, 
                    $birthDay ?: null, $birthMonth ?: null, $birthYear ?: null,
                    $birthDateGregorian ?: null, $childId, $notes, $status, $id
                ]);
                
                // אם הסטטוס שונה לנשוי, צריך לעדכן גם ב-beit_neeman אם קיים
                if ($status === 'נשוי') {
                    $updateBN = $pdo->prepare("UPDATE beit_neeman SET status = 'נשוי' WHERE child_record_id = ?");
                    $updateBN->execute([$id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'הילד עודכן בהצלחה', 'id' => $id]);
            } else {
                // הוספה
                $stmt = $pdo->prepare("INSERT INTO children (
                    parent_husband_id, child_name, gender, birth_day, birth_month, birth_year,
                    birth_date_gregorian, child_id, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $parentId, $childName, $gender, 
                    $birthDay ?: null, $birthMonth ?: null, $birthYear ?: null,
                    $birthDateGregorian ?: null, $childId, $notes, $status
                ]);
                
                $newId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'הילד נוסף בהצלחה', 'id' => $newId]);
            }
            break;
            
        case 'delete':
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה למחיקה']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            
            // מחיקת הקשרים ב-beit_neeman קודם
            $stmt = $pdo->prepare("UPDATE beit_neeman SET child_record_id = NULL WHERE child_record_id = ?");
            $stmt->execute([$id]);
            
            // מחיקת הילד
            $stmt = $pdo->prepare("DELETE FROM children WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'הילד נמחק בהצלחה']);
            break;
            
        case 'export':
            // ייצוא לאקסל
            $status = $_GET['status'] ?? 'not_married';
            
            $sql = "SELECT c.*, 
                    p.full_name as parent_name,
                    p.family_name as parent_family_name,
                    p.first_name as parent_first_name
                    FROM children c
                    LEFT JOIN people p ON c.parent_husband_id = p.husband_id";
            
            if ($status === 'not_married') {
                $sql .= " WHERE c.status != 'נשוי'";
            } elseif ($status !== 'all') {
                $sql .= " WHERE c.status = " . $pdo->quote($status);
            }
            
            $sql .= " ORDER BY p.family_name, c.child_name";
            
            $children = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // הגדרת RTL לגיליון
            $sheet->setRightToLeft(true);
            
            // Headers
            $headers = ['שם משפחה', 'שם האב', 'שם הילד', 'מין', 'יום', 'חודש', 'שנה', 'ת. לידה לועזי', 'תעודת זהות', 'גיל', 'הערות', 'סטטוס'];
            $col = 1;
            foreach ($headers as $h) {
                $cell = $sheet->getCellByColumnAndRow($col, 1);
                $cell->setValue($h);
                // עיצוב כותרות - מודגש ורקע
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9');
                $cell->getStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $col++;
            }
            
            // Data
            $row = 2;
            foreach ($children as $child) {
                $age = calculateHebrewAge($child);
                $dayHebrew = numberToHebrewLetter($child['birth_day'] ?? '');
                $yearHebrew = yearToHebrewYear($child['birth_year'] ?? '');
                
                $sheet->setCellValueByColumnAndRow(1, $row, $child['parent_family_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(2, $row, $child['parent_first_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(3, $row, $child['child_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(4, $row, $child['gender'] ?? '');
                $sheet->setCellValueByColumnAndRow(5, $row, $dayHebrew);
                $sheet->setCellValueByColumnAndRow(6, $row, $child['birth_month'] ?? '');
                $sheet->setCellValueByColumnAndRow(7, $row, $yearHebrew);
                $sheet->setCellValueByColumnAndRow(8, $row, $child['birth_date_gregorian'] ?? '');
                $sheet->setCellValueByColumnAndRow(9, $row, $child['child_id'] ?? '');
                $sheet->setCellValueByColumnAndRow(10, $row, $age);
                $sheet->setCellValueByColumnAndRow(11, $row, $child['notes'] ?? '');
                $sheet->setCellValueByColumnAndRow(12, $row, $child['status'] ?? '');
                
                // יישור לימין לכל התאים
                for ($c = 1; $c <= 12; $c++) {
                    $sheet->getCellByColumnAndRow($c, $row)
                        ->getStyle()
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
                $row++;
            }
            
            // התאמת רוחב עמודות אוטומטית
            foreach (range(1, 12) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            $filename = 'children_' . date('Y-m-d_H-i-s') . '.xlsx';
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
            break;
            
        case 'import':
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה לייבוא']);
                exit;
            }
            
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'שגיאה בהעלאת הקובץ']);
                exit;
            }
            
            $tmp = $_FILES['excel_file']['tmp_name'];
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
            if (method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($tmp);
            $ws = $spreadsheet->getActiveSheet();
            $rows = $ws->toArray();
            
            if (!$rows || count($rows) < 2) {
                echo json_encode(['success' => false, 'message' => 'קובץ ריק']);
                exit;
            }
            
            $header = array_map('trim', $rows[0]);
            
            // מיפוי עמודות
            $map = [
                'שם משפחה' => 'family_name',
                'שם הילד' => 'child_name',
                'מין' => 'gender',
                'יום' => 'birth_day',
                'חודש' => 'birth_month',
                'שנה' => 'birth_year',
                'ת. לידה לועזי' => 'birth_date_gregorian',
                'תעודת זהות' => 'child_id',
                'הערות' => 'notes',
                'סטטוס' => 'status',
                'ת.ז. הורה' => 'parent_husband_id'
            ];
            
            $indexes = [];
            foreach ($header as $i => $h) {
                if (isset($map[$h])) {
                    $indexes[$i] = $map[$h];
                }
            }
            
            $added = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            for ($r = 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                $excelRow = $r + 1;
                
                if (!is_array($row) || count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }
                
                $data = [];
                foreach ($indexes as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim((string)$row[$i]) : '';
                }
                
                // המרת ימים עבריים למספרים אם צריך
                if (!empty($data['birth_day'])) {
                    $convertedDay = hebrewLetterToNumber($data['birth_day']);
                    if ($convertedDay !== null) {
                        $data['birth_day'] = $convertedDay;
                    }
                }
                
                // המרת שנים עבריות למספרים אם צריך
                if (!empty($data['birth_year'])) {
                    $convertedYear = hebrewYearToNumber($data['birth_year']);
                    if ($convertedYear !== null) {
                        $data['birth_year'] = $convertedYear;
                    }
                }
                
                // חייב להיות שם ילד ומין
                if (empty($data['child_name']) || empty($data['gender'])) {
                    $errors[] = "שורה {$excelRow}: חסר שם ילד או מין";
                    $skipped++;
                    continue;
                }
                
                // אם אין ת.ז. הורה, מנסים למצוא לפי שם משפחה
                if (empty($data['parent_husband_id']) && !empty($data['family_name'])) {
                    $findParent = $pdo->prepare("SELECT husband_id FROM people WHERE family_name = ? AND husband_id IS NOT NULL AND husband_id != '' LIMIT 1");
                    $findParent->execute([$data['family_name']]);
                    $parent = $findParent->fetch();
                    if ($parent) {
                        $data['parent_husband_id'] = $parent['husband_id'];
                    }
                }
                
                if (empty($data['parent_husband_id'])) {
                    $errors[] = "שורה {$excelRow}: לא נמצא הורה עבור {$data['child_name']}";
                    $skipped++;
                    continue;
                }
                
                // בדיקה אם הילד כבר קיים (לפי שם + הורה)
                $checkExisting = $pdo->prepare("SELECT id FROM children WHERE child_name = ? AND parent_husband_id = ? LIMIT 1");
                $checkExisting->execute([$data['child_name'], $data['parent_husband_id']]);
                $existing = $checkExisting->fetch();
                
                try {
                    if ($existing) {
                        // עדכון
                        $stmt = $pdo->prepare("UPDATE children SET 
                            gender = ?, birth_day = ?, birth_month = ?, birth_year = ?,
                            birth_date_gregorian = ?, child_id = ?, notes = ?, status = ?
                            WHERE id = ?");
                        $stmt->execute([
                            $data['gender'],
                            $data['birth_day'] ?: null,
                            $data['birth_month'] ?: null,
                            $data['birth_year'] ?: null,
                            $data['birth_date_gregorian'] ?: null,
                            $data['child_id'] ?? '',
                            $data['notes'] ?? '',
                            $data['status'] ?? 'רווק',
                            $existing['id']
                        ]);
                        $updated++;
                    } else {
                        // הוספה
                        $stmt = $pdo->prepare("INSERT INTO children (
                            parent_husband_id, child_name, gender, birth_day, birth_month, birth_year,
                            birth_date_gregorian, child_id, notes, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $data['parent_husband_id'],
                            $data['child_name'],
                            $data['gender'],
                            $data['birth_day'] ?: null,
                            $data['birth_month'] ?: null,
                            $data['birth_year'] ?: null,
                            $data['birth_date_gregorian'] ?: null,
                            $data['child_id'] ?? '',
                            $data['notes'] ?? '',
                            $data['status'] ?? 'רווק'
                        ]);
                        $added++;
                    }
                } catch (PDOException $e) {
                    $errors[] = "שורה {$excelRow}: {$e->getMessage()}";
                    $skipped++;
                }
            }
            
            $msg = "ייבוא הושלם: נוספו {$added}, עודכנו {$updated}, דולגו {$skipped}";
            if (!empty($errors)) {
                $msg .= "\nשגיאות:\n" . implode("\n", array_slice($errors, 0, 10));
            }
            
            echo json_encode(['success' => true, 'message' => $msg, 'added' => $added, 'updated' => $updated, 'skipped' => $skipped]);
            break;
            
        case 'get_by_parent':
            // קבלת ילדים של הורה ספציפי
            $parentId = $_GET['parent_id'] ?? '';
            if (empty($parentId)) {
                echo json_encode(['success' => false, 'message' => 'חסר מזהה הורה']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM children WHERE parent_husband_id = ? ORDER BY child_name");
            $stmt->execute([$parentId]);
            $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // חישוב גילאים וסיכום
            $boys = 0;
            $girls = 0;
            $married = 0;
            
            foreach ($children as &$child) {
                $child['age'] = calculateHebrewAge($child);
                if ($child['gender'] === 'זכר') $boys++;
                if ($child['gender'] === 'נקבה') $girls++;
                if ($child['status'] === 'נשוי') $married++;
            }
            
            echo json_encode([
                'success' => true, 
                'children' => $children,
                'summary' => [
                    'boys' => $boys,
                    'girls' => $girls,
                    'married' => $married,
                    'total' => count($children)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'פעולה לא חוקית']);
    }
} catch (Exception $e) {
    error_log('Children API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'שגיאת שרת: ' . $e->getMessage()]);
}

/**
 * המרת מספר לאות עברית (1-30 -> א'-ל')
 */
function numberToHebrewLetter($num) {
    if (empty($num)) return '';
    
    $hebrewLetters = [
        1 => 'א', 2 => 'ב', 3 => 'ג', 4 => 'ד', 5 => 'ה',
        6 => 'ו', 7 => 'ז', 8 => 'ח', 9 => 'ט', 10 => 'י',
        11 => 'יא', 12 => 'יב', 13 => 'יג', 14 => 'יד', 15 => 'טו',
        16 => 'טז', 17 => 'יז', 18 => 'יח', 19 => 'יט', 20 => 'כ',
        21 => 'כא', 22 => 'כב', 23 => 'כג', 24 => 'כד', 25 => 'כה',
        26 => 'כו', 27 => 'כז', 28 => 'כח', 29 => 'כט', 30 => 'ל'
    ];
    
    return $hebrewLetters[(int)$num] ?? (string)$num;
}

/**
 * המרת אות עברית למספר (א'-ל' -> 1-30)
 */
function hebrewLetterToNumber($letter) {
    if (empty($letter)) return null;
    
    // אם זה כבר מספר, נחזיר אותו
    if (is_numeric($letter)) return (int)$letter;
    
    $letterToNumber = [
        'א' => 1, 'ב' => 2, 'ג' => 3, 'ד' => 4, 'ה' => 5,
        'ו' => 6, 'ז' => 7, 'ח' => 8, 'ט' => 9, 'י' => 10,
        'יא' => 11, 'יב' => 12, 'יג' => 13, 'יד' => 14, 'טו' => 15,
        'טז' => 16, 'יז' => 17, 'יח' => 18, 'יט' => 19, 'כ' => 20,
        'כא' => 21, 'כב' => 22, 'כג' => 23, 'כד' => 24, 'כה' => 25,
        'כו' => 26, 'כז' => 27, 'כח' => 28, 'כט' => 29, 'ל' => 30
    ];
    
    return $letterToNumber[trim($letter)] ?? null;
}

/**
 * המרת שנה לגימטריה עברית (5784 -> תשפ"ד)
 */
function yearToHebrewYear($year) {
    if (empty($year) || $year < 5000) return '';
    
    // הסרת אלפים (5784 -> 784)
    $shortYear = $year - 5000;
    
    $hundreds = floor($shortYear / 100);
    $remainder = $shortYear % 100;
    $tens = floor($remainder / 10);
    $ones = $remainder % 10;
    
    $hundredsMap = [
        1 => 'ק', 2 => 'ר', 3 => 'ש', 4 => 'ת', 5 => 'תק',
        6 => 'תר', 7 => 'תש', 8 => 'תת', 9 => 'תתק'
    ];
    
    $tensMap = [
        1 => 'י', 2 => 'כ', 3 => 'ל', 4 => 'מ', 5 => 'ן',
        6 => 'ס', 7 => 'ע', 8 => 'פ', 9 => 'צ'
    ];
    
    $onesMap = [
        1 => 'א', 2 => 'ב', 3 => 'ג', 4 => 'ד', 5 => 'ה',
        6 => 'ו', 7 => 'ז', 8 => 'ח', 9 => 'ט'
    ];
    
    $result = '';
    
    if ($hundreds > 0) {
        $result .= $hundredsMap[$hundreds] ?? '';
    }
    
    // טיפול מיוחד: טו = ט"ו (לא יה), טז = ט"ז (לא יו)
    if ($tens === 1 && $ones === 5) {
        $result .= 'ט"ו';
    } elseif ($tens === 1 && $ones === 6) {
        $result .= 'ט"ז';
    } else {
        if ($tens > 0) {
            $result .= $tensMap[$tens] ?? '';
        }
        if ($ones > 0) {
            if ($tens > 0) {
                $result .= '"' . ($onesMap[$ones] ?? '');
            } else {
                $result .= $onesMap[$ones] ?? '';
            }
        } elseif ($tens > 0 && strpos($result, '"') === false) {
            // אם יש רק עשרות בלי אחדות, נוסיף גרש
            $result = mb_substr($result, 0, -1) . "'" . mb_substr($result, -1);
        }
    }
    
    // אם אין גרש או גרשיים, נוסיף גרש לפני האות האחרונה
    if (strpos($result, '"') === false && strpos($result, "'") === false && mb_strlen($result) > 1) {
        $result = mb_substr($result, 0, -1) . '"' . mb_substr($result, -1);
    }
    
    return $result;
}

/**
 * המרת גימטריה עברית לשנה (תשפ"ד -> 5784)
 */
function hebrewYearToNumber($hebrewYear) {
    if (empty($hebrewYear)) return null;
    
    // אם זה כבר מספר, נחזיר אותו
    if (is_numeric($hebrewYear)) {
        $num = (int)$hebrewYear;
        // אם זה כבר שנה מלאה (מעל 5000), נחזיר כמו שזה
        if ($num >= 5000) return $num;
        // אם זה שנה קצרה (מתחת 1000), נוסיף 5000
        if ($num < 1000) return $num + 5000;
        return $num;
    }
    
    // הסרת גרשיים וגרש
    $cleaned = str_replace(['"', "'", '״', '׳'], '', trim($hebrewYear));
    
    $letterValues = [
        'א' => 1, 'ב' => 2, 'ג' => 3, 'ד' => 4, 'ה' => 5,
        'ו' => 6, 'ז' => 7, 'ח' => 8, 'ט' => 9,
        'י' => 10, 'כ' => 20, 'ך' => 20, 'ל' => 30, 'מ' => 40, 'ם' => 40,
        'נ' => 50, 'ן' => 50, 'ס' => 60, 'ע' => 70, 'פ' => 80, 'ף' => 80,
        'צ' => 90, 'ץ' => 90, 'ק' => 100, 'ר' => 200, 'ש' => 300, 'ת' => 400
    ];
    
    $total = 0;
    $len = mb_strlen($cleaned);
    for ($i = 0; $i < $len; $i++) {
        $letter = mb_substr($cleaned, $i, 1);
        $total += $letterValues[$letter] ?? 0;
    }
    
    // הוספת 5000 (ה' אלפים)
    if ($total < 1000) {
        $total += 5000;
    }
    
    return $total;
}

/**
 * פונקציה לחישוב גיל עברי
 */
function calculateHebrewAge($child) {
    if (empty($child['birth_year'])) {
        return '';
    }
    
    // שנה עברית נוכחית (קירוב)
    // ניתן לשפר עם ספריית המרה מדויקת
    $currentGregorianYear = (int)date('Y');
    $currentHebrewYear = $currentGregorianYear + 3760;
    
    // אם אנחנו לפני ראש השנה, צריך להחסיר שנה
    $currentMonth = (int)date('m');
    if ($currentMonth < 9) { // לפני ספטמבר בדרך כלל לפני ראש השנה
        $currentHebrewYear--;
    }
    
    $birthYear = (int)$child['birth_year'];
    $age = $currentHebrewYear - $birthYear;
    
    return max(0, $age);
}
