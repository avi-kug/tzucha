<?php
/**
 * person_details_api.php
 * Retrieves all information about a person from multiple tables
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$software_id = $_GET['software_id'] ?? null;
$person_id = $_GET['person_id'] ?? null;

if (!$software_id && !$person_id) {
    http_response_code(400);
    echo json_encode(['error' => 'חסר מזהה']);
    exit;
}

try {
    $result = [];
    
    // Get person basic info from people table by software_id
    if ($software_id) {
        $stmt = $pdo->prepare("SELECT * FROM people WHERE software_id = ?");
        $stmt->execute([$software_id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['person'] = $person;
        // Use the actual people.id for other queries
        if ($person) {
            $person_id = $person['id'];
        }
    } else {
        // Fallback to person_id if software_id not provided
        $stmt = $pdo->prepare("SELECT * FROM people WHERE id = ?");
        $stmt->execute([$person_id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['person'] = $person;
        if ($person) {
            $software_id = $person['software_id'];
        }
    }
    
    // Get cash donations using software_id (matches id_alfon in cash_donations)
    if ($software_id) {
        $stmt = $pdo->prepare("
            SELECT project, SUM(amount) as total, COUNT(*) as count,
                   GROUP_CONCAT(CONCAT(date, ': ', amount, ' ש\"ח') ORDER BY date DESC SEPARATOR '; ') as details
            FROM cash_donations 
            WHERE id_alfon = ? 
            GROUP BY project
        ");
        $stmt->execute([$software_id]);
        $result['cash_donations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all cash donation records
        $stmt = $pdo->prepare("
            SELECT * FROM cash_donations 
            WHERE id_alfon = ? 
            ORDER BY date DESC
        ");
        $stmt->execute([$software_id]);
        $result['cash_donations_all'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get standing orders using person_id (matches people.id)
    if ($person_id) {
        $stmt = $pdo->prepare("SELECT * FROM standing_orders_koach WHERE person_id = ?");
        $stmt->execute([$person_id]);
        $result['standing_orders_koach'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM standing_orders_achim WHERE person_id = ?");
        $stmt->execute([$person_id]);
        $result['standing_orders_achim'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get supports using person_id (matches people.id)
        $stmt = $pdo->prepare("SELECT * FROM supports WHERE person_id = ?");
        $stmt->execute([$person_id]);
        $result['supports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'שגיאה בשליפת נתונים: ' . $e->getMessage()]);
}
