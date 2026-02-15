
<?php
// Don't load heavy cache data into page - let JavaScript fetch it via AJAX
include '../templates/header.php';
?>
<h2>מלבושי כבוד</h2>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs" id="kavodTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="raw-data-tab" data-bs-toggle="tab" data-bs-target="#raw-data" type="button" role="tab">
            נתונים מ-Kavod
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="alphon-tab" data-bs-toggle="tab" data-bs-target="#alphon" type="button" role="tab">
            אלפון משולב
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="kavodTabContent">
    <!-- Tab 1: Raw Kavod Data -->
    <div class="tab-pane fade show active" id="raw-data" role="tabpanel">
        <div class="mb-3 mt-3 d-flex justify-content-between align-items-center">
            <span>נתונים מתוך Kavod.org.il</span>
            <div>
                <span id="cacheInfo" class="text-muted me-3" style="font-size:0.85rem;"></span>
                <button id="refreshBtn" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> רענן מ-Kavod
                </button>
            </div>
        </div>

        <div id="loadingIndicator" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2" id="loadingText">טוען נתונים...</div>
        </div>

        <div class="table-responsive" id="tableContainer" style="display:none;">
            <table id="kavodTable" class="table table-bordered table-striped" style="width:100%">
                <thead><tr id="kavodTableHead"></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Alphon (Combined Data) -->
    <div class="tab-pane fade" id="alphon" role="tabpanel">
        <div class="mb-3 mt-3 d-flex justify-content-between align-items-center">
            <div>
                <span id="alphonCacheInfo" class="text-muted me-3" style="font-size:0.85rem;"></span>
                <button id="refreshAlphonBtn" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> רענן נתונים
                </button>
            </div>
        </div>

        <div id="alphonLoadingIndicator" class="text-center py-4" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2">טוען נתונים משולבים...</div>
        </div>

        <div class="table-responsive" id="alphonTableContainer">
            <table id="alphonTable" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>מזהה מלבושי כבוד</th>
                        <th>משפחה</th>
                        <th>שם</th>
                        <th>ת.ז.</th>
                        <th>כתובת</th>
                        <th>שכונה</th>
                        <th>עיר</th>
                        <th>טלפון</th>
                        <th>נייד בעל</th>
                        <th>נייד אשה</th>
                        <th>כתובת מייל</th>
                        <th>מספר ילדים</th>
                        <th>סטטוס עדכון</th>
                        <th>מספר הזמנות</th>
                        <th>סה״כ הזמנות קודמות</th>
                        <th>יתרה</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Person Details Modal -->
<div class="modal fade" id="kavodPersonModal" tabindex="-1" aria-labelledby="kavodPersonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kavodPersonModalLabel">פרטים מלאים</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="סגור"></button>
            </div>
            <div class="modal-body" id="kavodPersonModalBody">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary border-bottom pb-2">פרטים אישיים</h6>
                        <table class="table table-sm">
                            <tr><th>מזהה מלבושי כבוד:</th><td id="detail_phone_id"></td></tr>
                            <tr><th>משפחה:</th><td id="detail_family_name"></td></tr>
                            <tr><th>שם פרטי:</th><td id="detail_first_name"></td></tr>
                            <tr><th>ת.ז.:</th><td id="detail_husband_id"></td></tr>
                        </table>
                        
                        <h6 class="text-primary border-bottom pb-2 mt-3">כתובת ואזור</h6>
                        <table class="table table-sm">
                            <tr><th>כתובת:</th><td id="detail_address"></td></tr>
                            <tr><th>שכונה:</th><td id="detail_neighborhood"></td></tr>
                            <tr><th>עיר:</th><td id="detail_city"></td></tr>
                        </table>
                        
                        <h6 class="text-primary border-bottom pb-2 mt-3">פרטי התקשרות</h6>
                        <table class="table table-sm">
                            <tr><th>טלפון:</th><td id="detail_phone"></td></tr>
                            <tr><th>נייד בעל:</th><td id="detail_husband_mobile"></td></tr>
                            <tr><th>נייד אשה:</th><td id="detail_wife_mobile"></td></tr>
                            <tr><th>כתובת מייל:</th><td id="detail_updated_email"></td></tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-success border-bottom pb-2">נתונים ממלבושי כבוד</h6>
                        <table class="table table-sm">
                            <tr><th>מספר ילדים:</th><td id="detail_number_of_children"></td></tr>
                            <tr><th>סטטוס עדכון:</th><td id="detail_update_status"></td></tr>
                        </table>
                        
                        <h6 class="text-success border-bottom pb-2 mt-3">הזמנות ויתרות</h6>
                        <table class="table table-sm">
                            <tr><th>מספר הזמנות:</th><td id="detail_orders_count"></td></tr>
                            <tr><th>סה״כ הזמנות קודמות:</th><td id="detail_total_previous_orders"></td></tr>
                            <tr><th>יתרה:</th><td id="detail_balance" class="fw-bold"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
            </div>
        </div>
    </div>
</div>

<?php $pageScripts = ['../assets/js/honor_clothing.js?v=' . (@filemtime(__DIR__ . '/../assets/js/honor_clothing.js') ?: time())]; ?>
<?php include '../templates/footer.php'; ?>
