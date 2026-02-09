<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';

$stmt = $pdo->query("SELECT id, full_name, address, city, phone, husband_mobile, wife_name, wife_mobile, updated_email, husband_email, wife_email, alphon, send_messages FROM people ORDER BY full_name");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>

<link rel="stylesheet" href="../assets/css/alphon.css">

<h2 class="page-title text-end">אלפון</h2>
<div class="card fixed-card">
    <div class="card-body">
        <div class="table-scroll">
            <table id="alphonTable" class="table table-striped mb-0" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>שם ומשפחה ביחד</th>
                        <th>כתובת</th>
                        <th>עיר</th>
                        <th>טלפון</th>
                        <th>נייד בעל</th>
                        <th>שם האשה</th>
                        <th>נייד אשה</th>
                        <th>כתובת מייל מעודכן</th>
                        <th>מייל בעל</th>
                        <th>מייל אשה</th>
                        <th>אלפון</th>
                        <th>הודעות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr data-id="<?php echo (int)$row['id']; ?>">
                            <td class="editable" data-field="full_name"><?php echo htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="address"><?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="city"><?php echo htmlspecialchars($row['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="phone"><?php echo htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="husband_mobile"><?php echo htmlspecialchars($row['husband_mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="wife_name"><?php echo htmlspecialchars($row['wife_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="wife_mobile"><?php echo htmlspecialchars($row['wife_mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="updated_email"><?php echo htmlspecialchars($row['updated_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="husband_email"><?php echo htmlspecialchars($row['husband_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="wife_email"><?php echo htmlspecialchars($row['wife_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="alphon"><?php echo htmlspecialchars($row['alphon'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="editable" data-field="send_messages"><?php echo htmlspecialchars($row['send_messages'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../assets/js/alphon.js"></script>

<?php include '../templates/footer.php'; ?><?php
