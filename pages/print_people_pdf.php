<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Increase limits for large reports
ini_set('memory_limit', '1024M'); // 1GB for very large reports
ini_set('max_execution_time', '600'); // 10 minutes
set_time_limit(600);

// Increase PCRE limits for large HTML processing in mPDF
ini_set('pcre.backtrack_limit', '5000000'); // 5x default (prevents regex backtrack errors)
ini_set('pcre.recursion_limit', '100000');  // 10x default

require_once '../config/db.php';
require_once '../vendor/autoload.php';
require_once '../config/auth.php';

auth_require_login($pdo);
auth_require_permission('people');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

if (!csrf_validate()) {
    die('Invalid CSRF');
}

// Get parameters
$reportType = $_POST['reportType'] ?? '';
$outputType = $_POST['outputType'] ?? 'report';
$selectedJson = $_POST['selected'] ?? '[]';
$selected = json_decode($selectedJson, true);
$monthsJson = $_POST['months'] ?? '[]';
$months = json_decode($monthsJson, true);
$sortBy = $_POST['sortBy'] ?? 'gizbar';
$partLabel = $_POST['partLabel'] ?? ''; // For split PDFs
if (!is_array($months)) {
    $months = [];
}

if (!in_array($reportType, ['amarchal', 'gizbar']) || empty($selected)) {
    die('Invalid parameters');
}

// Log for debugging large reports
$selectedCount = count($selected);  
$monthsCount = count($months) ?: 1;
$estimatedLabels = ($outputType === 'labels') ? $selectedCount * $monthsCount : 0;
error_log("Print PDF: reportType=$reportType, outputType=$outputType, selected count=$selectedCount, months=$monthsCount, estimated labels=$estimatedLabels, partLabel=$partLabel");

// Warning for extremely large jobs
if ($estimatedLabels > 200) {
    error_log("WARNING: Large label job - $estimatedLabels labels may cause timeout/memory issues");
}

if ($reportType === 'gizbar') {
    // Build map of gizbar -> amarchal for sorting
    $mapStmt = $pdo->query("SELECT gizbar, amarchal FROM people WHERE gizbar IS NOT NULL AND gizbar <> ''");
    $gizbarMap = [];
    foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $g = trim((string)($row['gizbar'] ?? ''));
        $a = trim((string)($row['amarchal'] ?? ''));
        if ($g !== '' && !isset($gizbarMap[$g])) {
            $gizbarMap[$g] = $a;
        }
    }

    $sortBy = $sortBy === 'amarchal' ? 'amarchal' : 'gizbar';
    usort($selected, function ($a, $b) use ($gizbarMap, $sortBy) {
        $aName = trim((string)$a);
        $bName = trim((string)$b);
        if ($sortBy === 'amarchal') {
            $aA = $gizbarMap[$aName] ?? '';
            $bA = $gizbarMap[$bName] ?? '';
            $cmp = strcasecmp(mb_strtolower($aA, 'UTF-8'), mb_strtolower($bA, 'UTF-8'));
            if ($cmp !== 0) { return $cmp; }
        }
        return strcasecmp(mb_strtolower($aName, 'UTF-8'), mb_strtolower($bName, 'UTF-8'));
    });
}

// Cache reports for PDF rendering
$reports = [];

// Check if we need to create Excel spreadsheet (not needed for labels)
$needsSpreadsheet = ($outputType !== 'labels');
$spreadsheet = null;

if ($needsSpreadsheet) {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0);
}

