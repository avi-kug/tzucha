<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../templates/header.php'; 
require_once '../config/db.php';
?>

<style>
.editable {
    cursor: pointer;
    background-color: transparent;
    transition: background-color 0.2s;
}
.editable:hover {
    background-color: #fff3cd !important;
}
.editing {
    background-color: #d1ecf1 !important;
}
.table-wrapper {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<div class="table-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>רשימת אנשים</h2>
        <button class="btn btn-primary" id="addPersonBtn">
            <i class="bi bi-plus-circle"></i> הוסף איש קשר חדש
        </button>
    </div>
    
    <div class="table-responsive">
        <table id="peopleTable" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th>אמרכל</th>
                    <th>גזבר</th>
                    <th>מזהה תוכנה</th>
                    <th>מס תורם</th>
                    <th>חתן הר"ר</th>
                    <th>משפחה</th>
                    <th>שם</th>
                    <th>שם לדואר</th>
                    <th>שם ומשפחה ביחד</th>
                    <th>תעודת זהות בעל</th>
                    <th>תעודת זהות אשה</th>
                    <th>כתובת</th>
                    <th>דואר ל</th>
                    <th>שכונה / אזור</th>
                    <th>קומה</th>
                    <th>עיר</th>
                    <th>טלפון</th>
                    <th>נייד בעל</th>
                    <th>שם האשה</th>
                    <th>נייד אשה</th>
                    <th>כתובת מייל מעודכן</th>
                    <th>מייל בעל</th>
                    <th>מייל אשה</th>
                    <th>קבלות ל</th>
                    <th>אלפון</th>
                    <th>שליחת הודעות</th>
                    <th>שינוי אחרון</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM people ORDER BY family_name, first_name");
                while ($row = $stmt->fetch()) {
                    $id = $row['id'];
                    echo "<tr data-id='$id'>";
                    echo "<td class='editable' data-field='amarchal'>" . htmlspecialchars($row['amarchal'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='gizbar'>" . htmlspecialchars($row['gizbar'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='software_id'>" . htmlspecialchars($row['software_id'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='donor_number'>" . htmlspecialchars($row['donor_number'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='chatan_harar'>" . htmlspecialchars($row['chatan_harar'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='family_name'>" . htmlspecialchars($row['family_name'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='first_name'>" . htmlspecialchars($row['first_name'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='name_for_mail'>" . htmlspecialchars($row['name_for_mail'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='full_name'>" . htmlspecialchars($row['full_name'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='husband_id'>" . htmlspecialchars($row['husband_id'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='wife_id'>" . htmlspecialchars($row['wife_id'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='address'>" . htmlspecialchars($row['address'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='mail_to'>" . htmlspecialchars($row['mail_to'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='neighborhood'>" . htmlspecialchars($row['neighborhood'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='floor'>" . htmlspecialchars($row['floor'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='city'>" . htmlspecialchars($row['city'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='phone'>" . htmlspecialchars($row['phone'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='husband_mobile'>" . htmlspecialchars($row['husband_mobile'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='wife_name'>" . htmlspecialchars($row['wife_name'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='wife_mobile'>" . htmlspecialchars($row['wife_mobile'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='updated_email'>" . htmlspecialchars($row['updated_email'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='husband_email'>" . htmlspecialchars($row['husband_email'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='wife_email'>" . htmlspecialchars($row['wife_email'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='receipts_to'>" . htmlspecialchars($row['receipts_to'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='alphon'>" . htmlspecialchars($row['alphon'] ?? '') . "</td>";
                    echo "<td class='editable' data-field='send_messages'>" . htmlspecialchars($row['send_messages'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_change'] ?? '') . "</td>";
                    echo "<td class='text-center'>";
                    echo "<button class='btn btn-sm btn-warning edit-btn me-1' data-id='$id' title='ערוך'><i class='bi bi-pencil'></i></button>";
                    echo "<button class='btn btn-sm btn-danger delete-btn' data-id='$id' title='מחק'><i class='bi bi-trash'></i></button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Add/Edit Person -->
<div class="modal fade" id="personModal" tabindex="-1" aria-labelledby="personModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personModalLabel">הוסף איש קשר</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="personForm">
                    <input type="hidden" id="person_id" name="id">
                    
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="savePersonBtn">שמור</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const modal = new bootstrap.Modal(document.getElementById('personModal'));
    let isEditMode = false;
    
    // Initialize DataTable
    const table = $('#peopleTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json',
            search: 'חיפוש:',
            lengthMenu: 'הצג _MENU_ רשומות',
            info: 'מציג _START_ עד _END_ מתוך _TOTAL_ רשומות',
            infoEmpty: 'אין רשומות להצגה',
            infoFiltered: '(מסונן מתוך _MAX_ רשומות)',
            paginate: {
                first: 'ראשון',
                last: 'אחרון',
                next: 'הבא',
                previous: 'הקודם'
            }
        },
        pageLength: 25,
        responsive: true,
        order: [[5, 'asc']], // Sort by family name
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on actions column
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
    
    // Add Person Button
    $('#addPersonBtn').on('click', function() {
        isEditMode = false;
        $('#personModalLabel').text('הוסף איש קשר חדש');
        $('#personForm')[0].reset();
        $('#person_id').val('');
        modal.show();
    });
    
    // Edit Person Button
    $('#peopleTable').on('click', '.edit-btn', function() {
        isEditMode = true;
        const id = $(this).data('id');
        $('#personModalLabel').text('ערוך איש קשר');
        
        // Fetch person data
        $.ajax({
            url: 'people_api.php?action=get_one&id=' + id,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const person = response.data;
                    $('#person_id').val(person.id);
                    $('#amarchal').val(person.amarchal || '');
                    $('#gizbar').val(person.gizbar || '');
                    $('#software_id').val(person.software_id || '');
                    $('#donor_number').val(person.donor_number || '');
                    $('#chatan_harar').val(person.chatan_harar || '');
                    $('#family_name').val(person.family_name || '');
                    $('#first_name').val(person.first_name || '');
                    $('#name_for_mail').val(person.name_for_mail || '');
                    $('#full_name').val(person.full_name || '');
                    $('#husband_id').val(person.husband_id || '');
                    $('#wife_id').val(person.wife_id || '');
                    $('#address').val(person.address || '');
                    $('#mail_to').val(person.mail_to || '');
                    $('#neighborhood').val(person.neighborhood || '');
                    $('#floor').val(person.floor || '');
                    $('#city').val(person.city || '');
                    $('#phone').val(person.phone || '');
                    $('#husband_mobile').val(person.husband_mobile || '');
                    $('#wife_name').val(person.wife_name || '');
                    $('#wife_mobile').val(person.wife_mobile || '');
                    $('#updated_email').val(person.updated_email || '');
                    $('#husband_email').val(person.husband_email || '');
                    $('#wife_email').val(person.wife_email || '');
                    $('#receipts_to').val(person.receipts_to || '');
                    $('#alphon').val(person.alphon || '');
                    $('#send_messages').val(person.send_messages || '');
                    
                    modal.show();
                } else {
                    alert('שגיאה בטעינת הנתונים');
                }
            },
            error: function() {
                alert('שגיאה בטעינת הנתונים');
            },
            dataType: 'json'
        });
    });
    
    // Save Person
    $('#savePersonBtn').on('click', function() {
        const formData = $('#personForm').serialize();
        const action = isEditMode ? 'update_full' : 'add';
        
        $.ajax({
            url: 'people_api.php',
            method: 'POST',
            data: formData + '&action=' + action,
            success: function(response) {
                if (response.success) {
                    modal.hide();
                    location.reload(); // Reload to refresh the table
                } else {
                    alert('שגיאה בשמירה: ' + response.error);
                }
            },
            error: function() {
                alert('שגיאה בשמירה');
            },
            dataType: 'json'
        });
    });
    
    // Inline editing
    let originalValue = '';
    let currentCell = null;
    
    $('#peopleTable').on('click', 'td.editable', function() {
        if (currentCell) {
            // Save previous edit first
            saveEdit();
        }
        
        currentCell = $(this);
        originalValue = currentCell.text();
        
        const input = $('<input type="text" class="form-control form-control-sm">')
            .val(originalValue)
            .css({
                'width': '100%',
                'box-sizing': 'border-box'
            });
        
        currentCell.html(input).addClass('editing');
        input.focus().select();
        
        // Save on Enter or blur
        input.on('blur', saveEdit);
        input.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                saveEdit();
            }
        });
        input.on('keydown', function(e) {
            if (e.which === 27) { // Escape key
                cancelEdit();
            }
        });
    });
    
    function saveEdit() {
        if (!currentCell) return;
        
        const input = currentCell.find('input');
        if (!input.length) return;
        
        const newValue = input.val();
        const field = currentCell.data('field');
        const id = currentCell.closest('tr').data('id');
        
        if (newValue !== originalValue) {
            // Send AJAX request to update
            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: {
                    action: 'update',
                    id: id,
                    field: field,
                    value: newValue
                },
                success: function(response) {
                    if (response.success) {
                        currentCell.text(newValue).removeClass('editing');
                        currentCell.css('background-color', '#d4edda');
                        setTimeout(() => {
                            currentCell.css('background-color', '');
                        }, 1000);
                    } else {
                        alert('שגיאה בעדכון: ' + response.error);
                        currentCell.text(originalValue).removeClass('editing');
                    }
                },
                error: function() {
                    alert('שגיאה בעדכון');
                    currentCell.text(originalValue).removeClass('editing');
                },
                dataType: 'json'
            });
        } else {
            currentCell.text(originalValue).removeClass('editing');
        }
        
        currentCell = null;
    }
    
    function cancelEdit() {
        if (currentCell) {
            currentCell.text(originalValue).removeClass('editing');
            currentCell = null;
        }
    }
    
    // Delete functionality
    $('#peopleTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        if (confirm('האם אתה בטוח שברצונך למחוק רשומה זו?')) {
            $.ajax({
                url: 'people_api.php',
                method: 'POST',
                data: {
                    action: 'delete',
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        table.row(row).remove().draw();
                    } else {
                        alert('שגיאה במחיקה: ' + response.error);
                    }
                },
                error: function() {
                    alert('שגיאה במחיקה');
                },
                dataType: 'json'
            });
        }
    });
});
</script>

<?php include '../templates/footer.php'; ?>