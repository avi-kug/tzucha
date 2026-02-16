<?php
// Don't set JSON header yet - export_excel needs different headers
require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../repositories/SupportsRepository.php';
require_once '../repositories/PeopleRepository.php';

auth_require_login($pdo);
auth_require_permission('supports');

// Initialize repositories
$supportsRepo = new SupportsRepository($pdo);
$peopleRepo = new PeopleRepository($pdo);

// Get the action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle export_excel BEFORE try-catch to avoid JSON header conflicts
if ($action === 'export_excel') {
    require_once '../vendor/autoload.php';
    
    $tab = $_GET['tab'] ?? 'data';
    
    // Force proper headers BEFORE any output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    
    // Create new Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $filename = 'export_' . date('Y-m-d') . '.xlsx'; // Default filename
    
    if ($tab === 'approved') {
        // Export approved supports table
        $sheet->setTitle('תמיכות שאושרו');
        
        // Set headers
        $headers = ['מס\' תורם', 'שם', 'משפחה', 'סכום', 'חודש תמיכה', 'תאריך אישור'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Get data
        $stmt = $pdo->prepare("
            SELECT 
                donor_number,
                first_name,
                last_name,
                amount,
                support_month,
                approved_at
            FROM approved_supports
            ORDER BY support_month DESC, created_at DESC
        ");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        
        // Add data to sheet
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'תמיכות_שאושרו_' . date('Y-m-d') . '.xlsx';
        
    } elseif ($tab === 'summary') {
        // Export summary table with calculations
        $sheet->setTitle('תמיכה');
        
        $headers = ['שם', 'משפחה', 'מס\' תורם', 'כתובת', 'עיר', 'סה"כ הכנסות', 'סה"כ הוצאות', 
                    'כולל חריגה?', 'הכנסה לנפש', 'סכום תמיכה', 'חודש תמיכה'];
        $sheet->fromArray($headers, null, 'A1');
        
        $supports = $supportsRepo->getAllWithCalculations(true);
        $data = [];
        foreach ($supports as $support) {
            $data[] = [
                $support['first_name'] ?? '',
                $support['last_name'] ?? '',
                $support['donor_number'] ?? '',
                $support['street'] ?? '',
                $support['city'] ?? '',
                $support['total_income'] ?? 0,
                $support['total_expenses'] ?? 0,
                $support['include_exceptional_in_calc'] == 1 ? 'כן' : 'לא',
                $support['income_per_person'] ?? 0,
                $support['support_amount'] ?? 0,
                $support['support_month'] ?? ''
            ];
        }
        
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'תמיכות_סיכום_' . date('Y-m-d') . '.xlsx';
        
    } else {
        // Export raw data table
        $sheet->setTitle('נתונים');
        
        $headers = ['מזהה', 'תאריך יצירה', 'תאריך עדכון', 'שם עמדה', 'שם פרטי', 'שם משפחה',
                   'מספר זהות', 'עיר', 'רחוב', 'מס\' טל\'', 'מס\' נפשות בבית', 'מס\' ילדים נשואים',
                   'מקום לימודים/עבודה 1', 'סכום הכנסה/מלגה 1', 'מקום לימודים/עבודה 2', 'סכום הכנסה/מלגה 2',
                   'קצבת ילדים', 'קצבת שארים', 'קצבת נכות', 'הבטחת הכנסה', 'השלמת הכנסה', 'סיוע בשכר דירה',
                   'מקור הקצבה אחר', 'סכום', 'הוצאות דיור', 'הוצאות שכר לימוד', 'הוצאה חריגה קבועה',
                   'פירוט הוצאה חריגה', 'סיבת הקושי', 'הערות', 'שם בעל החשבון', 'בנק', 'סניף',
                   'מס\' חשבון', 'שם מבקש התמיכה', 'מספר עסקה'];
        $sheet->fromArray($headers, null, 'A1');
        
        $supports = $supportsRepo->getAll();
        $data = [];
        foreach ($supports as $support) {
            $data[] = [
                $support['id'] ?? '',
                $support['created_at'] ?? '',
                $support['updated_at'] ?? '',
                $support['position_name'] ?? '',
                $support['first_name'] ?? '',
                $support['last_name'] ?? '',
                $support['id_number'] ?? '',
                $support['city'] ?? '',
                $support['street'] ?? '',
                $support['phone'] ?? '',
                $support['household_members'] ?? 0,
                $support['married_children'] ?? 0,
                $support['study_work_place_1'] ?? '',
                $support['income_scholarship_1'] ?? 0,
                $support['study_work_place_2'] ?? '',
                $support['income_scholarship_2'] ?? 0,
                $support['child_allowance'] ?? 0,
                $support['survivor_allowance'] ?? 0,
                $support['disability_allowance'] ?? 0,
                $support['income_guarantee'] ?? 0,
                $support['income_supplement'] ?? 0,
                $support['rent_assistance'] ?? 0,
                $support['other_allowance_source'] ?? '',
                $support['other_allowance_amount'] ?? 0,
                $support['housing_expenses'] ?? 0,
                $support['tuition_expenses'] ?? 0,
                $support['recurring_exceptional_expense'] ?? 0,
                $support['exceptional_expense_details'] ?? '',
                $support['difficulty_reason'] ?? '',
                $support['notes'] ?? '',
                $support['account_holder_name'] ?? '',
                $support['bank_name'] ?? '',
                $support['branch_number'] ?? '',
                $support['account_number'] ?? '',
                $support['support_requester_name'] ?? '',
                $support['transaction_number'] ?? ''
            ];
        }
        
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'תמיכות_נתונים_' . date('Y-m-d') . '.xlsx';
    }
    
    // Set RTL direction
    $sheet->setRightToLeft(true);
    
    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output headers and file
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Set JSON header for non-export actions
header('Content-Type: application/json; charset=utf-8');

$mutatingActions = ['add', 'update', 'delete', 'delete_bulk', 'import_excel', 'link_person', 'approve_support', 'delete_approved_support'];
if (in_array($action, $mutatingActions, true) && auth_role() === 'viewer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'אין הרשאה לפעולה זו.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate()) {
    echo json_encode(['success' => false, 'error' => 'פג תוקף הטופס, נסה שוב.']);
    exit;
}

try {
    switch ($action) {
        case 'get_all':
            // Get all supports with calculations
            $includeExceptionalExpense = isset($_GET['include_exceptional']) ? 
                (bool)$_GET['include_exceptional'] : true;
            
            $supports = $supportsRepo->getAllWithCalculations($includeExceptionalExpense);
            echo json_encode(['success' => true, 'data' => $supports]);
            break;
        
        case 'get_raw_data':
            // Get all supports without calculations (for data tab)
            $supports = $supportsRepo->getAll();
            echo json_encode(['success' => true, 'data' => $supports]);
            break;
        
        case 'get_one':
            // Get one support record
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $support = $supportsRepo->getById($id);
            if (!$support) {
                throw new Exception('Support record not found');
            }
            
            echo json_encode(['success' => true, 'data' => $support]);
            break;
        
        case 'add':
            // Add a new support record
            $data = [
                'person_id' => $_POST['person_id'] ?? null,
                'position_name' => $_POST['position_name'] ?? '',
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'id_number' => $_POST['id_number'] ?? '',
                'city' => $_POST['city'] ?? '',
                'street' => $_POST['street'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'household_members' => $_POST['household_members'] ?? 0,
                'married_children' => $_POST['married_children'] ?? 0,
                'study_work_place_1' => $_POST['study_work_place_1'] ?? '',
                'income_scholarship_1' => $_POST['income_scholarship_1'] ?? 0,
                'study_work_place_2' => $_POST['study_work_place_2'] ?? '',
                'income_scholarship_2' => $_POST['income_scholarship_2'] ?? 0,
                'child_allowance' => $_POST['child_allowance'] ?? 0,
                'survivor_allowance' => $_POST['survivor_allowance'] ?? 0,
                'disability_allowance' => $_POST['disability_allowance'] ?? 0,
                'income_guarantee' => $_POST['income_guarantee'] ?? 0,
                'income_supplement' => $_POST['income_supplement'] ?? 0,
                'rent_assistance' => $_POST['rent_assistance'] ?? 0,
                'other_allowance_source' => $_POST['other_allowance_source'] ?? '',
                'other_allowance_amount' => $_POST['other_allowance_amount'] ?? 0,
                'housing_expenses' => $_POST['housing_expenses'] ?? 0,
                'tuition_expenses' => $_POST['tuition_expenses'] ?? 0,
                'recurring_exceptional_expense' => $_POST['recurring_exceptional_expense'] ?? 0,
                'exceptional_expense_details' => $_POST['exceptional_expense_details'] ?? '',
                'difficulty_reason' => $_POST['difficulty_reason'] ?? '',
                'notes' => $_POST['notes'] ?? '',
                'account_holder_name' => $_POST['account_holder_name'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'branch_number' => $_POST['branch_number'] ?? '',
                'account_number' => $_POST['account_number'] ?? '',
                'support_requester_name' => $_POST['support_requester_name'] ?? '',
                'transaction_number' => $_POST['transaction_number'] ?? '',
                'include_exceptional_in_calc' => isset($_POST['include_exceptional_in_calc']) ? 1 : 0,
                'support_amount' => $_POST['support_amount'] ?? 0,
                'support_month' => $_POST['support_month'] ?? ''
            ];
            
            $newId = $supportsRepo->create($data);
            echo json_encode(['success' => true, 'message' => 'נוסף בהצלחה', 'id' => $newId]);
            break;
        
        case 'update':
            // Update a support record
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $data = [
                'person_id' => $_POST['person_id'] ?? null,
                'position_name' => $_POST['position_name'] ?? '',
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'id_number' => $_POST['id_number'] ?? '',
                'city' => $_POST['city'] ?? '',
                'street' => $_POST['street'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'household_members' => $_POST['household_members'] ?? 0,
                'married_children' => $_POST['married_children'] ?? 0,
                'study_work_place_1' => $_POST['study_work_place_1'] ?? '',
                'income_scholarship_1' => $_POST['income_scholarship_1'] ?? 0,
                'study_work_place_2' => $_POST['study_work_place_2'] ?? '',
                'income_scholarship_2' => $_POST['income_scholarship_2'] ?? 0,
                'child_allowance' => $_POST['child_allowance'] ?? 0,
                'survivor_allowance' => $_POST['survivor_allowance'] ?? 0,
                'disability_allowance' => $_POST['disability_allowance'] ?? 0,
                'income_guarantee' => $_POST['income_guarantee'] ?? 0,
                'income_supplement' => $_POST['income_supplement'] ?? 0,
                'rent_assistance' => $_POST['rent_assistance'] ?? 0,
                'other_allowance_source' => $_POST['other_allowance_source'] ?? '',
                'other_allowance_amount' => $_POST['other_allowance_amount'] ?? 0,
                'housing_expenses' => $_POST['housing_expenses'] ?? 0,
                'tuition_expenses' => $_POST['tuition_expenses'] ?? 0,
                'recurring_exceptional_expense' => $_POST['recurring_exceptional_expense'] ?? 0,
                'exceptional_expense_details' => $_POST['exceptional_expense_details'] ?? '',
                'difficulty_reason' => $_POST['difficulty_reason'] ?? '',
                'notes' => $_POST['notes'] ?? '',
                'account_holder_name' => $_POST['account_holder_name'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'branch_number' => $_POST['branch_number'] ?? '',
                'account_number' => $_POST['account_number'] ?? '',
                'support_requester_name' => $_POST['support_requester_name'] ?? '',
                'transaction_number' => $_POST['transaction_number'] ?? '',
                'include_exceptional_in_calc' => isset($_POST['include_exceptional_in_calc']) ? 1 : 0,
                'support_amount' => $_POST['support_amount'] ?? 0,
                'support_month' => $_POST['support_month'] ?? ''
            ];
            
            $supportsRepo->update($id, $data);
            echo json_encode(['success' => true, 'message' => 'עודכן בהצלחה']);
            break;
        
        case 'delete':
            // Delete a support record
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $supportsRepo->delete($id);
            echo json_encode(['success' => true, 'message' => 'נמחק בהצלחה']);
            break;
        
        case 'delete_bulk':
            // Delete multiple support records
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                throw new Exception('No IDs provided');
            }
            
            foreach ($ids as $id) {
                $supportsRepo->delete($id);
            }
            
            echo json_encode(['success' => true, 'message' => 'נמחקו בהצלחה']);
            break;
        
        case 'get_people_list':
            // Get list of all people for dropdown
            $people = $peopleRepo->getAll();
            echo json_encode(['success' => true, 'data' => $people]);
            break;
        
        case 'link_person':
            // Link a support record to a person
            $supportId = $_POST['support_id'] ?? 0;
            $personId = $_POST['person_id'] ?? 0;
            
            if (!$supportId || !$personId) {
                throw new Exception('Missing required parameters');
            }
            
            $supportsRepo->linkPersonByIdNumber($supportId, $personId);
            echo json_encode(['success' => true, 'message' => 'שויך בהצלחה']);
            break;
        
        case 'import_excel':
            // Import from Excel file
            require_once '../vendor/autoload.php';
            
            // === Validation ===
            if (!isset($_FILES['excel_file'])) {
                throw new Exception('לא הועלה קובץ');
            }
            
            $file = $_FILES['excel_file'];
            
            // Check file size (max 10MB)
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new Exception('הקובץ גדול מדי (מקסימום 10MB)');
            }
            
            // Check file extension
            $allowedExtensions = ['xlsx', 'xls'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('סוג קובץ לא נתמך. אנא העלה קובץ Excel (.xlsx או .xls)');
            }
            
            // Check MIME type
            $allowedMimeTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                throw new Exception('סוג הקובץ אינו תקין');
            }
            
            // Generate unique filename to prevent race conditions
            $uniqueFilename = uniqid('import_', true) . '.' . $fileExtension;
            $uploadPath = '../uploads/temp/' . $uniqueFilename;
            
            // Create temp directory if it doesn't exist
            if (!is_dir('../uploads/temp')) {
                mkdir('../uploads/temp', 0777, true);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('שגיאה בהעלאת הקובץ');
            }
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            $needsLinking = [];
            $processedIdNumbers = []; // Track ID numbers in this import to prevent duplicates within same file
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                
                // Get header row and create column mapping
                $headers = [];
                $headerRow = 1;
                foreach ($worksheet->getRowIterator($headerRow, $headerRow) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $colIndex = 0;
                    foreach ($cellIterator as $cell) {
                        $headerValue = trim($cell->getValue());
                        if (!empty($headerValue)) {
                            $headers[$headerValue] = $colIndex;
                        }
                        $colIndex++;
                    }
                }
                
                // Define column mapping (Hebrew header => DB field)
                $columnMap = [
                    'מזהה' => 'id',
                    'שם עמדה' => 'position_name',
                    'שם פרטי' => 'first_name',
                    'שם משפחה' => 'last_name',
                    'מספר זהות' => 'id_number',
                    'עיר' => 'city',
                    'רחוב' => 'street',
                    'מס\' טל\'' => 'phone',
                    'מס\' נפשות בבית (כולל ההורים)' => 'household_members',
                    'מס\' נפשות בבית' => 'household_members',
                    'מס\' ילדים נשואים' => 'married_children',
                    'מקום לימודים / עבודה' => 'study_work_place_1',
                    'מקום לימודים/עבודה 1' => 'study_work_place_1',
                    'סכום הכנסה / מלגה' => 'income_scholarship_1',
                    'סכום הכנסה/מלגה 1' => 'income_scholarship_1',
                    'מקום לימודים/עבודה 2' => 'study_work_place_2',
                    'סכום הכנסה/מלגה 2' => 'income_scholarship_2',
                    'קצבת ילדים' => 'child_allowance',
                    'קצבת שארים' => 'survivor_allowance',
                    'קצבת נכות' => 'disability_allowance',
                    'הבטחת הכנסה' => 'income_guarantee',
                    'השלמת הכנסה' => 'income_supplement',
                    'סיוע בשכר דירה' => 'rent_assistance',
                    'מקור הקצבה אחר' => 'other_allowance_source',
                    'סכום' => 'other_allowance_amount',
                    'הוצאות דיור' => 'housing_expenses',
                    'הוצאות שכר לימוד (סכום כולל למוסדות)' => 'tuition_expenses',
                    'הוצאות שכר לימוד' => 'tuition_expenses',
                    'הוצאה חריגה קבועה' => 'recurring_exceptional_expense',
                    'כולל חריגה בחישוב?' => 'include_exceptional_in_calc',
                    'כולל חריגה?' => 'include_exceptional_in_calc',
                    'פירוט - הוצאה חריגה' => 'exceptional_expense_details',
                    'פירוט הוצאה חריגה' => 'exceptional_expense_details',
                    'פרט מה סיבת הקושי' => 'difficulty_reason',
                    'סיבת הקושי' => 'difficulty_reason',
                    'הערות' => 'notes',
                    'שם בעל החשבון' => 'account_holder_name',
                    'בנק' => 'bank_name',
                    'סניף' => 'branch_number',
                    'מס\' חשבון' => 'account_number',
                    'שם מבקש התמיכה' => 'support_requester_name',
                    'מספר עסקה' => 'transaction_number'
                ];
                
                // Process rows
                for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
                    try {
                        $rowData = $worksheet->rangeToArray("A$rowNum:" . $worksheet->getHighestColumn() . "$rowNum", null, true, false)[0];
                        
                        // Skip completely empty rows
                        if (empty(array_filter($rowData))) {
                            continue;
                        }
                        
                        // Build data array from column mapping
                        $data = [
                            'person_id' => null,
                            'position_name' => '',
                            'first_name' => '',
                            'last_name' => '',
                            'id_number' => '',
                            'city' => '',
                            'street' => '',
                            'phone' => '',
                            'household_members' => 0,
                            'married_children' => 0,
                            'study_work_place_1' => '',
                            'income_scholarship_1' => 0,
                            'study_work_place_2' => '',
                            'income_scholarship_2' => 0,
                            'child_allowance' => 0,
                            'survivor_allowance' => 0,
                            'disability_allowance' => 0,
                            'income_guarantee' => 0,
                            'income_supplement' => 0,
                            'rent_assistance' => 0,
                            'other_allowance_source' => '',
                            'other_allowance_amount' => 0,
                            'housing_expenses' => 0,
                            'tuition_expenses' => 0,
                            'recurring_exceptional_expense' => 0,
                            'include_exceptional_in_calc' => 1,
                            'exceptional_expense_details' => '',
                            'difficulty_reason' => '',
                            'notes' => '',
                            'account_holder_name' => '',
                            'bank_name' => '',
                            'branch_number' => '',
                            'account_number' => '',
                            'support_requester_name' => '',
                            'transaction_number' => ''
                        ];
                        
                        // Map data from Excel to array
                        foreach ($columnMap as $excelHeader => $dbField) {
                            if (isset($headers[$excelHeader])) {
                                $colIndex = $headers[$excelHeader];
                                $value = $rowData[$colIndex] ?? '';
                                
                                // Type conversion and validation
                                if ($dbField === 'include_exceptional_in_calc') {
                                    $valueLower = mb_strtolower(trim($value));
                                    $data[$dbField] = in_array($valueLower, ['לא', 'no', '0', 'false']) ? 0 : 1;
                                } elseif (in_array($dbField, [
                                    'household_members', 'married_children', 'income_scholarship_1', 'income_scholarship_2',
                                    'child_allowance', 'survivor_allowance', 'disability_allowance', 'income_guarantee',
                                    'income_supplement', 'rent_assistance', 'other_allowance_amount', 'housing_expenses',
                                    'tuition_expenses', 'recurring_exceptional_expense'
                                ])) {
                                    // Numeric fields
                                    $cleanValue = preg_replace('/[^\d.-]/', '', trim($value));
                                    $numValue = is_numeric($cleanValue) ? floatval($cleanValue) : 0;
                                    if ($numValue < 0) $numValue = 0; // No negative values
                                    $data[$dbField] = $numValue;
                                } else {
                                    // Text fields - sanitize
                                    $data[$dbField] = trim(strip_tags($value));
                                }
                            }
                        }
                        
                        // Validate required field: ID number
                        if (empty($data['id_number'])) {
                            $errors[] = "שורה $rowNum: חסר מספר זהות - דילוג";
                            $skipped++;
                            continue;
                        }
                        
                        // Check if this ID number was already processed in this import
                        if (isset($processedIdNumbers[$data['id_number']])) {
                            $errors[] = "שורה $rowNum: מספר זהות {$data['id_number']} כבר קיים בקובץ בשורה {$processedIdNumbers[$data['id_number']]} - דילוג";
                            $skipped++;
                            continue;
                        }
                        
                        // Mark this ID number as processed
                        $processedIdNumbers[$data['id_number']] = $rowNum;
                        
                        // Try to find person by ID number
                        $stmt = $pdo->prepare("SELECT * FROM people WHERE husband_id = ? OR wife_id = ?");
                        $stmt->execute([$data['id_number'], $data['id_number']]);
                        $person = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($person) {
                            $data['person_id'] = $person['id'];
                            
                            // Fill missing details from people table
                            if (empty($data['first_name']) && !empty($person['first_name'])) {
                                $data['first_name'] = $person['first_name'];
                            }
                            if (empty($data['last_name']) && !empty($person['family_name'])) {
                                $data['last_name'] = $person['family_name'];
                            }
                            if (empty($data['city']) && !empty($person['city'])) {
                                $data['city'] = $person['city'];
                            }
                            if (empty($data['street']) && !empty($person['address'])) {
                                $data['street'] = $person['address'];
                            }
                            if (empty($data['phone'])) {
                                $data['phone'] = $person['phone'] ?? $person['husband_mobile'] ?? '';
                            }
                        } else {
                            // Not linked to person - add to needs linking list
                            if (!empty($data['first_name']) || !empty($data['last_name'])) {
                                $needsLinking[] = [
                                    'first_name' => $data['first_name'],
                                    'last_name' => $data['last_name'],
                                    'id_number' => $data['id_number']
                                ];
                            }
                        }
                        
                        // Check if record already exists by ID number
                        $existing = $supportsRepo->getByIdNumber($data['id_number']);
                        
                        // Prepare clean data for repository (only valid fields)
                        $cleanData = [
                            'person_id' => $data['person_id'],
                            'position_name' => $data['position_name'],
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'id_number' => $data['id_number'],
                            'city' => $data['city'],
                            'street' => $data['street'],
                            'phone' => $data['phone'],
                            'household_members' => $data['household_members'],
                            'married_children' => $data['married_children'],
                            'study_work_place_1' => $data['study_work_place_1'],
                            'income_scholarship_1' => $data['income_scholarship_1'],
                            'study_work_place_2' => $data['study_work_place_2'],
                            'income_scholarship_2' => $data['income_scholarship_2'],
                            'child_allowance' => $data['child_allowance'],
                            'survivor_allowance' => $data['survivor_allowance'],
                            'disability_allowance' => $data['disability_allowance'],
                            'income_guarantee' => $data['income_guarantee'],
                            'income_supplement' => $data['income_supplement'],
                            'rent_assistance' => $data['rent_assistance'],
                            'other_allowance_source' => $data['other_allowance_source'],
                            'other_allowance_amount' => $data['other_allowance_amount'],
                            'housing_expenses' => $data['housing_expenses'],
                            'tuition_expenses' => $data['tuition_expenses'],
                            'recurring_exceptional_expense' => $data['recurring_exceptional_expense'],
                            'exceptional_expense_details' => $data['exceptional_expense_details'],
                            'difficulty_reason' => $data['difficulty_reason'],
                            'notes' => $data['notes'],
                            'account_holder_name' => $data['account_holder_name'],
                            'bank_name' => $data['bank_name'],
                            'branch_number' => $data['branch_number'],
                            'account_number' => $data['account_number'],
                            'support_requester_name' => $data['support_requester_name'],
                            'transaction_number' => $data['transaction_number'],
                            'include_exceptional_in_calc' => $data['include_exceptional_in_calc']
                        ];
                        
                        if ($existing) {
                            // Check if data has changed
                            $hasChanges = false;
                            foreach ($cleanData as $key => $value) {
                                if ($key !== 'person_id' && isset($existing[$key]) && $existing[$key] !== $value) {
                                    $hasChanges = true;
                                    break;
                                }
                            }
                            
                            if ($hasChanges) {
                                $supportsRepo->update($existing['id'], $cleanData);
                                $updated++;
                            }
                        } else {
                            $supportsRepo->create($cleanData);
                            $imported++;
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = "שורה $rowNum: " . $e->getMessage();
                        $skipped++;
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Delete temp file
                unlink($uploadPath);
                
                $message = "ייבוא הושלם: $imported נוספו, $updated עודכנו";
                if ($skipped > 0) {
                    $message .= ", $skipped דולגו";
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'needs_linking' => $needsLinking
                ]);
                
            } catch (Exception $e) {
                // Rollback transaction
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // Delete temp file
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                
                throw new Exception('שגיאה בייבוא: ' . $e->getMessage());
            }
            break;
        
        case 'export_excel':
            // Export to Excel
            require_once '../vendor/autoload.php';
            
            $exportTab = $_GET['tab'] ?? 'data';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setRightToLeft(true);
            
            if ($exportTab === 'summary') {
                // Export summary table (same as what's shown in תמיכה tab)
                $supports = $supportsRepo->getAllWithCalculations(true);
                
                $headers = [
                    'שם', 'משפחה', 'מס\' תורם', 'כתובת', 'עיר',
                    'סה"כ הכנסות', 'סה"כ הוצאות', 'כולל חריגה?',
                    'סה"כ הכנסות לנפש', 'סה"כ לתמיכה'
                ];
                
                $worksheet->fromArray($headers, null, 'A1');
                
                $row = 2;
                $totalIncome = 0;
                $totalExpenses = 0;
                $totalSupport = 0;
                
                foreach ($supports as $support) {
                    $address = $support['street'] ?? '';
                    
                    $totalIncome += floatval($support['total_income'] ?? 0);
                    $totalExpenses += floatval($support['total_expenses'] ?? 0);
                    $totalSupport += floatval($support['support_amount'] ?? 0);
                    
                    $worksheet->fromArray([
                        $support['first_name'] ?? '',
                        $support['last_name'] ?? '',
                        $support['donor_number'] ?? '',
                        $address,
                        $support['city'] ?? '',
                        floatval($support['total_income'] ?? 0),
                        floatval($support['total_expenses'] ?? 0),
                        ($support['include_exceptional_in_calc'] ?? 1) == 1 ? 'כן' : 'לא',
                        floatval($support['income_per_person'] ?? 0),
                        floatval($support['support_amount'] ?? 0),
                    ], null, "A$row");
                    $row++;
                }
                
                // Add totals row
                $worksheet->fromArray([
                    'סה"כ:', '', '', '', '',
                    $totalIncome, $totalExpenses, '',
                    '', $totalSupport
                ], null, "A$row");
                $worksheet->getStyle("A$row:J$row")->getFont()->setBold(true);
                
            } else {
                // Export full data table (original behavior)
                $supports = $supportsRepo->getAll();
                
                $headers = [
                    'מזהה', 'תאריך יצירה', 'תאריך עדכון', 'שם עמדה', 'שם פרטי', 'שם משפחה',
                    'מספר זהות', 'עיר', 'רחוב', 'מס\' טל\'', 'מס\' נפשות בבית (כולל ההורים)',
                    'מס\' ילדים נשואים', 'מקום לימודים / עבודה', 'סכום הכנסה / מלגה',
                    'מקום לימודים / עבודה', 'סכום הכנסה / מלגה', 'קצבת ילדים', 'קצבת שארים',
                    'קצבת נכות', 'הבטחת הכנסה', 'השלמת הכנסה', 'סיוע בשכר דירה',
                    'מקור הקצבה אחר', 'סכום', 'הוצאות דיור', 'הוצאות שכר לימוד (סכום כולל למוסדות)',
                    'הוצאה חריגה קבועה', 'כולל חריגה בחישוב?', 'פירוט - הוצאה חריגה', 'פרט מה סיבת הקושי', 'הערות',
                    'שם בעל החשבון', 'בנק', 'סניף', 'מס\' חשבון', 'שם מבקש התמיכה', 'מספר עסקה'
                ];
                
                $worksheet->fromArray($headers, null, 'A1');
                
                $row = 2;
                foreach ($supports as $support) {
                    $worksheet->fromArray([
                        $support['id'],
                        $support['created_at'],
                        $support['updated_at'],
                        $support['position_name'],
                        $support['first_name'],
                        $support['last_name'],
                        $support['id_number'],
                        $support['city'],
                        $support['street'],
                        $support['phone'],
                        $support['household_members'],
                        $support['married_children'],
                        $support['study_work_place_1'],
                        $support['income_scholarship_1'],
                        $support['study_work_place_2'],
                        $support['income_scholarship_2'],
                        $support['child_allowance'],
                        $support['survivor_allowance'],
                        $support['disability_allowance'],
                        $support['income_guarantee'],
                        $support['income_supplement'],
                        $support['rent_assistance'],
                        $support['other_allowance_source'],
                        $support['other_allowance_amount'],
                        $support['housing_expenses'],
                        $support['tuition_expenses'],
                        $support['recurring_exceptional_expense'],
                        $support['include_exceptional_in_calc'] == 1 ? 'כן' : 'לא',
                        $support['exceptional_expense_details'],
                        $support['difficulty_reason'],
                        $support['notes'],
                        $support['account_holder_name'],
                        $support['bank_name'],
                        $support['branch_number'],
                        $support['account_number'],
                        $support['support_requester_name'],
                        $support['transaction_number']
                    ], null, "A$row");
                    $row++;
                }
            }
            
            // Create Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Output to browser
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="supports_export_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
        
        case 'approve_support':
            // Approve support and add to approved_supports table
            $supportId = $_POST['support_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $supportMonth = $_POST['support_month'] ?? '';
            
            if (!$supportId || !$supportMonth) {
                throw new Exception('Missing required parameters');
            }
            
            // Get support details
            $support = $supportsRepo->getById($supportId);
            if (!$support) {
                throw new Exception('Support record not found');
            }
            
            // If amount is 0 or empty, calculate it
            if (!$amount || floatval($amount) <= 0) {
                $includeExceptional = isset($support['include_exceptional_in_calc']) 
                    ? (bool)$support['include_exceptional_in_calc'] 
                    : true;
                $calculations = $supportsRepo->calculateSupport($support, $includeExceptional);
                $amount = $calculations['support_amount'] ?? 0;
                
                // If still 0, throw error
                if (floatval($amount) <= 0) {
                    throw new Exception('לא ניתן לחשב סכום תמיכה. נא למלא את השדה ידנית.');
                }
            }
            
            // Get approved_by user ID - validate it exists in users table
            $approvedBy = null;
            if (isset($_SESSION['user_id'])) {
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $checkUser->execute([$_SESSION['user_id']]);
                if ($checkUser->fetch()) {
                    $approvedBy = $_SESSION['user_id'];
                }
            }
            
            // Insert into approved_supports
            $stmt = $pdo->prepare("
                INSERT INTO approved_supports 
                (support_id, donor_number, first_name, last_name, amount, support_month, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $supportId,
                $support['donor_number'] ?? '',
                $support['first_name'] ?? '',
                $support['last_name'] ?? '',
                $amount,
                $supportMonth,
                $approvedBy
            ]);
            
            echo json_encode(['success' => true, 'message' => 'התמיכה אושרה בהצלחה']);
            break;
        
        case 'get_approved_supports':
            // Get all approved supports
            $stmt = $pdo->prepare("
                SELECT 
                    a.*,
                    u.username as approved_by_name
                FROM approved_supports a
                LEFT JOIN users u ON a.approved_by = u.id
                ORDER BY a.support_month DESC, a.created_at DESC
            ");
            $stmt->execute();
            $approvedSupports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $approvedSupports]);
            break;
        
        case 'delete_approved_support':
            // Delete an approved support record
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $stmt = $pdo->prepare("DELETE FROM approved_supports WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'נמחק בהצלחה']);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log('Supports API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
