
<?php include '../templates/header.php'; ?>
<h2>מלבושי כבוד</h2>
<div class="mb-3 d-flex justify-content-between align-items-center">
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

<?php $pageScripts = ['../assets/js/honor_clothing.js?v=' . (@filemtime(__DIR__ . '/../assets/js/honor_clothing.js') ?: time())]; ?>
<?php include '../templates/footer.php'; ?>
