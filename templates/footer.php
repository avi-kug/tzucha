            </div>
        </div>
    </div>
    <footer class="footer-fixed text-white" style="display:flex; align-items:center;">
        <?php if (auth_is_logged_in()): ?>
            <div style="width:250px; padding-right:1rem;">
                <a class="btn btn-outline-light btn-sm" href="/tzucha/pages/logout.php">
                    <i class="bi bi-box-arrow-left"></i> התנתקות
                </a>
            </div>
        <?php endif; ?>
        <p style="margin:0; flex:1; text-align:center;">&copy; צדקה וחסד אמשינוב</p>
    </footer>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/colresizable/colResizable-1.6.min.js"></script>
    <?php $jsV = @filemtime(__DIR__ . '/../assets/js/script.js') ?: '20260127'; ?>
    <script src="../assets/js/script.js?v=<?php echo $jsV; ?>"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $ps): ?>
            <script src="<?php echo $ps; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>