// Process each selected item
foreach ($selected as $selectedName) {
    if ($reportType === 'amarchal') {
        // For Amarchal: get all people AND treasurers under this Amarchal
        $stmt = $pdo->prepare("
            SELECT * FROM people 
            WHERE amarchal = ? 
            ORDER BY 
                CASE WHEN gizbar IS NOT NULL AND gizbar != '' THEN 0 ELSE 1 END,
                gizbar, family_name, first_name
        ");
        $stmt->execute([$selectedName]);
    } else {
        // For Gizbar: get all people under this treasurer
        $stmt = $pdo->prepare("
            SELECT * FROM people 
            WHERE gizbar = ? 
            ORDER BY family_name, first_name
        ");
        $stmt->execute([$selectedName]);
    }
    
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($people)) {
        continue;
    }

    $reports[] = [
        'name' => $selectedName,
        'people' => $people,
    ];
    
    // Create sheet for this item (only if creating Excel output)
    if ($needsSpreadsheet) {
        $sheet = $spreadsheet->createSheet();
        $sheetTitle = mb_substr($selectedName, 0, 31); // Excel sheet name limit
        $sheet->setTitle($sheetTitle);
        $sheet->setRightToLeft(true);
    
        // Set up headers
        $row = 1;
        
        // בס"ד at top right
        $lastCol = $reportType === 'amarchal' ? 'G' : 'F';
        $sheet->setCellValue($lastCol . $row, 'בס"ד');
        $sheet->getStyle($lastCol . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle($lastCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;
        $row++; // Empty row
        
        // Title
        $titleText = $reportType === 'amarchal' 
            ? "דוח אמרכל: $selectedName" 
            : "דוח גזבר: $selectedName";
        $sheet->setCellValue('A' . $row, $titleText);
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4A90E2');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;
        $row++; // Empty row
        
        // Column headers
        if ($reportType === 'amarchal') {
            $headers = ['גזבר', 'משפחה', 'שם', 'נייד בעל', 'כתובת', 'קומה', 'עיר'];
            $sheet->fromArray($headers, null, 'A' . $row);
        } else {
            $headers = ['משפחה', 'שם', 'נייד בעל', 'כתובת', 'קומה', 'עיר'];
            $sheet->fromArray($headers, null, 'A' . $row);
        }
        
        // Style headers
        $headerRange = 'A' . $row . ':' . ($reportType === 'amarchal' ? 'G' : 'F') . $row;
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2C3E50');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;
        
        // Data rows
        $startDataRow = $row;
        $isOddRow = true;
        foreach ($people as $person) {
            if ($reportType === 'amarchal') {
                $rowData = [
                    $person['gizbar'] ?? '',
                    $person['family_name'] ?? '',
                    $person['first_name'] ?? '',
                    $person['husband_mobile'] ?? '',
                    $person['address'] ?? '',
                    $person['floor'] ?? '',
                    $person['city'] ?? ''
                ];
            } else {
                $rowData = [
                    $person['family_name'] ?? '',
                    $person['first_name'] ?? '',
                    $person['husband_mobile'] ?? '',
                    $person['address'] ?? '',
                    $person['floor'] ?? '',
                    $person['city'] ?? ''
                ];
            }
            
            $sheet->fromArray($rowData, null, 'A' . $row);
            
            // Alternating row colors
            $rowRange = 'A' . $row . ':' . ($reportType === 'amarchal' ? 'G' : 'F') . $row;
            if (!$isOddRow) {
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8F9FA');
            }
            $sheet->getStyle($rowRange)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($row)->setRowHeight(20);
            
            $isOddRow = !$isOddRow;
            $row++;
        }
        
        // Style data rows with borders
        if ($row > $startDataRow) {
            $dataRange = 'A' . $startDataRow . ':' . ($reportType === 'amarchal' ? 'G' : 'F') . ($row - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('CCCCCC');
        }
        
        // Add summary
        $row++;
        $totalPeople = count($people);
        if ($reportType === 'amarchal') {
            // Count unique treasurers
            $uniqueGizbarim = array_unique(array_filter(array_column($people, 'gizbar')));
            $totalGizbarim = count($uniqueGizbarim);
            
            $sheet->setCellValue('A' . $row, "סה\"כ גזברים: $totalGizbarim");
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E8F4F8');
            $sheet->getStyle('A' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            $sheet->setCellValue('A' . $row, "סה\"כ קופות: $totalPeople");
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E8F4F8');
            $sheet->getStyle('A' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        } else {
            $sheet->setCellValue('A' . $row, "סה\"כ קופות: $totalPeople");
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E8F4F8');
            $sheet->getStyle('A' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
        
        // Set column widths based on content
        $lastCol = $reportType === 'amarchal' ? 'G' : 'F';
        
        // Auto-size all columns first
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set minimum widths for readability
        if ($reportType === 'amarchal') {
            $sheet->getColumnDimension('A')->setWidth(20); // גזבר
            $sheet->getColumnDimension('B')->setWidth(15); // משפחה
            $sheet->getColumnDimension('C')->setWidth(15); // שם
            $sheet->getColumnDimension('D')->setWidth(15); // נייד בעל
            $sheet->getColumnDimension('E')->setWidth(30); // כתובת
            $sheet->getColumnDimension('F')->setWidth(8);  // קומה
            $sheet->getColumnDimension('G')->setWidth(15); // עיר
        } else {
            $sheet->getColumnDimension('A')->setWidth(15); // משפחה
            $sheet->getColumnDimension('B')->setWidth(15); // שם
            $sheet->getColumnDimension('C')->setWidth(15); // נייד בעל
            $sheet->getColumnDimension('D')->setWidth(30); // כתובת
            $sheet->getColumnDimension('E')->setWidth(8);  // קומה
            $sheet->getColumnDimension('F')->setWidth(15); // עיר
        }
    } // End of if ($needsSpreadsheet)
}

// Remove default sheet if exists (only if we created spreadsheet)
if ($needsSpreadsheet && $spreadsheet && $spreadsheet->getSheetCount() > count($selected)) {
    try {
        $spreadsheet->removeSheetByIndex(0);
    } catch (Exception $e) {
        // Ignore if already removed
    }
}

// Set first sheet as active (only if we created spreadsheet)
if ($needsSpreadsheet && $spreadsheet && $spreadsheet->getSheetCount() > 0) {
    $spreadsheet->setActiveSheetIndex(0);
}

// Generate filename
$reportTypeHe = $reportType === 'amarchal' ? 'אמרכלים' : 'גזברים';
$baseFilename = 'דוח_' . $reportTypeHe . '_' . date('Y-m-d_H-i-s');
if (!empty($partLabel)) {
    $baseFilename .= '_' . $partLabel;
}
$filename = $baseFilename . '.pdf';

// Check if mPDF library is available (only relevant if we need PDF output)
if (!class_exists('\Mpdf\Mpdf')) {
    // If mPDF is not available, export as Excel instead (only if we have spreadsheet data)
    if ($needsSpreadsheet && $spreadsheet) {
        $filename = str_replace('.pdf', '.xlsx', $filename);
        
        if (ob_get_length()) { 
            ob_end_clean(); 
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    } else {
        // Cannot create PDF without mPDF library
        die('שגיאה: ספריית mPDF אינה זמינה. לא ניתן ליצור PDF.');
    }
}

// Create PDF
try {
    if (ob_get_length()) { 
        ob_end_clean(); 
    }

    $backgroundImage = __DIR__ . '/../assets/images/blank_template.png';
    $hasBackground = is_file($backgroundImage) && @getimagesize($backgroundImage);
    $bgCss = '';
    if ($hasBackground) {
        $bgUrl = 'file:///' . str_replace('\\', '/', $backgroundImage);
        $bgCss = "background-image: url('{$bgUrl}'); background-image-resize: 6; background-repeat: no-repeat; background-position: center;";
    }

    if ($outputType === 'labels') {
        error_log("Print PDF Labels: Creating mPDF instance for labels");
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [210, 296.9],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        $mpdf->SetDirectionality('rtl');

        error_log("Print PDF Labels: mPDF instance created");

        $labelsCss = "@page { margin: 15mm 0; }\n" .
            "body { direction: rtl; font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #111; margin: 0; }\n" .
            ".labels-page { width: 210mm; height: 266.9mm; }\n" .
            ".labels-table { width: 210mm; height: 266.9mm; border-collapse: collapse; table-layout: fixed; direction: rtl; }\n" .
            ".labels-table td { width: 70mm; height: 38mm; box-sizing: border-box; padding: 2mm; overflow: hidden; vertical-align: middle; text-align: center; }\n" .
            ".label-inner { width: 100%; }\n" .
            ".line { display: block; margin: 0 0 8mm; line-height: 2; }\n" .
            ".line strong { font-weight: bold; }\n" .
            ".page-break { page-break-after: always; }\n";

        $labelsByGroup = [];
        $monthsToPrint = !empty($months) ? $months : [''];
        if ($reportType === 'amarchal') {
            foreach ($reports as $report) {
                $byGizbar = [];
                foreach ($report['people'] as $person) {
                    $gizbarName = trim((string)($person['gizbar'] ?? ''));
                    if ($gizbarName === '') {
                        $gizbarName = 'ללא גזבר';
                    }
                    $byGizbar[$gizbarName][] = $person;
                }

                foreach ($byGizbar as $gizbarName => $peopleList) {
                    foreach ($monthsToPrint as $month) {
                        foreach ($peopleList as $person) {
                            $labelsByGroup[$gizbarName][] = [
                                'family' => $person['family_name'] ?? '',
                                'name' => $person['first_name'] ?? '',
                                'address' => $person['address'] ?? '',
                                'floor' => $person['floor'] ?? '',
                                'city' => $person['city'] ?? '',
                                'mobile' => $person['husband_mobile'] ?? '',
                                'month' => $month,
                                'gizbar' => $gizbarName,
                                'is_bag' => false,
                            ];
                        }

                        $labelsByGroup[$gizbarName][] = [
                            'family' =>  'שקית מרוכזת',
                            'name' => '',
                            'address' => '',
                            'floor' => '',
                            'city' => '',
                            'mobile' => '',
                            'month' => $month,
                            'gizbar' => $gizbarName,
                            'is_bag' => true,
                        ];
                    }
                }
            }
        } else {
            foreach ($reports as $report) {
                $gizbarName = $report['name'] ?? '';
                foreach ($monthsToPrint as $month) {
                    foreach ($report['people'] as $person) {
                        $labelsByGroup[$gizbarName][] = [
                            'family' => $person['family_name'] ?? '',
                            'name' => $person['first_name'] ?? '',
                            'address' => $person['address'] ?? '',
                            'floor' => $person['floor'] ?? '',
                            'city' => $person['city'] ?? '',
                            'mobile' => $person['husband_mobile'] ?? '',
                            'month' => $month,
                            'gizbar' => $gizbarName,
                            'is_bag' => false,
                        ];
                    }

                    $labelsByGroup[$gizbarName][] = [
                        'family' =>  'שקית מרוכזת',
                        'name' => '',
                        'address' => '',
                        'floor' => '',
                        'city' => '',
                        'mobile' => '',
                        'month' => $month,
                        'gizbar' => $gizbarName,
                        'is_bag' => true,
                    ];
                }
            }
        }

        error_log("Print PDF Labels: Building HTML for " . count($labelsByGroup) . " groups, total labels ≈ $estimatedLabels");
        
        // Split large jobs into chunks to avoid memory issues
        $maxLabelsPerPdf = 500; // Maximum labels in single PDF
        $totalEstimatedLabels = array_sum(array_map('count', $labelsByGroup));
        
        if ($totalEstimatedLabels > $maxLabelsPerPdf) {
            error_log("WARNING: Total labels ($totalEstimatedLabels) exceeds max per PDF ($maxLabelsPerPdf)");
            // Note: For now we'll try anyway with increased limits, but log the warning
        }
        
        // Write CSS first
        try {
            $mpdf->WriteHTML($labelsCss, \Mpdf\HTMLParserMode::HEADER_CSS);
        
            error_log("Print PDF Labels: Writing HTML in chunks to avoid pcre.backtrack_limit");
        
            $perPage = 21;
            $groupKeys = array_keys($labelsByGroup);
            $groupCount = count($groupKeys);
        
            // Process each group and write to mPDF incrementally
            foreach ($groupKeys as $groupIndex => $groupKey) {
            $groupLabels = $labelsByGroup[$groupKey];
            $totalLabels = count($groupLabels);
            
            for ($i = 0; $i < $totalLabels; $i += $perPage) {
                $slice = array_slice($groupLabels, $i, $perPage);
                
                // Build HTML for this page only
                $pageHtml = '<div class="labels-page">';
                $pageHtml .= '<table class="labels-table">';

                for ($r = 0; $r < 7; $r++) {
                    $pageHtml .= '<tr>';
                    for ($c = 0; $c < 3; $c++) {
                        $index = $r * 3 + $c;
                        if (isset($slice[$index])) {
                            $label = $slice[$index];
                            $isBag = !empty($label['is_bag']);
                            if ($isBag) {
                                $lines = [
                                    htmlspecialchars($label['family'], ENT_QUOTES, 'UTF-8'),
                                    '<strong>' . htmlspecialchars($label['gizbar'], ENT_QUOTES, 'UTF-8') . '</strong> - <strong>' . htmlspecialchars($label['month'], ENT_QUOTES, 'UTF-8') . '</strong>'
                                ];
                            } else {
                                $lines = [
                                    htmlspecialchars($label['family'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($label['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($label['address'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($label['city'], ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars($label['mobile'], ENT_QUOTES, 'UTF-8'),
                                    '<strong>' . htmlspecialchars($label['gizbar'], ENT_QUOTES, 'UTF-8') . '</strong> - <strong>' . htmlspecialchars($label['month'], ENT_QUOTES, 'UTF-8') . '</strong>'
                                ];
                            }
                            $lineDivs = array_map(function ($line) {
                                return '<div style="display:block; padding-bottom: 10px; line-height:1.6;">' . $line . '</div>';
                            }, $lines);
                            $cellHtml = '<div class="label-inner">' . implode('', $lineDivs) . '</div>';
                            $pageHtml .= '<td>' . $cellHtml . '</td>';
                        } else {
                            $pageHtml .= '<td></td>';
                        }
                    }
                    $pageHtml .= '</tr>';
                }

                $pageHtml .= '</table>';
                $pageHtml .= '</div>';
                
                if ($i + $perPage < $totalLabels) {
                    $pageHtml .= '<div class="page-break"></div>';
                }
                
                // Write this page to mPDF immediately
                $mpdf->WriteHTML($pageHtml, \Mpdf\HTMLParserMode::HTML_BODY);
                
                // Free memory
                unset($pageHtml);
            }

            if ($groupIndex < $groupCount - 1) {
                $mpdf->WriteHTML('<div class="page-break"></div>', \Mpdf\HTMLParserMode::HTML_BODY);
            }
            
            // Free memory after each group
            unset($labelsByGroup[$groupKey]);
            if ($groupIndex % 5 === 0) {
                gc_collect_cycles();
            }
        }
        
        // Free all remaining memory
        unset($labelsByGroup);
        gc_collect_cycles();

        error_log("Print PDF Labels: mPDF processing complete, outputting...");

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
            error_log("Print PDF Labels: Output complete");
            exit;
            
        } catch (Exception $e) {
            error_log("Print PDF Labels Error: " . $e->getMessage());
            
            if (ob_get_length()) { 
                ob_end_clean(); 
            }
            
            http_response_code(500);
            echo "<!DOCTYPE html><html dir='rtl'><head><meta charset='utf-8'><title>שגיאה</title></head><body>";
            echo "<h2>שגיאה ביצירת PDF מדבקות</h2>";
            echo "<p><strong>סיבה:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
            echo "<h3>פתרונות אפשריים:</h3>";
            echo "<ul>";
            echo "<li>בחר פחות גזברים בכל פעם (עד 30-40)</li>";
            echo "<li>בחר חודש אחד בכל פעם במקום כמה חודשים</li>";
            echo "<li>פצל את ההדפסה למספר קבצי PDF נפרדים</li>";
            echo "</ul>";
            echo "<p><a href='people.php'>חזור לדף אנשים</a></p>";
            echo "</body></html>";
            exit;
        }
    }

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
    ]);
    $mpdf->SetDirectionality('rtl');

    $css = "@page :first { margin-top: 12mm; margin-right: 12mm; margin-bottom: 12mm; margin-left: 12mm; {$bgCss} }\n" .
        "@page { margin-top: 21mm; margin-right: 12mm; margin-bottom: 12mm; margin-left: 12mm; {$bgCss} }\n" .
        "body { direction: rtl; font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }\n" .
        ".page { width: 100%; padding-top: 12mm; }\n" .
        ".basad { text-align: right; font-weight: bold; font-size: 14pt; color: #111; margin-top: 8mm; }\n" .
        ".title { background: #222; color: #fff; text-align: center; font-weight: bold; font-size: 18pt; padding: 8px; border-radius: 4px; margin: 6px 0 8px; }\n" .
        ".info-bar { border: 1px solid #666; background: #f2f2f2; border-radius: 6px; padding: 6px 10px; margin: 6px 0 12px; display: flex; justify-content: space-between; gap: 12px; align-items: center; }\n" .
        ".info-group { display: flex; flex-wrap: wrap; gap: 10px; }\n" .
        ".info-item { display: inline-block; font-weight: bold; color: #222; }\n" .
        ".info-item span { font-weight: normal; color: #333; }\n" .
        ".info-item .highlight { font-weight: bold; color: #000; }\n" .
        ".table-wrap { margin-top: 6mm; }\n" .
        ".page-footer { position: fixed; bottom: 6mm; left: 0; right: 0; text-align: center; font-size: 10pt; color: #333; }\n" .
        "table { width: 100%; border-collapse: collapse; }\n" .
        "th, td { border: 1px solid #999; padding: 4px 6px; line-height: 1.15; text-align: right; vertical-align: middle; }\n" .
        "thead th { background: #444; color: #fff; }\n" .
        "tbody tr:nth-child(even) td { background: #f6f6f6; }\n" .
        ".summary { margin-top: 10px; }\n" .
        ".summary .box { background: #ededed; border: 1px solid #999; padding: 6px 8px; font-weight: bold; display: inline-block; margin-left: 6px; }\n" .
        ".page-break { page-break-after: always; }\n";

    $html = '';
    $pages = [];
    foreach ($reports as $report) {
        $selectedName = $report['name'];
        $people = $report['people'];

        if ($reportType === 'amarchal') {
            $pages[] = [
                'title' => "דוח אמרכל: {$selectedName}",
                'people' => $people,
                'gizbar' => '',
                'amarchal' => $selectedName,
            ];
        } else {
            $pages[] = [
                'title' => "דוח גזבר: {$selectedName}",
                'people' => $people,
                'gizbar' => $selectedName,
                'amarchal' => '',
            ];
        }
    }

    $pageCount = count($pages);
    foreach ($pages as $idx => $page) {
        $titleText = $page['title'];
        $people = $page['people'];

        $uniqueGizbarim = array_unique(array_filter(array_column($people, 'gizbar')));
        $uniqueAmarchalim = array_unique(array_filter(array_column($people, 'amarchal')));
        $uniqueNeighborhoods = array_unique(array_filter(array_column($people, 'neighborhood')));

        $monthsLabel = !empty($months) ? implode(' | ', $months) : '—';

        $gizbarLabel = $reportType === 'gizbar'
            ? $page['gizbar']
            : (!empty($page['gizbar']) ? $page['gizbar'] : (!empty($uniqueGizbarim) ? implode(' | ', $uniqueGizbarim) : '—'));
        $amarchalLabel = $reportType === 'amarchal'
            ? $page['amarchal']
            : (!empty($uniqueAmarchalim) ? implode(' | ', $uniqueAmarchalim) : '—');
        $neighborhoodLabel = !empty($uniqueNeighborhoods) ? implode(' | ', $uniqueNeighborhoods) : '—';

        if ($reportType === 'amarchal') {
            $headers = ['גזבר', 'משפחה', 'שם', 'נייד בעל', 'כתובת', 'קומה', 'עיר'];
        } else {
            $headers = ['משפחה', 'שם', 'נייד בעל', 'כתובת', 'קומה', 'עיר'];
        }

        $rowsHtml = '';
        foreach ($people as $person) {
            if ($reportType === 'amarchal') {
                $rowData = [
                    $person['gizbar'] ?? '',
                    $person['family_name'] ?? '',
                    $person['first_name'] ?? '',
                    $person['husband_mobile'] ?? '',
                    $person['address'] ?? '',
                    $person['floor'] ?? '',
                    $person['city'] ?? ''
                ];
            } else {
                $rowData = [
                    $person['family_name'] ?? '',
                    $person['first_name'] ?? '',
                    $person['husband_mobile'] ?? '',
                    $person['address'] ?? '',
                    $person['floor'] ?? '',
                    $person['city'] ?? ''
                ];
            }

            $cells = array_map(function ($v) {
                return '<td>' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '</td>';
            }, $rowData);
            $rowsHtml .= '<tr>' . implode('', $cells) . '</tr>';
        }

        $summaryHtml = '';
        $totalPeople = count($people);
        if ($reportType === 'amarchal') {
            $uniqueGizbarim = array_unique(array_filter(array_column($people, 'gizbar')));
            $totalGizbarim = count($uniqueGizbarim);
            $summaryHtml .= '<span class="box">סה"כ גזברים: ' . $totalGizbarim . '</span>';
            $summaryHtml .= '<span class="box">סה"כ קופות: ' . $totalPeople . '</span>';
        } else {
            $summaryHtml .= '<span class="box">סה"כ קופות: ' . $totalPeople . '</span>';
        }

        $headerCells = array_map(function ($h) {
            return '<th>' . htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') . '</th>';
        }, $headers);

        $html .= '<div class="page">';
        $html .= '<div class="basad">בס"ד</div>';
        $html .= '<div class="title">' . htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') . '</div>';
        if ($reportType === 'amarchal') {
            $html .= '<div class="info-bar">'
                . '<div class="info-group">'
                . '<div class="info-item">גזבר: <span class="highlight">' . htmlspecialchars($gizbarLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '<div class="info-item">חודשי איסוף: <span class="highlight">' . htmlspecialchars($monthsLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '</div>'
                . '<div class="info-group">'
                . '<div class="info-item">ניתן לעדכן סכומים בנדרים פלוס, וכן בטלפון מס: <span>073-3990652</span></div>'
                . '</div>'
                . '</div>';
        } else {
            $html .= '<div class="info-bar">'
                . '<div class="info-group">'
                . '<div class="info-item">גזבר: <span class="highlight">' . htmlspecialchars($gizbarLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '<div class="info-item">אזור איסוף: <span>' . htmlspecialchars($neighborhoodLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '<div class="info-item">אמרכל: <span>' . htmlspecialchars($amarchalLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '<div class="info-item">חודשי איסוף: <span class="highlight">' . htmlspecialchars($monthsLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
                . '</div>'
                . '<div class="info-group">'
                . '<div class="info-item">ניתן לעדכן סכומים בנדרים פלוס, וכן בטלפון מס: <span>073-3990652</span></div>'
                . '</div>'
                . '</div>';
        }
        $html .= '<div class="table-wrap"><table><thead><tr>' . implode('', $headerCells) . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table></div>';
        $html .= '<div class="summary">' . $summaryHtml . '</div>';
        $html .= '</div>';

        if ($idx < $pageCount - 1) {
            $html .= '<div class="page-break"></div>';
        }
    }

    $mpdf->SetHTMLFooter('<div style="text-align:center;font-size:10pt;color:#333;">דף {PAGENO} מתוך {nbpg}</div>');

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);

} catch (Exception $e) {
    error_log("Print PDF Error: " . $e->getMessage());
    
    // If PDF generation fails, fall back to Excel (only if we have spreadsheet data)
    if ($needsSpreadsheet && $spreadsheet) {
        $filename = str_replace('.pdf', '.xlsx', $filename);
        
        if (ob_get_length()) { 
            ob_end_clean(); 
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    } else {
        // Cannot create any output
        if (ob_get_length()) { 
            ob_end_clean(); 
        }
        http_response_code(500);
        echo "שגיאה ביצירת PDF: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

exit;
