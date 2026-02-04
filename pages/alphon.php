<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
session_start();

$stmt = $pdo->query("SELECT id, full_name, address, city, phone, husband_mobile, wife_name, wife_mobile, updated_email, husband_email, wife_email, alphon, send_messages FROM people ORDER BY full_name");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>

<style>
.editable { cursor: pointer; background-color: transparent; transition: background-color 0.2s; }
.editable:hover { background-color: #fff3cd !important; }
.editing { background-color: #d1ecf1 !important; }
#alphonTable { table-layout: auto; }
#alphonTable th, #alphonTable td { padding-top: 4px !important; padding-bottom: 4px !important; }
.fixed-card { height: 80vh; }
.fixed-card .card-body { display: flex; flex-direction: column; min-height: 0; }
.fixed-card .table-scroll { flex: 1 1 auto; overflow: auto; min-height: 0; }
#alphonTable thead th,
#alphonTable tbody td {
    text-align: right;
}
#alphonTable th:nth-child(1), #alphonTable td:nth-child(1) { min-width: 180px; }
#alphonTable th:nth-child(2), #alphonTable td:nth-child(2) { min-width: 200px; }
#alphonTable th:nth-child(3), #alphonTable td:nth-child(3) { min-width: 120px; }
#alphonTable th:nth-child(4), #alphonTable td:nth-child(4) { min-width: 120px; }
#alphonTable th:nth-child(5), #alphonTable td:nth-child(5) { min-width: 130px; }
#alphonTable th:nth-child(6), #alphonTable td:nth-child(6) { min-width: 140px; }
#alphonTable th:nth-child(7), #alphonTable td:nth-child(7) { min-width: 130px; }
#alphonTable th:nth-child(8), #alphonTable td:nth-child(8) { min-width: 200px; }
#alphonTable th:nth-child(9), #alphonTable td:nth-child(9) { min-width: 180px; }
#alphonTable th:nth-child(10), #alphonTable td:nth-child(10) { min-width: 180px; }
#alphonTable th:nth-child(11), #alphonTable td:nth-child(11) { min-width: 100px; }
#alphonTable th:nth-child(12), #alphonTable td:nth-child(12) { min-width: 100px; }
#alphonTable th:nth-child(13), #alphonTable td:nth-child(13) { min-width: 120px; }
</style>

<h2>אלפון</h2>
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
                            <td><?php echo htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['husband_mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['wife_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['wife_mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['updated_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['husband_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['wife_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['alphon'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['send_messages'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
    function initTable($){
        if ($.fn.DataTable.isDataTable('#alphonTable')) {
            $('#alphonTable').DataTable().clear().destroy();
        }
        const table = $('#alphonTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json' },
            pageLength: 25,
            autoWidth: true,
            responsive: true,
            order: [[0, 'asc']],
            dom: "<'row'<'col-md-6'l><'col-md-6'f>>rt<'row'<'col-md-6'i><'col-md-6'p>>"
        });
    }

    function tryInit(attempts){
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            initTable(jQuery);
            return;
        }
        if ((attempts||0) < 50) {
            setTimeout(function(){ tryInit((attempts||0)+1); }, 100);
        }
    }
    tryInit(0);
})();
</script>

<?php include '../templates/footer.php'; ?>
