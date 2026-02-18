<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';
session_start();
require_once '../config/auth.php';
auth_require_login($pdo);
auth_require_permission('beit_neeman');

$canEdit = auth_role() !== 'viewer';

include '../templates/header.php';
?>

<link rel="stylesheet" href="../assets/css/children.css">

<h2 class="page-title text-end">בית נאמן</h2>

<div class="table-loader" id="beitNeemanTableLoader">
    <div class="loader-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">טוען...</span>
        </div>
        <p class="mt-3">טוען נתונים...</p>
    </div>
</div>

<div class="table-content-hidden" id="beitNeemanTableContent">
    <div id="beit-neeman">
        <div class="card fixed-card">
            <div class="card-body">
                <div class="table-action-bar" id="beitNeemanActionBar">
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-brand" id="addBeitNeemanBtn">הוסף חדש</button>
                        <button type="button" class="btn btn-brand" id="syncBeitNeemanBtn" title="סנכרון אוטומטי של ילדים מעל גיל 16">סנכרון ממאגר ילדים</button>
                        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#importBeitNeemanModal">ייבא אקסל</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-brand" id="exportBeitNeemanBtn">ייצוא אקסל</button>
                </div>
                <div class="table-scroll">
                    <div class="table-wrapper">
                        <table id="beitNeemanTable" class="table table-striped mb-0" style="width:100%">
                            <thead>
                            <tr>
                                <th>משפחה</th>
                                <th>שם הילד</th>
                                <th>גיל</th>
                                <th>שם האב</th>
                                <th>נייד אב</th>
                                <th>שם האם</th>
                                <th>לבית</th>
                                <th>נייד אם</th>
                                <th>כתובת</th>
                                <th>עיר</th>
                                <th>מין</th>
                                <th>ת.ז.</th>
                                <th>יום</th>
                                <th>חודש</th>
                                <th>שנה</th>
                                <th>מקום לימודים</th>
                                <th>הערות</th>
                                <th>סטטוס</th>
                                <?php if ($canEdit): ?>
                                <th>פעולות</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTable will load content via AJAX -->
                        </tbody>
                        </table>
                    </div>
                </div>
                <div class="table-pagination"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Beit Neeman -->
<div class="modal fade" id="beitNeemanModal" tabindex="-1" aria-labelledby="beitNeemanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="beitNeemanModalLabel">הוסף/ערוך רשומה - בית נאמן</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="beitNeemanForm">
                    <input type="hidden" id="beit_neeman_id" name="id">
                    <input type="hidden" id="child_record_id" name="child_record_id">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bn_family_name" class="form-label">שם משפחה <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bn_family_name" name="family_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bn_child_name" class="form-label">שם הילד <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bn_child_name" name="child_name" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="bn_gender" class="form-label">מין</label>
                            <select class="form-select" id="bn_gender" name="gender">
                                <option value="">בחר...</option>
                                <option value="זכר">זכר</option>
                                <option value="נקבה">נקבה</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="bn_age" class="form-label">גיל</label>
                            <input type="number" class="form-control" id="bn_age" name="age" min="16" max="120">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bn_father_name" class="form-label">שם האב</label>
                            <input type="text" class="form-control" id="bn_father_name" name="father_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bn_father_mobile" class="form-label">נייד אב</label>
                            <input type="text" class="form-control" id="bn_father_mobile" name="father_mobile">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bn_mother_name" class="form-label">שם האם</label>
                            <input type="text" class="form-control" id="bn_mother_name" name="mother_name">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bn_maiden_name" class="form-label">לבית</label>
                            <input type="text" class="form-control" id="bn_maiden_name" name="maiden_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bn_mother_mobile" class="form-label">נייד אם</label>
                            <input type="text" class="form-control" id="bn_mother_mobile" name="mother_mobile">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bn_child_id" class="form-label">תעודת זהות</label>
                            <input type="text" class="form-control" id="bn_child_id" name="child_id">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bn_address" class="form-label">כתובת</label>
                            <textarea class="form-control" id="bn_address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bn_city" class="form-label">עיר</label>
                            <input type="text" class="form-control" id="bn_city" name="city">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="bn_birth_day" class="form-label">יום</label>
                            <select class="form-select" id="bn_birth_day" name="birth_day">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bn_birth_month" class="form-label">חודש עברי</label>
                            <select class="form-select" id="bn_birth_month" name="birth_month">
                                <option value="">בחר...</option>
                                <option value="תשרי">תשרי</option>
                                <option value="חשון">חשון</option>
                                <option value="כסלו">כסלו</option>
                                <option value="טבת">טבת</option>
                                <option value="שבט">שבט</option>
                                <option value="אדר">אדר</option>
                                <option value="אדר א">אדר א</option>
                                <option value="אדר ב">אדר ב</option>
                                <option value="ניסן">ניסן</option>
                                <option value="אייר">אייר</option>
                                <option value="סיון">סיון</option>
                                <option value="תמוז">תמוז</option>
                                <option value="אב">אב</option>
                                <option value="אלול">אלול</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bn_birth_year" class="form-label">שנה עברית</label>
                            <select class="form-select" id="bn_birth_year" name="birth_year">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="bn_calculated_age" class="form-label">גיל משוער</label>
                            <input type="text" class="form-control" id="bn_calculated_age" readonly style="background-color: #e9ecef;">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="bn_status" class="form-label">סטטוס</label>
                            <select class="form-select" id="bn_status" name="status">
                                <option value="רווק">רווק</option>
                                <option value="מאורס">מאורס</option>
                                <option value="נשוי">נשוי</option>
                                <option value="גרוש">גרוש</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bn_study_place" class="form-label">מקום לימודים</label>
                            <input type="text" class="form-control" id="bn_study_place" name="study_place">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bn_notes" class="form-label">הערות</label>
                            <textarea class="form-control" id="bn_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="saveBeitNeemanBtn">שמור</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Beit Neeman Modal -->
<div class="modal fade" id="importBeitNeemanModal" tabindex="-1" aria-labelledby="importBeitNeemanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importBeitNeemanModalLabel">ייבוא בית נאמן מקובץ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body">
                <input type="file" class="form-control" id="beit_neeman_excel_file" accept=".xlsx,.xls">
                <small class="text-muted d-block mt-2">עמודות: משפחה, שם הילד, גיל, שם האב, נייד אב, שם האם, לבית, נייד אם, כתובת, עיר, מין, ת.ז., יום, חודש, שנה, מקום לימודים, הערות, סטטוס</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">בטל</button>
                <button type="button" class="btn btn-brand" id="importBeitNeemanFileBtn">ייבא</button>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
<script src="../assets/js/hebrew-dates.js"></script>
<script src="../assets/js/beit_neeman.js"></script>
