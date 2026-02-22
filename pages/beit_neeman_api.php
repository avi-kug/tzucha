<?php
/**
 * API לניהול בית נאמן - ילדים והוריהם מעל גיל 16
 */

require_once '../config/db.php';
require_once '../vendor/autoload.php';
session_start();
require_once '../config/auth.php';

auth_require_login($pdo);
auth_require_permission('people');
$canEdit = auth_role() !== 'viewer';

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 60, 60);
    check_request_size();
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

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
            // רשימת כל הילדים בבית נאמן (מעל גיל 16)
            $sql = "SELECT * FROM beit_neeman ORDER BY family_name, child_name";
            $stmt = $pdo->query($sql);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'records' => $records]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM beit_neeman WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo json_encode(['success' => true, 'record' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'רשומה לא נמצאה']);
            }
            break;
            
        case 'save':
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה לעריכה']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            $familyName = trim($_POST['family_name'] ?? '');
            $childName = trim($_POST['child_name'] ?? '');
            $age = $_POST['age'] ?? null;
            $fatherName = trim($_POST['father_name'] ?? '');
            $fatherMobile = trim($_POST['father_mobile'] ?? '');
            $motherName = trim($_POST['mother_name'] ?? '');
            $maidenName = trim($_POST['maiden_name'] ?? '');
            $motherMobile = trim($_POST['mother_mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $childId = trim($_POST['child_id'] ?? '');
            $birthDay = $_POST['birth_day'] ?? null;
            $birthMonth = trim($_POST['birth_month'] ?? '');
            $birthYear = $_POST['birth_year'] ?? null;
            $studyPlace = trim($_POST['study_place'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $status = $_POST['status'] ?? 'רווק';
            $childRecordId = $_POST['child_record_id'] ?? null;
            
            // ולידציה
            if (empty($familyName)) {
                echo json_encode(['success' => false, 'message' => 'חובה להזין שם משפחה']);
                exit;
            }
            if (empty($childName)) {
                echo json_encode(['success' => false, 'message' => 'חובה להזין שם ילד']);
                exit;
            }
            
            if ($id > 0) {
                // עדכון
                $stmt = $pdo->prepare("UPDATE beit_neeman SET 
                    family_name = ?,
                    child_name = ?,
                    age = ?,
                    father_name = ?,
                    father_mobile = ?,
                    mother_name = ?,
                    maiden_name = ?,
                    mother_mobile = ?,
                    address = ?,
                    city = ?,
                    gender = ?,
                    child_id = ?,
                    birth_day = ?,
                    birth_month = ?,
                    birth_year = ?,
                    study_place = ?,
                    notes = ?,
                    status = ?,
                    child_record_id = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $familyName, $childName, $age ?: null, $fatherName, $fatherMobile,
                    $motherName, $maidenName, $motherMobile, $address, $city,
                    $gender, $childId, $birthDay ?: null, $birthMonth, $birthYear ?: null,
                    $studyPlace, $notes, $status, $childRecordId ?: null, $id
                ]);
                
                // עדכון גם ב-children אם קיים קישור
                if ($childRecordId) {
                    $updateChild = $pdo->prepare("UPDATE children SET status = ? WHERE id = ?");
                    $updateChild->execute([$status, $childRecordId]);
                }
                
                echo json_encode(['success' => true, 'message' => 'הרשומה עודכנה בהצלחה', 'id' => $id]);
            } else {
                // הוספה
                $stmt = $pdo->prepare("INSERT INTO beit_neeman (
                    family_name, child_name, age, father_name, father_mobile,
                    mother_name, maiden_name, mother_mobile, address, city,
                    gender, child_id, birth_day, birth_month, birth_year,
                    study_place, notes, status, child_record_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $familyName, $childName, $age ?: null, $fatherName, $fatherMobile,
                    $motherName, $maidenName, $motherMobile, $address, $city,
                    $gender, $childId, $birthDay ?: null, $birthMonth, $birthYear ?: null,
                    $studyPlace, $notes, $status, $childRecordId ?: null
                ]);
                
                $newId = $pdo->lastInsertId();
                
                // עדכון גם ב-children אם קיים קישור
                if ($childRecordId) {
                    $updateChild = $pdo->prepare("UPDATE children SET status = ? WHERE id = ?");
                    $updateChild->execute([$status, $childRecordId]);
                }
                
                echo json_encode(['success' => true, 'message' => 'הרשומה נוספה בהצלחה', 'id' => $newId]);
            }
            break;
            
        case 'delete':
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה למחיקה']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM beit_neeman WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'הרשומה נמחקה בהצלחה']);
            break;
            
        case 'sync_from_children':
            // סנכרון אוטומטי של ילדים מעל גיל 16 ממאגר הילדים
            if (!$canEdit) {
                echo json_encode(['success' => false, 'message' => 'אין הרשאה']);
                exit;
            }
            
            $minAge = 16;
            
            // שנה עברית נוכחית
            $currentGregorianYear = (int)date('Y');
            $currentHebrewYear = $currentGregorianYear + 3760;
            $currentMonth = (int)date('m');
            if ($currentMonth < 9) {
                $currentHebrewYear--;
            }
            
            // מציאת ילדים מעל גיל 16 שאינם נשואים
            $sql = "SELECT c.*, 
                    p.full_name as parent_name,
                    p.family_name,
                    p.first_name as father_name,
                    p.husband_mobile as father_mobile,
                    p.wife_name as mother_name,
                    p.wife_mobile as mother_mobile,
                    p.address,
                    p.city
                    FROM children c
                    LEFT JOIN people p ON c.parent_husband_id = p.husband_id
                    WHERE c.status != 'נשוי' 
                    AND c.birth_year IS NOT NULL
                    AND (? - c.birth_year) >= ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$currentHebrewYear, $minAge]);
            $eligibleChildren = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $added = 0;
            $updated = 0;
            
            foreach ($eligibleChildren as $child) {
                $age = $currentHebrewYear - (int)$child['birth_year'];
                
                // בדיקה אם כבר קיים בבית נאמן
                $checkExisting = $pdo->prepare("SELECT id FROM beit_neeman WHERE child_record_id = ?");
                $checkExisting->execute([$child['id']]);
                $existing = $checkExisting->fetch();
                
                if ($existing) {
                    // עדכון
                    $updateStmt = $pdo->prepare("UPDATE beit_neeman SET 
                        family_name = ?,
                        child_name = ?,
                        age = ?,
                        father_name = ?,
                        father_mobile = ?,
                        mother_name = ?,
                        mother_mobile = ?,
                        address = ?,
                        city = ?,
                        gender = ?,
                        child_id = ?,
                        birth_day = ?,
                        birth_month = ?,
                        birth_year = ?,
                        status = ?
                        WHERE id = ?");
                    
                    $updateStmt->execute([
                        $child['family_name'] ?? '',
                        $child['child_name'],
                        $age,
                        $child['father_name'] ?? '',
                        $child['father_mobile'] ?? '',
                        $child['mother_name'] ?? '',
                        $child['mother_mobile'] ?? '',
                        $child['address'] ?? '',
                        $child['city'] ?? '',
                        $child['gender'],
                        $child['child_id'] ?? '',
                        $child['birth_day'],
                        $child['birth_month'],
                        $child['birth_year'],
                        $child['status'],
                        $existing['id']
                    ]);
                    $updated++;
                } else {
                    // הוספה
                    $insertStmt = $pdo->prepare("INSERT INTO beit_neeman (
                        family_name, child_name, age, father_name, father_mobile,
                        mother_name, mother_mobile, address, city, gender,
                        child_id, birth_day, birth_month, birth_year, status, child_record_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $insertStmt->execute([
                        $child['family_name'] ?? '',
                        $child['child_name'],
                        $age,
                        $child['father_name'] ?? '',
                        $child['father_mobile'] ?? '',
                        $child['mother_name'] ?? '',
                        $child['mother_mobile'] ?? '',
                        $child['address'] ?? '',
                        $child['city'] ?? '',
                        $child['gender'],
                        $child['child_id'] ?? '',
                        $child['birth_day'],
                        $child['birth_month'],
                        $child['birth_year'],
                        $child['status'],
                        $child['id']
                    ]);
                    $added++;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => "סונכרנו {$added} רשומות חדשות ועדכנו {$updated} רשומות קיימות",
                'added' => $added,
                'updated' => $updated
            ]);
            break;
            
        case 'export':
            // ייצוא לאקסל
            $records = $pdo->query("SELECT * FROM beit_neeman ORDER BY family_name, child_name")->fetchAll(PDO::FETCH_ASSOC);
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // הגדרת RTL לגיליון
            $sheet->setRightToLeft(true);
            
            // Headers
            $headers = ['משפחה', 'שם הילד', 'גיל', 'שם האב', 'נייד אב', 'שם האם', 'לבית', 'נייד אם', 
                        'כתובת', 'עיר', 'מין', 'ת.ז.', 'יום', 'חודש', 'שנה', 'מקום לימודים', 'הערות', 'סטטוס'];
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
            foreach ($records as $rec) {
                $dayHebrew = numberToHebrewLetter($rec['birth_day'] ?? '');
                $yearHebrew = yearToHebrewYear($rec['birth_year'] ?? '');
                
                $sheet->setCellValueByColumnAndRow(1, $row, $rec['family_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(2, $row, $rec['child_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(3, $row, $rec['age'] ?? '');
                $sheet->setCellValueByColumnAndRow(4, $row, $rec['father_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(5, $row, $rec['father_mobile'] ?? '');
                $sheet->setCellValueByColumnAndRow(6, $row, $rec['mother_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(7, $row, $rec['maiden_name'] ?? '');
                $sheet->setCellValueByColumnAndRow(8, $row, $rec['mother_mobile'] ?? '');
                $sheet->setCellValueByColumnAndRow(9, $row, $rec['address'] ?? '');
                $sheet->setCellValueByColumnAndRow(10, $row, $rec['city'] ?? '');
                $sheet->setCellValueByColumnAndRow(11, $row, $rec['gender'] ?? '');
                $sheet->setCellValueByColumnAndRow(12, $row, $rec['child_id'] ?? '');
                $sheet->setCellValueByColumnAndRow(13, $row, $dayHebrew);
                $sheet->setCellValueByColumnAndRow(14, $row, $rec['birth_month'] ?? '');
                $sheet->setCellValueByColumnAndRow(15, $row, $yearHebrew);
                $sheet->setCellValueByColumnAndRow(16, $row, $rec['study_place'] ?? '');
                $sheet->setCellValueByColumnAndRow(17, $row, $rec['notes'] ?? '');
                $sheet->setCellValueByColumnAndRow(18, $row, $rec['status'] ?? '');
                
                // יישור לימין לכל התאים
                for ($c = 1; $c <= 18; $c++) {
                    $sheet->getCellByColumnAndRow($c, $row)
                        ->getStyle()
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
                $row++;
            }
            
            // התאמת רוחב עמודות אוטומטית
            foreach (range(1, 18) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            $filename = 'beit_neeman_' . date('Y-m-d_H-i-s') . '.xlsx';
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
            
            $map = [
                'משפחה' => 'family_name',
                'שם הילד' => 'child_name',
                'גיל' => 'age',
                'שם האב' => 'father_name',
                'נייד אב' => 'father_mobile',
                'שם האם' => 'mother_name',
                'לבית' => 'maiden_name',
                'נייד אם' => 'mother_mobile',
                'כתובת' => 'address',
                'עיר' => 'city',
                'מין' => 'gender',
                'ת.ז.' => 'child_id',
                'יום' => 'birth_day',
                'חודש' => 'birth_month',
                'שנה' => 'birth_year',
                'מקום לימודים' => 'study_place',
                'הערות' => 'notes',
                'סטטוס' => 'status'
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
                
                if (empty($data['family_name']) || empty($data['child_name'])) {
                    $errors[] = "שורה {$excelRow}: חסר שם משפחה או שם ילד";
                    $skipped++;
                    continue;
                }
                
                // בדיקה אם קיים (לפי שם משפחה + שם ילד)
                $checkExisting = $pdo->prepare("SELECT id FROM beit_neeman WHERE family_name = ? AND child_name = ? LIMIT 1");
                $checkExisting->execute([$data['family_name'], $data['child_name']]);
                $existing = $checkExisting->fetch();
                
                try {
                    if ($existing) {
                        // עדכון
                        $stmt = $pdo->prepare("UPDATE beit_neeman SET 
                            age = ?, father_name = ?, father_mobile = ?, mother_name = ?,
                            maiden_name = ?, mother_mobile = ?, address = ?, city = ?,
                            gender = ?, child_id = ?, birth_day = ?, birth_month = ?,
                            birth_year = ?, study_place = ?, notes = ?, status = ?
                            WHERE id = ?");
                        $stmt->execute([
                            $data['age'] ?: null,
                            $data['father_name'] ?? '',
                            $data['father_mobile'] ?? '',
                            $data['mother_name'] ?? '',
                            $data['maiden_name'] ?? '',
                            $data['mother_mobile'] ?? '',
                            $data['address'] ?? '',
                            $data['city'] ?? '',
                            $data['gender'] ?? '',
                            $data['child_id'] ?? '',
                            $data['birth_day'] ?: null,
                            $data['birth_month'] ?? '',
                            $data['birth_year'] ?: null,
                            $data['study_place'] ?? '',
                            $data['notes'] ?? '',
                            $data['status'] ?? 'רווק',
                            $existing['id']
                        ]);
                        $updated++;
                    } else {
                        // הוספה
                        $stmt = $pdo->prepare("INSERT INTO beit_neeman (
                            family_name, child_name, age, father_name, father_mobile,
                            mother_name, maiden_name, mother_mobile, address, city,
                            gender, child_id, birth_day, birth_month, birth_year,
                            study_place, notes, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $data['family_name'],
                            $data['child_name'],
                            $data['age'] ?: null,
                            $data['father_name'] ?? '',
                            $data['father_mobile'] ?? '',
                            $data['mother_name'] ?? '',
                            $data['maiden_name'] ?? '',
                            $data['mother_mobile'] ?? '',
                            $data['address'] ?? '',
                            $data['city'] ?? '',
                            $data['gender'] ?? '',
                            $data['child_id'] ?? '',
                            $data['birth_day'] ?: null,
                            $data['birth_month'] ?? '',
                            $data['birth_year'] ?: null,
                            $data['study_place'] ?? '',
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
            
        default:
            echo json_encode(['success' => false, 'message' => 'פעולה לא חוקית']);
    }
} catch (Exception $e) {
    error_log('Beit Neeman API Error: ' . $e->getMessage());
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
 * המרת אות עברית למספר (א'-ל')
 * @param mixed $input - יכול להיות אות עברית או כבר מספר
 * @return int|null - המספר או null אם לא תקין
 */
function hebrewLetterToNumber($input) {
    if ($input === null || $input === '') {
        return null;
    }
    
    // אם זה כבר מספר, נחזיר אותו
    if (is_numeric($input)) {
        $num = (int)$input;
        return ($num >= 1 && $num <= 30) ? $num : null;
    }
    
    // הסרת רווחים ותווים מיוחדים
    $input = trim($input);
    $input = str_replace(['"', "'", 'ׂ', 'ׁ'], '', $input);
    
    $letterMap = [
        'א' => 1, 'ב' => 2, 'ג' => 3, 'ד' => 4, 'ה' => 5,
        'ו' => 6, 'ז' => 7, 'ח' => 8, 'ט' => 9, 'י' => 10,
        'יא' => 11, 'יב' => 12, 'יג' => 13, 'יד' => 14, 'טו' => 15,
        'טז' => 16, 'יז' => 17, 'יח' => 18, 'יט' => 19, 'כ' => 20,
        'כא' => 21, 'כב' => 22, 'כג' => 23, 'כד' => 24, 'כה' => 25,
        'כו' => 26, 'כז' => 27, 'כח' => 28, 'כט' => 29, 'ל' => 30
    ];
    
    return $letterMap[$input] ?? null;
}

/**
 * המרת שנה עברית חזרה למספר
 * @param mixed $input - יכול להיות גימטריה, שנה בעברית, או כבר מספר
 * @return int|null - השנה המלאה או null אם לא תקין
 */
function hebrewYearToNumber($input) {
    if ($input === null || $input === '') {
        return null;
    }
    
    // אם זה כבר מספר מלא (5xxx), נחזיר אותו
    if (is_numeric($input)) {
        $num = (int)$input;
        if ($num >= 5000 && $num <= 6000) {
            return $num;
        }
        // אם זה מספר קטן (כמו 84 עבור תשפ"ד), נהפוך אותו
        if ($num >= 0 && $num <= 999) {
            return 5000 + $num;
        }
        return null;
    }
    
    // הסרת תווים מיוחדים
    $input = trim($input);
    $input = str_replace(['"', "'", 'ׂ', 'ׁ', 'ה'], '', $input);
    
    // מיפוי גימטריה בסיסי
    $letterValues = [
        'א' => 1, 'ב' => 2, 'ג' => 3, 'ד' => 4, 'ה' => 5,
        'ו' => 6, 'ז' => 7, 'ח' => 8, 'ט' => 9,
        'י' => 10, 'כ' => 20, 'ל' => 30, 'מ' => 40, 'נ' => 50,
        'ס' => 60, 'ע' => 70, 'פ' => 80, 'צ' => 90,
        'ק' => 100, 'ר' => 200, 'ש' => 300, 'ת' => 400
    ];
    
    $value = 0;
    $length = mb_strlen($input);
    
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($input, $i, 1);
        if (isset($letterValues[$char])) {
            $value += $letterValues[$char];
        }
    }
    
    // אם קיבלנו ערך, נוסיף 5000 (לדוגמה: תשפד = 784 → 5784)
    if ($value > 0) {
        return 5000 + $value;
    }
    
    return null;
}