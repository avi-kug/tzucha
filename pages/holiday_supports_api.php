<?php
// Don't set JSON header yet - export_excel needs different headers
require_once '../config/db.php';
require_once '../config/auth_enhanced.php';
require_once '../repositories/PeopleRepository.php';

auth_require_login($pdo);
auth_require_permission('supports');

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 60, 60); // 60 requests per minute
    check_request_size();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// CSRF Protection for mutating actions
$mutatingActions = ['save_calculation', 'delete_calculation', 'link_donor', 'search_donors', 'save_support', 'delete_support', 'delete_form', 'delete_approved_support', 'import_json', 'approve_support', 'calculate_support'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, $mutatingActions) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'פג תוקף הטופס, נסה שוב.']);
        exit;
    }
}

$peopleRepo = new PeopleRepository($pdo);

// Handle export_excel BEFORE setting JSON header
if ($action === 'export_excel') {
    require_once '../vendor/autoload.php';
    
    $tab = $_GET['tab'] ?? 'support';
    
    // Force proper headers BEFORE any output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    
    // Create new Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setRightToLeft(true);
    
    $filename = 'holiday_supports_export_' . date('Y-m-d') . '.xlsx';
    
    if ($tab === 'support') {
        // Export support table
        $sheet->setTitle('תמיכה');
        
        $headers = ['מס\' תורם', 'שם', 'משפחה', 'עלות תמיכה', 'תמיכה בסיסית (60%)', 'תמיכה מלאה', 'תאריך תמיכה'];
        $sheet->fromArray($headers, null, 'A1');
        
        $stmt = $pdo->query("
            SELECT 
                donor_number,
                first_name,
                last_name,
                support_cost,
                basic_support,
                full_support,
                support_date
            FROM holiday_supports
            WHERE approved_at IS NULL
            ORDER BY created_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        
        // Export Throttling Protection
        try {
            check_export_throttle($pdo, 'holiday_supports', count($data), 5, 3600, 10000);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'תמיכות_חג_' . date('Y-m-d') . '.xlsx';
        
    } elseif ($tab === 'approved') {
        // Export approved supports
        $sheet->setTitle('תמיכות שאושרו');
        
        $headers = ['מס\' תורם', 'שם', 'משפחה', 'סכום', 'תאריך תמיכה', 'תאריך אישור'];
        $sheet->fromArray($headers, null, 'A1');
        
        $stmt = $pdo->query("
            SELECT 
                donor_number,
                first_name,
                last_name,
                full_support,
                support_date,
                approved_at
            FROM holiday_supports
            WHERE approved_at IS NOT NULL
            ORDER BY approved_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        
        // Export Throttling Protection
        try {
            check_export_throttle($pdo, 'holiday_approved_supports', count($data), 5, 3600, 10000);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'תמיכות_חג_שאושרו_' . date('Y-m-d') . '.xlsx';
        
    } elseif ($tab === 'data') {
        // Export raw data
        $sheet->setTitle('נתונים');
        
        $headers = ['מזהה', 'תאריך יצירה', 'שם מלא', 'עיר', 'כתובת', 'נפשות', 'ילדים', 
                   'משכורת אב', 'משכורת אם', 'הכנסות נוספות', 'קצבאות', 
                   'שכר לימוד', 'שכר דירה', 'מס\' תורם'];
        $sheet->fromArray($headers, null, 'A1');
        
        $stmt = $pdo->query("
            SELECT 
                hf.form_id,
                hf.created_date,
                hf.full_name,
                hf.city,
                hf.street,
                hf.sum_kids,
                hf.sum_kids2,
                hf.maskorte_av,
                hf.maskorte_am,
                hf.hachnasa,
                hf.kitzva,
                hf.hotzaot_limud,
                hf.hotzaot_dira,
                hf.donor_number
            FROM holiday_forms hf
            ORDER BY hf.created_date DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        
        // Export Throttling Protection
        try {
            check_export_throttle($pdo, 'holiday_forms_data', count($data), 5, 3600, 10000);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        
        $sheet->fromArray($data, null, 'A2');
        
        $filename = 'טפסים_חג_' . date('Y-m-d') . '.xlsx';
    }
    
    // Output the file
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Now set JSON header for all other actions
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_data':
            $tab = $_GET['tab'] ?? 'support';
            
            if ($tab === 'support') {
                // Get support calculations
                $stmt = $pdo->query("
                    SELECT 
                        hs.*,
                        ROUND(hs.support_cost * 0.6, 2) as basic_support_calc
                    FROM holiday_supports hs
                    WHERE hs.approved_at IS NULL
                    ORDER BY hs.created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } elseif ($tab === 'calculations') {
                // Get calculation rules
                $stmt = $pdo->query("
                    SELECT * FROM holiday_calculations
                    ORDER BY created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } elseif ($tab === 'data') {
                // Get raw form data
                $stmt = $pdo->query("
                    SELECT 
                        hf.*,
                        COUNT(hfk.id) as kids_count
                    FROM holiday_forms hf
                    LEFT JOIN holiday_form_kids hfk ON hf.id = hfk.form_id
                    GROUP BY hf.id
                    ORDER BY hf.created_date DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } elseif ($tab === 'approved') {
                // Get approved supports
                $stmt = $pdo->query("
                    SELECT * FROM holiday_supports
                    WHERE approved_at IS NOT NULL
                    ORDER BY approved_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'import_json':
            // Load API credentials from .env
            $envFile = __DIR__ . '/../.env';
            $envVars = [];
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue; // Skip comments
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $envVars[trim($parts[0])] = trim($parts[1], '"');
                    }
                }
            }
            
            // Get dynamic parameters from request
            $lastId = $_POST['last_id'] ?? 0;
            $maxId = $_POST['max_id'] ?? 500;
            
            // Build URL from .env
            $baseUrl = $envVars['MATARA_FORMS_URL'] ?? '';
            $mosadId = $envVars['MATARA_MOSAD_ID'] ?? '';
            $apiPassword = $envVars['MATARA_API_PASSWORD'] ?? '';
            $tofesId = $envVars['MATARA_TOFES_ID'] ?? '';
            
            if (empty($baseUrl) || empty($apiPassword)) {
                throw new Exception('הגדרות API חסרות בקובץ .env');
            }
            
            $jsonUrl = sprintf(
                '%s?Action=GetJson&MosadId=%s&ApiPassword=%s&TofesId=%s&LastId=%d&MaxId=%d',
                $baseUrl,
                $mosadId,
                $apiPassword,
                $tofesId,
                $lastId,
                $maxId
            );
            
            if (empty($jsonUrl)) {
                throw new Exception('כתובת URL חסרה');
            }

            // Fetch JSON data
            $jsonData = @file_get_contents($jsonUrl);
            if ($jsonData === false) {
                throw new Exception('לא ניתן לטעון את הנתונים מהכתובת');
            }

            $forms = json_decode($jsonData, true);
            if (!is_array($forms)) {
                throw new Exception('פורמט JSON לא תקין');
            }

            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($forms as $form) {
                try {
                    $formId = $form['ID'] ?? null;
                    if (!$formId) continue;

                    // Check if form exists
                    $stmt = $pdo->prepare("SELECT id FROM holiday_forms WHERE form_id = ?");
                    $stmt->execute([$formId]);
                    $existing = $stmt->fetch();

                    // Parse kids data
                    $kidsData = [];
                    for ($i = 1; $i <= 16; $i++) {
                        if (!empty($form["Kid_Name_$i"])) {
                            $kidsData[] = [
                                'name' => $form["Kid_Name_$i"] ?? '',
                                'status' => $form["Kid_Status_$i"] ?? '',
                                'birthdate' => $form["Kid_Bd_$i"] ?? '',
                                'age' => $form["Age_$i"] ?? 0
                            ];
                        }
                    }

                    // Determine gender and married status from kids
                    $marriedCount = $form['NumKids'] ?? 0;
                    $totalKids = $form['SumKids2'] ?? 0;

                    if ($existing) {
                        // Update existing form
                        $stmt = $pdo->prepare("
                            UPDATE holiday_forms SET
                                created_date = ?,
                                masof_id = ?,
                                emda = ?,
                                full_name = ?,
                                street = ?,
                                city = ?,
                                sum_kids = ?,
                                num_kids = ?,
                                maskorte_av = ?,
                                maskorte_am = ?,
                                hachnasa = ?,
                                kitzva = ?,
                                hotzaot_limud = ?,
                                hotzaot_dira = ?,
                                hotzaot_chariga = ?,
                                hotzaot_chariga2 = ?,
                                sum_nefesh = ?,
                                help = ?,
                                sum_kids2 = ?,
                                sum_kids3 = ?,
                                sum_kids_m1 = ?,
                                sum_kids_m2 = ?,
                                sum_kids_m3 = ?,
                                bank_name = ?,
                                bank = ?,
                                snif = ?,
                                account = ?,
                                name_bakasha = ?,
                                transaction_id = ?,
                                kids_data = ?,
                                ishur1 = ?,
                                ishur1_ = ?,
                                ishur_1_ = ?,
                                ishur2 = ?,
                                ishur2_ = ?,
                                ishur_2_ = ?,
                                ishur3 = ?,
                                ishur3_ = ?,
                                ishur_3_ = ?,
                                ishur = ?,
                                updated_at = NOW()
                            WHERE form_id = ?
                        ");
                        $stmt->execute([
                            $form['CreatedDate'] ?? null,
                            $form['MasofId'] ?? null,
                            $form['Emda'] ?? '',
                            $form['FullName'] ?? '',
                            $form['Street'] ?? '',
                            $form['City'] ?? '',
                            $form['SumKids'] ?? 0,
                            $form['NumKids'] ?? 0,
                            $form['MaskorteAv'] ?? 0,
                            $form['MaskorteAm'] ?? 0,
                            $form['Hachnasa'] ?? 0,
                            $form['Kitzva'] ?? 0,
                            $form['HotzaotLimud'] ?? 0,
                            $form['HotzaotDira'] ?? 0,
                            $form['HotzaotChariga'] ?? 0,
                            $form['HotzaotChariga2'] ?? '',
                            $form['SumNefesh'] ?? 0,
                            $form['Help'] ?? '',
                            $form['SumKids2'] ?? 0,
                            $form['SumKids3'] ?? 0,
                            $form['SumKidsM1'] ?? 0,
                            $form['SumKidsM2'] ?? 0,
                            $form['SumKidsM3'] ?? 0,
                            $form['Bank_Name'] ?? '',
                            $form['Bank'] ?? '',
                            $form['Snif'] ?? '',
                            $form['Account'] ?? '',
                            $form['NameBakasha'] ?? '',
                            $form['TransactionId'] ?? '',
                            json_encode($kidsData, JSON_UNESCAPED_UNICODE),
                            $form['Ishur1'] ?? '',
                            $form['Ishur1_'] ?? 0,
                            $form['Ishur_1_'] ?? '',
                            $form['Ishur2'] ?? '',
                            $form['Ishur2_'] ?? 0,
                            $form['Ishur_2_'] ?? '',
                            $form['Ishur3'] ?? '',
                            $form['Ishur3_'] ?? 0,
                            $form['Ishur_3_'] ?? '',
                            $form['Ishur'] ?? '',
                            $formId
                        ]);
                        $dbFormId = $existing['id'];
                        $updated++;
                    } else {
                        // Insert new form
                        $stmt = $pdo->prepare("
                            INSERT INTO holiday_forms (
                                form_id, created_date, masof_id, emda, full_name, street, city,
                                sum_kids, num_kids, maskorte_av, maskorte_am, hachnasa, kitzva,
                                hotzaot_limud, hotzaot_dira, hotzaot_chariga, hotzaot_chariga2,
                                sum_nefesh, help, sum_kids2, sum_kids3,
                                sum_kids_m1, sum_kids_m2, sum_kids_m3,
                                bank_name, bank, snif, account, name_bakasha, transaction_id,
                                kids_data,
                                ishur1, ishur1_, ishur_1_,
                                ishur2, ishur2_, ishur_2_,
                                ishur3, ishur3_, ishur_3_,
                                ishur,
                                created_at, updated_at
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                NOW(), NOW()
                            )
                        ");
                        $stmt->execute([
                            $formId,
                            $form['CreatedDate'] ?? null,
                            $form['MasofId'] ?? null,
                            $form['Emda'] ?? '',
                            $form['FullName'] ?? '',
                            $form['Street'] ?? '',
                            $form['City'] ?? '',
                            $form['SumKids'] ?? 0,
                            $form['NumKids'] ?? 0,
                            $form['MaskorteAv'] ?? 0,
                            $form['MaskorteAm'] ?? 0,
                            $form['Hachnasa'] ?? 0,
                            $form['Kitzva'] ?? 0,
                            $form['HotzaotLimud'] ?? 0,
                            $form['HotzaotDira'] ?? 0,
                            $form['HotzaotChariga'] ?? 0,
                            $form['HotzaotChariga2'] ?? '',
                            $form['SumNefesh'] ?? 0,
                            $form['Help'] ?? '',
                            $form['SumKids2'] ?? 0,
                            $form['SumKids3'] ?? 0,
                            $form['SumKidsM1'] ?? 0,
                            $form['SumKidsM2'] ?? 0,
                            $form['SumKidsM3'] ?? 0,
                            $form['Bank_Name'] ?? '',
                            $form['Bank'] ?? '',
                            $form['Snif'] ?? '',
                            $form['Account'] ?? '',
                            $form['NameBakasha'] ?? '',
                            $form['TransactionId'] ?? '',
                            json_encode($kidsData, JSON_UNESCAPED_UNICODE),
                            $form['Ishur1'] ?? '',
                            $form['Ishur1_'] ?? 0,
                            $form['Ishur_1_'] ?? '',
                            $form['Ishur2'] ?? '',
                            $form['Ishur2_'] ?? 0,
                            $form['Ishur_2_'] ?? '',
                            $form['Ishur3'] ?? '',
                            $form['Ishur3_'] ?? 0,
                            $form['Ishur_3_'] ?? '',
                            $form['Ishur'] ?? ''
                        ]);
                        $dbFormId = $pdo->lastInsertId();
                        $imported++;
                    }

                    // Store kids data separately
                    if (!empty($kidsData)) {
                        // Delete old kids data
                        $stmt = $pdo->prepare("DELETE FROM holiday_form_kids WHERE form_id = ?");
                        $stmt->execute([$dbFormId]);

                        // Insert new kids data
                        $stmt = $pdo->prepare("
                            INSERT INTO holiday_form_kids (form_id, name, status, birthdate, age, gender)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($kidsData as $kid) {
                            // Try to determine gender from status
                            $gender = null;
                            $status = $kid['status'] ?? '';
                            if (stripos($status, 'בן') !== false || stripos($status, 'זכר') !== false) {
                                $gender = 'male';
                            } elseif (stripos($status, 'בת') !== false || stripos($status, 'נקבה') !== false) {
                                $gender = 'female';
                            }

                            $stmt->execute([
                                $dbFormId,
                                $kid['name'],
                                $kid['status'],
                                $kid['birthdate'],
                                $kid['age'],
                                $gender
                            ]);
                        }
                    }

                } catch (Exception $e) {
                    $errors[] = "טופס $formId: " . $e->getMessage();
                }
            }

            echo json_encode([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors
            ]);
            break;

        case 'save_calculation':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $calculationType = $_POST['calculation_type'] ?? 'fixed';
            
            // Conditions
            $useGender = isset($_POST['use_gender']) ? 1 : 0;
            $gender = $_POST['gender'] ?? '';
            $useKidsAge = isset($_POST['use_kids_age']) ? 1 : 0;
            $kidsAgeFrom = $_POST['kids_age_from'] ?? null;
            $kidsAgeTo = $_POST['kids_age_to'] ?? null;
            $useCity = isset($_POST['use_city']) ? 1 : 0;
            $city = $_POST['city'] ?? '';
            $useMarriedYears = isset($_POST['use_married_years']) ? 1 : 0;
            $marriedYearsFrom = $_POST['married_years_from'] ?? null;
            $marriedYearsTo = $_POST['married_years_to'] ?? null;
            $useKidsCount = isset($_POST['use_kids_count']) ? 1 : 0;
            $kidsFrom = $_POST['kids_from'] ?? null;
            $kidsTo = $_POST['kids_to'] ?? null;
            $amount = $_POST['amount'] ?? 0;

            $conditions = json_encode([
                'use_gender' => $useGender,
                'gender' => $gender,
                'use_kids_age' => $useKidsAge,
                'kids_age_from' => $kidsAgeFrom,
                'kids_age_to' => $kidsAgeTo,
                'use_city' => $useCity,
                'city' => $city,
                'use_married_years' => $useMarriedYears,
                'married_years_from' => $marriedYearsFrom,
                'married_years_to' => $marriedYearsTo,
                'use_kids_count' => $useKidsCount,
                'kids_from' => $kidsFrom,
                'kids_to' => $kidsTo
            ], JSON_UNESCAPED_UNICODE);

            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE holiday_calculations 
                    SET name = ?, calculation_type = ?, conditions = ?, amount = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $calculationType, $conditions, $amount, $id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO holiday_calculations (name, calculation_type, conditions, amount, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$name, $calculationType, $conditions, $amount]);
                $id = $pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'delete_calculation':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM holiday_calculations WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'link_donor':
            $formId = $_POST['form_id'] ?? 0;
            $donorNumber = $_POST['donor_number'] ?? 0;

            $stmt = $pdo->prepare("UPDATE holiday_forms SET donor_number = ? WHERE id = ?");
            $stmt->execute([$donorNumber, $formId]);

            echo json_encode(['success' => true]);
            break;

        case 'search_donors':
            $search = $_POST['search'] ?? '';
            $people = $peopleRepo->searchPeople($search, 20);
            echo json_encode(['success' => true, 'data' => $people]);
            break;

        case 'save_support':
            $id = $_POST['id'] ?? null;
            $formId = $_POST['form_id'] ?? null;
            
            // Basic support fields
            $donorNumber = $_POST['donor_number'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $supportCost = $_POST['support_cost'] ?? 0;
            $basicSupport = $supportCost * 0.6;
            $fullSupport = $_POST['full_support'] ?? 0;
            $supportDate = $_POST['support_date'] ?? date('Y-m-d');
            $notes = $_POST['notes'] ?? '';
            
            // Extended form fields
            $fullName = $_POST['full_name'] ?? '';
            $createdDate = $_POST['created_date'] ?? null;
            $masofId = $_POST['masof_id'] ?? null;
            $emda = $_POST['emda'] ?? '';
            $street = $_POST['street'] ?? '';
            $city = $_POST['city'] ?? '';
            $sumKids = $_POST['sum_kids'] ?? 0;
            $numKids = $_POST['num_kids'] ?? 0;
            $maskorteAv = $_POST['maskorte_av'] ?? 0;
            $maskorteAm = $_POST['maskorte_am'] ?? 0;
            $hachnasa = $_POST['hachnasa'] ?? 0;
            $kitzva = $_POST['kitzva'] ?? 0;
            $hotzaotLimud = $_POST['hotzaot_limud'] ?? 0;
            $hotzaotDira = $_POST['hotzaot_dira'] ?? 0;
            $hotzaotChariga = $_POST['hotzaot_chariga'] ?? 0;
            $hotzaotChariga2 = $_POST['hotzaot_chariga2'] ?? '';
            $sumNefesh = $_POST['sum_nefesh'] ?? 0;
            $help = $_POST['help'] ?? '';
            $sumKids2 = $_POST['sum_kids2'] ?? 0;
            $sumKids3 = $_POST['sum_kids3'] ?? 0;
            $sumKidsM1 = $_POST['sum_kids_m1'] ?? 0;
            $sumKidsM2 = $_POST['sum_kids_m2'] ?? 0;
            $sumKidsM3 = $_POST['sum_kids_m3'] ?? 0;
            $bankName = $_POST['bank_name'] ?? '';
            $bank = $_POST['bank'] ?? '';
            $snif = $_POST['snif'] ?? '';
            $account = $_POST['account'] ?? '';
            $nameBakasha = $_POST['name_bakasha'] ?? '';
            $transactionId = $_POST['transaction_id'] ?? '';
            
            // Ishur fields
            $ishur1 = $_POST['ishur1'] ?? '';
            $ishur1_ = $_POST['ishur1_'] ?? 0;
            $ishur_1_ = $_POST['ishur_1_'] ?? '';
            $ishur2 = $_POST['ishur2'] ?? '';
            $ishur2_ = $_POST['ishur2_'] ?? 0;
            $ishur_2_ = $_POST['ishur_2_'] ?? '';
            $ishur3 = $_POST['ishur3'] ?? '';
            $ishur3_ = $_POST['ishur3_'] ?? 0;
            $ishur_3_ = $_POST['ishur_3_'] ?? '';
            $ishur = $_POST['ishur'] ?? '';
            
            // Kids data
            $kidsData = [];
            for ($i = 1; $i <= 16; $i++) {
                if (!empty($_POST["kid_name_$i"])) {
                    $kidsData[] = [
                        'name' => $_POST["kid_name_$i"] ?? '',
                        'status' => $_POST["kid_status_$i"] ?? '',
                        'birthdate' => $_POST["kid_bd_$i"] ?? '',
                        'age' => $_POST["age_$i"] ?? 0
                    ];
                }
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Save/Update holiday_support record
                if ($id) {
                    $stmt = $pdo->prepare("
                        UPDATE holiday_supports 
                        SET donor_number = ?, first_name = ?, last_name = ?, 
                            support_cost = ?, basic_support = ?, full_support = ?,
                            support_date = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $donorNumber, $firstName, $lastName, 
                        $supportCost, $basicSupport, $fullSupport,
                        $supportDate, $notes, $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO holiday_supports 
                        (donor_number, first_name, last_name, support_cost, basic_support, 
                         full_support, support_date, notes, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $donorNumber, $firstName, $lastName, 
                        $supportCost, $basicSupport, $fullSupport, $supportDate, $notes
                    ]);
                    $id = $pdo->lastInsertId();
                }
                
                // Save/Update holiday_forms record if extended data is provided
                if ($fullName || $city || $street || $sumKids > 0) {
                    // Build extended data JSON
                    $extendedData = json_encode([
                        'ishur1' => $ishur1,
                        'ishur1_' => $ishur1_,
                        'ishur_1_' => $ishur_1_,
                        'ishur2' => $ishur2,
                        'ishur2_' => $ishur2_,
                        'ishur_2_' => $ishur_2_,
                        'ishur3' => $ishur3,
                        'ishur3_' => $ishur3_,
                        'ishur_3_' => $ishur_3_,
                        'ishur' => $ishur
                    ], JSON_UNESCAPED_UNICODE);
                    
                    if ($formId) {
                        // Update existing form
                        $stmt = $pdo->prepare("
                            UPDATE holiday_forms SET
                                created_date = ?,
                                masof_id = ?,
                                emda = ?,
                                full_name = ?,
                                street = ?,
                                city = ?,
                                sum_kids = ?,
                                num_kids = ?,
                                maskorte_av = ?,
                                maskorte_am = ?,
                                hachnasa = ?,
                                kitzva = ?,
                                hotzaot_limud = ?,
                                hotzaot_dira = ?,
                                hotzaot_chariga = ?,
                                hotzaot_chariga2 = ?,
                                sum_nefesh = ?,
                                help = ?,
                                sum_kids2 = ?,
                                sum_kids3 = ?,
                                sum_kids_m1 = ?,
                                sum_kids_m2 = ?,
                                sum_kids_m3 = ?,
                                bank_name = ?,
                                bank = ?,
                                snif = ?,
                                account = ?,
                                name_bakasha = ?,
                                transaction_id = ?,
                                kids_data = ?,
                                ishur1 = ?,
                                ishur1_ = ?,
                                ishur_1_ = ?,
                                ishur2 = ?,
                                ishur2_ = ?,
                                ishur_2_ = ?,
                                ishur3 = ?,
                                ishur3_ = ?,
                                ishur_3_ = ?,
                                ishur = ?,
                                donor_number = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $createdDate,
                            $masofId,
                            $emda,
                            $fullName,
                            $street,
                            $city,
                            $sumKids,
                            $numKids,
                            $maskorteAv,
                            $maskorteAm,
                            $hachnasa,
                            $kitzva,
                            $hotzaotLimud,
                            $hotzaotDira,
                            $hotzaotChariga,
                            $hotzaotChariga2,
                            $sumNefesh,
                            $help,
                            $sumKids2,
                            $sumKids3,
                            $sumKidsM1,
                            $sumKidsM2,
                            $sumKidsM3,
                            $bankName,
                            $bank,
                            $snif,
                            $account,
                            $nameBakasha,
                            $transactionId,
                            json_encode($kidsData, JSON_UNESCAPED_UNICODE),
                            $ishur1,
                            $ishur1_,
                            $ishur_1_,
                            $ishur2,
                            $ishur2_,
                            $ishur_2_,
                            $ishur3,
                            $ishur3_,
                            $ishur_3_,
                            $ishur,
                            $donorNumber,
                            $formId
                        ]);
                    } else {
                        // Insert new form
                        $stmt = $pdo->prepare("
                            INSERT INTO holiday_forms (
                                form_id, created_date, masof_id, emda, full_name, street, city,
                                sum_kids, num_kids, maskorte_av, maskorte_am, hachnasa, kitzva,
                                hotzaot_limud, hotzaot_dira, hotzaot_chariga, hotzaot_chariga2,
                                sum_nefesh, help, sum_kids2, sum_kids3,
                                sum_kids_m1, sum_kids_m2, sum_kids_m3,
                                bank_name, bank, snif, account, name_bakasha, transaction_id,
                                kids_data, 
                                ishur1, ishur1_, ishur_1_,
                                ishur2, ishur2_, ishur_2_,
                                ishur3, ishur3_, ishur_3_,
                                ishur,
                                donor_number, created_at, updated_at
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, NOW(), NOW()
                            )
                        ");
                        $stmt->execute([
                            'MANUAL_' . time() . '_' . $id,
                            $createdDate,
                            $masofId,
                            $emda,
                            $fullName,
                            $street,
                            $city,
                            $sumKids,
                            $numKids,
                            $maskorteAv,
                            $maskorteAm,
                            $hachnasa,
                            $kitzva,
                            $hotzaotLimud,
                            $hotzaotDira,
                            $hotzaotChariga,
                            $hotzaotChariga2,
                            $sumNefesh,
                            $help,
                            $sumKids2,
                            $sumKids3,
                            $sumKidsM1,
                            $sumKidsM2,
                            $sumKidsM3,
                            $bankName,
                            $bank,
                            $snif,
                            $account,
                            $nameBakasha,
                            $transactionId,
                            json_encode($kidsData, JSON_UNESCAPED_UNICODE),
                            $ishur1,
                            $ishur1_,
                            $ishur_1_,
                            $ishur2,
                            $ishur2_,
                            $ishur_2_,
                            $ishur3,
                            $ishur3_,
                            $ishur_3_,
                            $ishur,
                            $donorNumber
                        ]);
                        $formId = $pdo->lastInsertId();
                    }
                    
                    // Store kids data separately
                    if (!empty($kidsData)) {
                        // Delete old kids data
                        $stmt = $pdo->prepare("DELETE FROM holiday_form_kids WHERE form_id = ?");
                        $stmt->execute([$formId]);

                        // Insert new kids data
                        $stmt = $pdo->prepare("
                            INSERT INTO holiday_form_kids (form_id, name, status, birthdate, age, gender)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($kidsData as $kid) {
                            // Try to determine gender from status
                            $gender = 'unknown';
                            $status = $kid['status'] ?? '';
                            if (stripos($status, 'בן') !== false || stripos($status, 'זכר') !== false) {
                                $gender = 'male';
                            } elseif (stripos($status, 'בת') !== false || stripos($status, 'נקבה') !== false) {
                                $gender = 'female';
                            }

                            $stmt->execute([
                                $formId,
                                $kid['name'],
                                $kid['status'],
                                $kid['birthdate'],
                                $kid['age'],
                                $gender
                            ]);
                        }
                    }
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'id' => $id, 'form_id' => $formId]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'approve_supports':
            $ids = $_POST['ids'] ?? [];
            
            if (empty($ids) || !is_array($ids)) {
                throw new Exception('לא נבחרו תמיכות לאישור');
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                UPDATE holiday_supports 
                SET approved_at = NOW()
                WHERE id IN ($placeholders) AND approved_at IS NULL
            ");
            $stmt->execute($ids);

            $approved = $stmt->rowCount();
            echo json_encode(['success' => true, 'approved' => $approved]);
            break;

        case 'delete_support':
            $id = $_POST['id'] ?? 0;
            
            // Prevent accidental double-delete
            check_idempotency($pdo, 'delete_holiday_support', ['id' => $id], 10); // 10 seconds window
            
            $stmt = $pdo->prepare("DELETE FROM holiday_supports WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_stats':
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN donor_number IS NOT NULL THEN 1 ELSE 0 END) as linked,
                    SUM(CASE WHEN donor_number IS NULL THEN 1 ELSE 0 END) as unlinked
                FROM holiday_forms
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'get_donors_list':
            $people = $peopleRepo->getAll();
            $donors = [];
            foreach ($people as $person) {
                if (!empty($person['donor_number'])) {
                    $donors[] = [
                        'donor_number' => $person['donor_number'],
                        'first_name' => $person['first_name'] ?? '',
                        'last_name' => $person['family_name'] ?? ''
                    ];
                }
            }
            echo json_encode(['success' => true, 'data' => $donors]);
            break;

        case 'get_form_by_donor':
            $donorNumber = $_GET['donor_number'] ?? '';
            
            // Get form data
            $stmt = $pdo->prepare("SELECT * FROM holiday_forms WHERE donor_number = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$donorNumber]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get person data for address and city
            $stmt = $pdo->prepare("SELECT address, city, full_name, first_name, family_name, husband_id FROM people WHERE donor_number = ? LIMIT 1");
            $stmt->execute([$donorNumber]);
            $person = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Merge person data into form if not already present
            if ($person && $form) {
                // Use person data if form data is empty
                $form['street'] = $form['street'] ?: ($person['address'] ?? '');
                $form['city'] = $form['city'] ?: ($person['city'] ?? '');
                $form['full_name'] = $form['full_name'] ?: ($person['full_name'] ?? '');
            } elseif ($person && !$form) {
                // Create basic form structure from person data
                $form = [
                    'street' => $person['address'] ?? '',
                    'city' => $person['city'] ?? '',
                    'full_name' => $person['full_name'] ?? '',
                    'donor_number' => $donorNumber
                ];
            }
            
            // Get kids data from holiday_form_kids
            $kids = [];
            if ($form && isset($form['id'])) {
                // If we have a form_id, use it
                $stmt = $pdo->prepare("
                    SELECT name, status, birthdate, age, gender 
                    FROM holiday_form_kids 
                    WHERE form_id = ? 
                    ORDER BY id
                ");
                $stmt->execute([$form['id']]);
                $kids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // If no kids found via form_id, try to get from main children table
            if (empty($kids) && $person && !empty($person['husband_id'])) {
                $stmt = $pdo->prepare("
                    SELECT 
                        child_name as name,
                        CASE 
                            WHEN gender = '???' THEN 'זכר'
                            WHEN gender = '????' THEN 'נקבה'
                            ELSE gender
                        END as status,
                        birth_date_gregorian as birthdate,
                        YEAR(CURDATE()) - birth_year as age,
                        CASE 
                            WHEN gender = '???' THEN 'male'
                            WHEN gender = '????' THEN 'female'
                            ELSE 'unknown'
                        END as gender
                    FROM children 
                    WHERE parent_husband_id = ?
                    ORDER BY birth_year DESC, birth_month DESC, birth_day DESC
                ");
                $stmt->execute([$person['husband_id']]);
                $kids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Add kids data to form if we found any
            if (!empty($kids)) {
                $form['kids_data'] = json_encode($kids);
                $form['sum_kids2'] = count($kids);
            }
            
            echo json_encode([
                'success' => true, 
                'data' => $form, 
                'debug' => [
                    'person' => $person, 
                    'form_id' => $form['id'] ?? null,
                    'kids_count' => count($kids),
                    'has_husband_id' => !empty($person['husband_id'])
                ]
            ]);
            break;

        case 'delete_form':
            $id = $_POST['id'] ?? 0;
            
            // Prevent accidental double-delete
            check_idempotency($pdo, 'delete_holiday_form', ['id' => $id], 10); // 10 seconds window
            
            $stmt = $pdo->prepare("DELETE FROM holiday_forms WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            
            // Prevent accidental double-delete
            check_idempotency($pdo, 'delete_approved_holiday_support', ['id' => $id], 10); // 10 seconds window
            
            break;

        case 'delete_approved_support':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM holiday_supports WHERE id = ? AND approved_at IS NOT NULL");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'apply_calculations':
            // Apply calculation rules to holiday forms and create/update supports
            $stmt = $pdo->query("SELECT * FROM holiday_calculations ORDER BY id");
            $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("
                SELECT 
                    hf.*,
                    GROUP_CONCAT(hfk.age) as kids_ages,
                    GROUP_CONCAT(hfk.gender) as kids_genders,
                    COUNT(hfk.id) as kids_count
                FROM holiday_forms hf
                LEFT JOIN holiday_form_kids hfk ON hf.id = hfk.form_id
                WHERE hf.donor_number IS NOT NULL
                GROUP BY hf.id
            ");
            $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $applied = 0;
            foreach ($forms as $form) {
                $matchedAmount = 0;

                foreach ($calculations as $calc) {
                    $conditions = json_decode($calc['conditions'], true);
                    $calculationType = $calc['calculation_type'] ?? 'fixed';
                    $amount = floatval($calc['amount']);
                    $match = true;
                    $multiplier = 1;
                    $matchCount = 0;

                    // Check gender conditions
                    if ($conditions['use_gender'] && !empty($conditions['gender'])) {
                        $genders = array_filter(explode(',', $form['kids_genders'] ?? ''));
                        $genderMatch = false;
                        foreach ($genders as $gender) {
                            if (trim($gender) === $conditions['gender']) {
                                $genderMatch = true;
                                $matchCount++;
                            }
                        }
                        if (!$genderMatch) $match = false;
                    }

                    // Check kids age conditions
                    if ($conditions['use_kids_age']) {
                        $ages = array_filter(explode(',', $form['kids_ages'] ?? ''));
                        $ageMatchCount = 0;
                        foreach ($ages as $age) {
                            if ($age >= $conditions['kids_age_from'] && $age <= $conditions['kids_age_to']) {
                                $ageMatchCount++;
                            }
                        }
                        if ($ageMatchCount === 0) {
                            $match = false;
                        } else {
                            $matchCount = $ageMatchCount;
                        }
                    }

                    // Check city conditions
                    if ($conditions['use_city'] && !empty($conditions['city'])) {
                        if (stripos($form['city'], $conditions['city']) === false) {
                            $match = false;
                        }
                    }

                    // Check married years conditions
                    if ($conditions['use_married_years']) {
                        $m1 = intval($form['sum_kids_m1'] ?? 0);
                        $m2 = intval($form['sum_kids_m2'] ?? 0);
                        $m3 = intval($form['sum_kids_m3'] ?? 0);
                        
                        $yearsFrom = intval($conditions['married_years_from'] ?? 0);
                        $yearsTo = intval($conditions['married_years_to'] ?? 50);
                        
                        $matchMarried = 0;
                        // 0-3 years
                        if ($yearsFrom <= 3 && $yearsTo >= 0) $matchMarried += $m1;
                        // 3-9 years
                        if ($yearsFrom <= 9 && $yearsTo >= 3) $matchMarried += $m2;
                        // 9+ years
                        if ($yearsFrom <= 50 && $yearsTo >= 9) $matchMarried += $m3;
                        
                        if ($matchMarried === 0) {
                            $match = false;
                        } else {
                            $multiplier = $matchMarried;
                        }
                    }

                    // Check kids count conditions
                    if ($conditions['use_kids_count']) {
                        $kidsCount = intval($form['kids_count'] ?? 0);
                        if ($kidsCount < $conditions['kids_from'] || $kidsCount > $conditions['kids_to']) {
                            $match = false;
                        } else {
                            $multiplier = $kidsCount;
                        }
                    }

                    // Calculate amount based on calculation type
                    if ($match) {
                        switch ($calculationType) {
                            case 'fixed':
                                // Fixed amount
                                $matchedAmount += $amount;
                                break;
                            case 'multiply':
                                // Multiply by count (e.g., number of married couples)
                                $matchedAmount += $amount * $multiplier;
                                break;
                            case 'per_item':
                                // Amount per item (e.g., per child)
                                $matchedAmount += $amount * ($matchCount > 0 ? $matchCount : $multiplier);
                                break;
                            case 'per_match':
                                // Amount per match (e.g., per child in age range)
                                $matchedAmount += $amount * $matchCount;
                                break;
                            default:
                                $matchedAmount += $amount;
                        }
                    }
                }

                if ($matchedAmount > 0) {
                    // Check if support already exists
                    $stmt = $pdo->prepare("
                        SELECT id FROM holiday_supports 
                        WHERE donor_number = ? AND approved_at IS NULL
                    ");
                    $stmt->execute([$form['donor_number']]);
                    $existing = $stmt->fetch();

                    $basicSupport = $matchedAmount * 0.6;

                    if ($existing) {
                        $stmt = $pdo->prepare("
                            UPDATE holiday_supports 
                            SET support_cost = ?, basic_support = ?, full_support = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$matchedAmount, $basicSupport, $matchedAmount, $existing['id']]);
                    } else {
                        // Get donor details
                        $person = $peopleRepo->getPersonByDonorNumber($form['donor_number']);
                        $firstName = $person['first_name'] ?? '';
                        $lastName = $person['family_name'] ?? '';

                        $stmt = $pdo->prepare("
                            INSERT INTO holiday_supports 
                            (donor_number, first_name, last_name, support_cost, basic_support, 
                             full_support, support_date, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $stmt->execute([
                            $form['donor_number'], $firstName, $lastName,
                            $matchedAmount, $basicSupport, $matchedAmount,
                            date('Y-m-d')
                        ]);
                    }
                    $applied++;
                }
            }

            echo json_encode(['success' => true, 'applied' => $applied]);
            break;

        default:
            throw new Exception('פעולה לא חוקית');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    error_log('Holiday Supports API Error: ' . $e->getMessage());
}
