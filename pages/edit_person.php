<?php 
require_once '../config/db.php';

if (!isset($_GET['id'])) {
    header('Location: people.php');
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE people SET 
        amarchal = ?, gizbar = ?, software_id = ?, donor_number = ?, chatan_harar = ?,
        family_name = ?, first_name = ?, name_for_mail = ?, full_name = ?, 
        husband_id = ?, wife_id = ?, address = ?, mail_to = ?, neighborhood = ?, 
        floor = ?, city = ?, phone = ?, husband_mobile = ?, wife_name = ?, 
        wife_mobile = ?, updated_email = ?, husband_email = ?, wife_email = ?, 
        receipts_to = ?, alphon = ?, send_messages = ?
        WHERE id = ?");
    
    $stmt->execute([
        $_POST['amarchal'] ?? '', $_POST['gizbar'] ?? '', $_POST['software_id'] ?? '',
        $_POST['donor_number'] ?? '', $_POST['chatan_harar'] ?? '', $_POST['family_name'],
        $_POST['first_name'], $_POST['name_for_mail'] ?? '', $_POST['full_name'] ?? '',
        $_POST['husband_id'] ?? '', $_POST['wife_id'] ?? '', $_POST['address'] ?? '',
        $_POST['mail_to'] ?? '', $_POST['neighborhood'] ?? '', $_POST['floor'] ?? '',
        $_POST['city'] ?? '', $_POST['phone'] ?? '', $_POST['husband_mobile'] ?? '',
        $_POST['wife_name'] ?? '', $_POST['wife_mobile'] ?? '', $_POST['updated_email'] ?? '',
        $_POST['husband_email'] ?? '', $_POST['wife_email'] ?? '', $_POST['receipts_to'] ?? '',
        $_POST['alphon'] ?? '', $_POST['send_messages'] ?? '', $id
    ]);
    
    header('Location: people.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM people WHERE id = ?");
$stmt->execute([$id]);
$person = $stmt->fetch();

if (!$person) {
    header('Location: people.php');
    exit;
}

include '../templates/header.php'; 
?>
<h2>ערוך איש קשר</h2>
<form action="edit_person.php?id=<?php echo $id; ?>" method="post">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="netgrov_site" class="form-label">אתר נט גרוב</label>
            <input type="text" class="form-control" id="netgrov_site" name="netgrov_site" value="<?php echo htmlspecialchars($person['netgrov_site'] ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="tnuva_id" class="form-label">מזהה תנובה</label>
            <input type="text" class="form-control" id="tnuva_id" name="tnuva_id" value="<?php echo htmlspecialchars($person['tnuva_id'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="file_number" class="form-label">מספר תקים</label>
            <input type="text" class="form-control" id="file_number" name="file_number" value="<?php echo htmlspecialchars($person['file_number'] ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="coordinator_id" class="form-label">תו"ז הרכר</label>
            <input type="text" class="form-control" id="coordinator_id" name="coordinator_id" value="<?php echo htmlspecialchars($person['coordinator_id'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="family_name" class="form-label">משפחה <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="family_name" name="family_name" value="<?php echo htmlspecialchars($person['family_name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="first_name" class="form-label">שם <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($person['first_name'] ?? ''); ?>" required>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="address_name" class="form-label">שם לאדרה</label>
            <input type="text" class="form-control" id="address_name" name="address_name" value="<?php echo htmlspecialchars($person['address_name'] ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="role_name" class="form-label">שם לאדח תפקיד</label>
            <input type="text" class="form-control" id="role_name" name="role_name" value="<?php echo htmlspecialchars($person['role_name'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="husband_id" class="form-label">תעודת זהות בעל</label>
            <input type="text" class="form-control" id="husband_id" name="husband_id" value="<?php echo htmlspecialchars($person['husband_id'] ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="wife_id" class="form-label">תעודת זהות אשה</label>
            <input type="text" class="form-control" id="wife_id" name="wife_id" value="<?php echo htmlspecialchars($person['wife_id'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="address" class="form-label">כתובת</label>
        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($person['address'] ?? ''); ?></textarea>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="po_box" class="form-label">ת דואר</label>
            <input type="text" class="form-control" id="po_box" name="po_box" value="<?php echo htmlspecialchars($person['po_box'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="year_or_other" class="form-label">שנה / אהד</label>
            <input type="text" class="form-control" id="year_or_other" name="year_or_other" value="<?php echo htmlspecialchars($person['year_or_other'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="floor" class="form-label">קומה</label>
            <input type="text" class="form-control" id="floor" name="floor" value="<?php echo htmlspecialchars($person['floor'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="city" class="form-label">עיר</label>
        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($person['city'] ?? ''); ?>">
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="phone" class="form-label">טלפון</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($person['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="husband_mobile" class="form-label">פיד בעל</label>
            <input type="text" class="form-control" id="husband_mobile" name="husband_mobile" value="<?php echo htmlspecialchars($person['husband_mobile'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="home_phone" class="form-label">טלפון בית</label>
            <input type="text" class="form-control" id="home_phone" name="home_phone" value="<?php echo htmlspecialchars($person['home_phone'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="wife_mobile" class="form-label">פיד אשה</label>
        <input type="text" class="form-control" id="wife_mobile" name="wife_mobile" value="<?php echo htmlspecialchars($person['wife_mobile'] ?? ''); ?>">
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="family_email" class="form-label">כתובת נייל משפחתי</label>
            <input type="email" class="form-control" id="family_email" name="family_email" value="<?php echo htmlspecialchars($person['family_email'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="husband_email" class="form-label">נייל בעל</label>
            <input type="email" class="form-control" id="husband_email" name="husband_email" value="<?php echo htmlspecialchars($person['husband_email'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="wife_email" class="form-label">נייל אשה</label>
            <input type="email" class="form-control" id="wife_email" name="wife_email" value="<?php echo htmlspecialchars($person['wife_email'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="receive_option" class="form-label">קבלת?</label>
            <input type="text" class="form-control" id="receive_option" name="receive_option" value="<?php echo htmlspecialchars($person['receive_option'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="location" class="form-label">מקום</label>
            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($person['location'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="mail_delivery" class="form-label">שליחית דואל</label>
            <input type="text" class="form-control" id="mail_delivery" name="mail_delivery" value="<?php echo htmlspecialchars($person['mail_delivery'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="value" class="form-label">שווי אתנון</label>
        <input type="text" class="form-control" id="value" name="value" value="<?php echo htmlspecialchars($person['value'] ?? ''); ?>">
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">עדכן</button>
        <a href="people.php" class="btn btn-secondary">ביטול</a>
    </div>
</form>
<?php include '../templates/footer.php'; ?>