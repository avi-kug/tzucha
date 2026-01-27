<?php 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/db.php';
    
    $stmt = $pdo->prepare("INSERT INTO people (
        amarchal, gizbar, software_id, donor_number, chatan_harar, family_name, first_name, 
        name_for_mail, full_name, husband_id, wife_id, address, mail_to, neighborhood, 
        floor, city, phone, husband_mobile, wife_name, wife_mobile, updated_email, 
        husband_email, wife_email, receipts_to, alphon, send_messages
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['amarchal'] ?? '', $_POST['gizbar'] ?? '', $_POST['software_id'] ?? '',
        $_POST['donor_number'] ?? '', $_POST['chatan_harar'] ?? '', $_POST['family_name'],
        $_POST['first_name'], $_POST['name_for_mail'] ?? '', $_POST['full_name'] ?? '',
        $_POST['husband_id'] ?? '', $_POST['wife_id'] ?? '', $_POST['address'] ?? '',
        $_POST['mail_to'] ?? '', $_POST['neighborhood'] ?? '', $_POST['floor'] ?? '',
        $_POST['city'] ?? '', $_POST['phone'] ?? '', $_POST['husband_mobile'] ?? '',
        $_POST['wife_name'] ?? '', $_POST['wife_mobile'] ?? '', $_POST['updated_email'] ?? '',
        $_POST['husband_email'] ?? '', $_POST['wife_email'] ?? '', $_POST['receipts_to'] ?? '',
        $_POST['alphon'] ?? '', $_POST['send_messages'] ?? ''
    ]);
    
    header('Location: people.php');
    exit;
}

include '../templates/header.php'; 
?>
<h2>הוסף איש קשר חדש</h2>
<form action="add_person.php" method="post">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="amarchal" class="form-label">אמרכל</label>
            <input type="text" class="form-control" id="amarchal" name="amarchal">
        </div>
        <div class="col-md-4 mb-3">
            <label for="gizbar" class="form-label">גזבר</label>
            <input type="text" class="form-control" id="gizbar" name="gizbar">
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
            <label for="chatan_harar" class="form-label">חתן הר"ר</label>
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
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">שמור</button>
        <a href="people.php" class="btn btn-secondary">ביטול</a>
    </div>
</form>
<?php include '../templates/footer.php'; ?>