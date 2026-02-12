<?php include '../templates/header.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<h2>מזומן - תרומות מ-ipapp.org</h2>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> 
    <strong>לתשומת לבך:</strong> הנתונים נשמרים בדאטאבייס המקומי. 
    לחץ על <strong>"רענן מ-ipapp.org"</strong> כדי לסנכרן נתונים עדכניים מהאתר החיצוני.
</div>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <span class="text-muted">נתונים מתוך ipapp.org/kupot</span>
    <div>
        <span id="cacheInfo" class="text-muted me-3" style="font-size:0.85rem;"></span>
        <button id="addBtn" class="btn btn-sm btn-success me-2">
            <i class="bi bi-plus-circle"></i> הוספת תרומה
        </button>
        <button id="refreshBtn" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-clockwise"></i> רענן מ-ipapp.org
        </button>
    </div>
</div>

<div id="loadingIndicator" class="text-center py-4">
    <div class="spinner-border text-primary" role="status"></div>
    <div class="mt-2" id="loadingText">טוען נתונים...</div>
</div>

<div class="table-responsive" id="tableContainer" style="display:none;">
    <table id="cashTable" class="table table-bordered table-striped" style="width:100%">
        <thead><tr id="cashTableHead"></tr></thead>
        <tbody></tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">עריכת תרומה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label for="editAmount" class="form-label">סכום</label>
                    <input type="number" class="form-control" id="editAmount" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="editNotes" class="form-label">הערות</label>
                    <textarea class="form-control" id="editNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">שמור</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">הוספת תרומה חדשה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addClient" class="form-label">לקוח <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="addClient" placeholder="שם משפחה" required>
                </div>
                <div class="mb-3">
                    <label for="addProject" class="form-label">פרויקט</label>
                    <select class="form-control" id="addProject">
                        <option value="חודש אדר">חודש אדר</option>
                        <option value="amshinov">amshinov</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addAmount" class="form-label">סכום <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="addAmount" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="addNotes" class="form-label">הערות</label>
                    <textarea class="form-control" id="addNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-success" id="saveAddBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="addSpinner"></span>
                    הוסף
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js"></script>

<?php $pageScripts = ['../assets/js/cash.js?v=' . (@filemtime(__DIR__ . '/../assets/js/cash.js') ?: time())]; ?>
<?php include '../templates/footer.php'; ?>
