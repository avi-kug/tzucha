<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../config/auth_enhanced.php';
auth_require_login($pdo);
auth_require_permission('people');

// DDoS Protection
try {
    check_api_rate_limit($_SERVER['REMOTE_ADDR'], 60, 60); // 60 requests per minute
    check_request_size(); // Max 10MB
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Get the action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$mutatingActions = ['add', 'update_full', 'update', 'delete', 'delete_bulk'];
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
            // Get all people
            $stmt = $pdo->query("SELECT * FROM people ORDER BY family_name, first_name");
            $people = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $people]);
            break;
        
        case 'get_one':
            // Get one person
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM people WHERE id = ?");
            $stmt->execute([$id]);
            $person = $stmt->fetch();
            
            if (!$person) {
                throw new Exception('Person not found');
            }
            
            echo json_encode(['success' => true, 'data' => $person]);
            break;
        
        case 'add':
            // Add a new person
            // Prevent duplicate submissions
            check_idempotency($pdo, 'add_person', [
                'full_name' => $_POST['full_name'] ?? '',
                'husband_id' => $_POST['husband_id'] ?? '',
                'wife_id' => $_POST['wife_id'] ?? ''
            ], 60); // 60 seconds window
            
            $stmt = $pdo->prepare("INSERT INTO people (
                amarchal, gizbar, software_id, donor_number, chatan_harar, family_name, first_name, 
                name_for_mail, full_name, husband_id, wife_id, phone_id, address, mail_to, neighborhood, 
                floor, city, phone, husband_mobile, wife_name, wife_mobile, updated_email, 
                husband_email, wife_email, receipts_to, alphon, send_messages
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_POST['amarchal'] ?? '', $_POST['gizbar'] ?? '', $_POST['software_id'] ?? '',
                $_POST['donor_number'] ?? '', $_POST['chatan_harar'] ?? '', $_POST['family_name'] ?? '',
                $_POST['first_name'] ?? '', $_POST['name_for_mail'] ?? '', $_POST['full_name'] ?? '',
                $_POST['husband_id'] ?? '', $_POST['wife_id'] ?? '', $_POST['phone_id'] ?? '',
                $_POST['address'] ?? '', $_POST['mail_to'] ?? '', $_POST['neighborhood'] ?? '',
                $_POST['floor'] ?? '', $_POST['city'] ?? '', $_POST['phone'] ?? '',
                $_POST['husband_mobile'] ?? '', $_POST['wife_name'] ?? '', $_POST['wife_mobile'] ?? '',
                $_POST['updated_email'] ?? '', $_POST['husband_email'] ?? '', $_POST['wife_email'] ?? '',
                $_POST['receipts_to'] ?? '', $_POST['alphon'] ?? '', $_POST['send_messages'] ?? ''
            ]);
            
            $newId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Added successfully', 'id' => $newId]);
            break;
        
        case 'update_full':
            // Update full person record
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $stmt = $pdo->prepare("UPDATE people SET 
                amarchal = ?, gizbar = ?, software_id = ?, donor_number = ?, chatan_harar = ?,
                family_name = ?, first_name = ?, name_for_mail = ?, full_name = ?, 
                husband_id = ?, wife_id = ?, phone_id = ?, address = ?, mail_to = ?, neighborhood = ?, 
                floor = ?, city = ?, phone = ?, husband_mobile = ?, wife_name = ?, 
                wife_mobile = ?, updated_email = ?, husband_email = ?, wife_email = ?, 
                receipts_to = ?, alphon = ?, send_messages = ?
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['amarchal'] ?? '', $_POST['gizbar'] ?? '', $_POST['software_id'] ?? '',
                $_POST['donor_number'] ?? '', $_POST['chatan_harar'] ?? '', $_POST['family_name'] ?? '',
                $_POST['first_name'] ?? '', $_POST['name_for_mail'] ?? '', $_POST['full_name'] ?? '',
                $_POST['husband_id'] ?? '', $_POST['wife_id'] ?? '', $_POST['phone_id'] ?? '',
                $_POST['address'] ?? '', $_POST['mail_to'] ?? '', $_POST['neighborhood'] ?? '',
                $_POST['floor'] ?? '', $_POST['city'] ?? '', $_POST['phone'] ?? '',
                $_POST['husband_mobile'] ?? '', $_POST['wife_name'] ?? '', $_POST['wife_mobile'] ?? '',
                $_POST['updated_email'] ?? '', $_POST['husband_email'] ?? '', $_POST['wife_email'] ?? '',
                $_POST['receipts_to'] ?? '', $_POST['alphon'] ?? '', $_POST['send_messages'] ?? '', $id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Updated successfully']);
            break;
            
        case 'update':
            // Update a person
            $id = $_POST['id'] ?? 0;
            $field = $_POST['field'] ?? '';
            $value = $_POST['value'] ?? '';
            
            if (!$id || !$field) {
                throw new Exception('Missing required parameters');
            }
            
            // Allowed fields for update
            $allowedFields = [
                'amarchal', 'gizbar', 'software_id', 'donor_number', 'chatan_harar',
                'family_name', 'first_name', 'name_for_mail', 'full_name',
                'husband_id', 'wife_id', 'phone_id', 'address', 'mail_to', 'neighborhood',
                'floor', 'city', 'phone', 'husband_mobile', 'wife_name',
                'wife_mobile', 'updated_email', 'husband_email', 'wife_email',
                'receipts_to', 'alphon', 'send_messages'
            ];
            
            if (!in_array($field, $allowedFields)) {
                throw new Exception('Invalid field');
            }
            
            $stmt = $pdo->prepare("UPDATE people SET $field = ? WHERE id = ?");
            $stmt->execute([$value, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Updated successfully']);
            break;
            
        case 'delete':
            // Prevent accidental double-delete
            check_idempotency($pdo, 'delete_person', ['id' => $id], 10); // 10 seconds window
            
            // Delete a person
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('Missing ID');
            }
            
            $stmt = $pdo->prepare("DELETE FROM people WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
            break;

        case 'delete_bulk':
            $ids = $_POST['ids'] ?? [];
            if (is_string($ids)) {
            // Prevent accidental bulk deletion
            check_idempotency($pdo, 'delete_bulk_people', ['ids' => $ids], 10); // 10 seconds window
            
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                }
            }

            if (!is_array($ids) || count($ids) === 0) {
                throw new Exception('Missing IDs');
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM people WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
