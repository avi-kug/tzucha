<?php include '../templates/header.php'; ?>

<div class="supports-container">
    <div class="supports-header">
        <h2>מערכת תמיכות</h2>
        <div class="actions-bar">
            <button id="addSupportBtn" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> הוסף תמיכה
            </button>
            <button id="exportExcelBtn" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> ייצוא לאקסל
            </button>
            <label for="importExcelFile" class="btn btn-info">
                <i class="bi bi-upload"></i> ייבוא מאקסל
            </label>
            <input type="file" id="importExcelFile" accept=".xlsx,.xls" style="display: none;">
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-nav">
        <a href="#data" class="tab-btn" data-tab="data">נתונים</a>
        <a href="#summary" class="tab-btn active" data-tab="summary">תמיכה</a>
    </div>

    <!-- Tab: תמיכה (Summary with Calculations) -->
    <div id="summary-tab" class="tab-panel active">
        <div class="table-container">
            <table id="summaryTable" class="supports-table">
                <thead>
                    <tr>
                        <th>שם</th>
                        <th>משפחה</th>
                        <th>מס' תורם</th>
                        <th>כתובת</th>
                        <th>עיר</th>
                        <th>סה"כ הכנסות</th>
                        <th>סה"כ הוצאות</th>
                        <th>כולל חריגה?</th>
                        <th>סה"כ הכנסות לנפש</th>
                        <th>סה"כ לתמיכה</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="summaryTableBody">
                    <!-- Data will be loaded here -->
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="5"><strong>סה"כ:</strong></td>
                        <td id="totalIncome"><strong>0</strong></td>
                        <td id="totalExpenses"><strong>0</strong></td>
                        <td></td>
                        <td id="totalSupport"><strong>0</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tab: נתונים (Raw Data) -->
    <div id="data-tab" class="tab-panel">
        <div class="table-container">
            <table id="dataTable" class="supports-table data-table">
                <thead>
                    <tr>
                        <th>מזהה</th>
                        <th>תאריך יצירה</th>
                        <th>תאריך עדכון</th>
                        <th>שם עמדה</th>
                        <th>שם פרטי</th>
                        <th>שם משפחה</th>
                        <th>מספר זהות</th>
                        <th>עיר</th>
                        <th>רחוב</th>
                        <th>מס' טל'</th>
                        <th>מס' נפשות בבית</th>
                        <th>מס' ילדים נשואים</th>
                        <th>מקום לימודים/עבודה 1</th>
                        <th>סכום הכנסה/מלגה 1</th>
                        <th>מקום לימודים/עבודה 2</th>
                        <th>סכום הכנסה/מלגה 2</th>
                        <th>קצבת ילדים</th>
                        <th>קצבת שארים</th>
                        <th>קצבת נכות</th>
                        <th>הבטחת הכנסה</th>
                        <th>השלמת הכנסה</th>
                        <th>סיוע בשכר דירה</th>
                        <th>מקור הקצבה אחר</th>
                        <th>סכום</th>
                        <th>הוצאות דיור</th>
                        <th>הוצאות שכר לימוד</th>
                        <th>הוצאה חריגה קבועה</th>
                        <th>פירוט הוצאה חריגה</th>
                        <th>סיבת הקושי</th>
                        <th>הערות</th>
                        <th>שם בעל החשבון</th>
                        <th>בנק</th>
                        <th>סניף</th>
                        <th>מס' חשבון</th>
                        <th>שם מבקש התמיכה</th>
                        <th>מספר עסקה</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody">
                    <!-- Data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Support -->
<div id="supportModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="modalTitle">הוספת תמיכה</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="supportForm">
                <input type="hidden" id="supportId" name="id">
                <?php echo csrf_input(); ?>
                
                <!-- בחירת אדם מהרשימה -->
                <div class="form-section">
                    <h4>בחירת אדם מהרשימה (אופציונלי)</h4>
                    <div class="form-group">
                        <label for="personSelect">בחר/י אדם:</label>
                        <select id="personSelect" class="form-control">
                            <option value="">-- הזנה ידנית --</option>
                        </select>
                        <button type="button" id="loadPersonDataBtn" class="btn btn-secondary">טען נתונים</button>
                    </div>
                </div>

                <hr>

                <!-- פרטים אישיים -->
                <div class="form-section">
                    <h4>פרטים אישיים</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="positionName">שם עמדה:</label>
                            <input type="text" id="positionName" name="position_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="firstName">שם פרטי:</label>
                            <input type="text" id="firstName" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">שם משפחה:</label>
                            <input type="text" id="lastName" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="idNumber">מספר זהות:</label>
                            <input type="text" id="idNumber" name="id_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="city">עיר:</label>
                            <input type="text" id="city" name="city" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="street">רחוב:</label>
                            <input type="text" id="street" name="street" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">מס' טל':</label>
                            <input type="text" id="phone" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="householdMembers">מס' נפשות בבית:</label>
                            <input type="number" id="householdMembers" name="household_members" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="marriedChildren">מס' ילדים נשואים:</label>
                            <input type="number" id="marriedChildren" name="married_children" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- הכנסות -->
                <div class="form-section">
                    <h4>הכנסות</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studyWorkPlace1">מקום לימודים/עבודה 1:</label>
                            <input type="text" id="studyWorkPlace1" name="study_work_place_1" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="incomeScholarship1">סכום הכנסה/מלגה 1:</label>
                            <input type="number" id="incomeScholarship1" name="income_scholarship_1" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studyWorkPlace2">מקום לימודים/עבודה 2:</label>
                            <input type="text" id="studyWorkPlace2" name="study_work_place_2" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="incomeScholarship2">סכום הכנסה/מלגה 2:</label>
                            <input type="number" id="incomeScholarship2" name="income_scholarship_2" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="childAllowance">קצבת ילדים:</label>
                            <input type="number" id="childAllowance" name="child_allowance" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="survivorAllowance">קצבת שארים:</label>
                            <input type="number" id="survivorAllowance" name="survivor_allowance" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="disabilityAllowance">קצבת נכות:</label>
                            <input type="number" id="disabilityAllowance" name="disability_allowance" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="incomeGuarantee">הבטחת הכנסה:</label>
                            <input type="number" id="incomeGuarantee" name="income_guarantee" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="incomeSupplement">השלמת הכנסה:</label>
                            <input type="number" id="incomeSupplement" name="income_supplement" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="rentAssistance">סיוע בשכר דירה:</label>
                            <input type="number" id="rentAssistance" name="rent_assistance" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="otherAllowanceSource">מקור הקצבה אחר:</label>
                            <input type="text" id="otherAllowanceSource" name="other_allowance_source" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="otherAllowanceAmount">סכום:</label>
                            <input type="number" id="otherAllowanceAmount" name="other_allowance_amount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- הוצאות -->
                <div class="form-section">
                    <h4>הוצאות</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="housingExpenses">הוצאות דיור:</label>
                            <input type="number" id="housingExpenses" name="housing_expenses" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="tuitionExpenses">הוצאות שכר לימוד:</label>
                            <input type="number" id="tuitionExpenses" name="tuition_expenses" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="recurringExceptionalExpense">הוצאה חריגה קבועה:</label>
                            <input type="number" id="recurringExceptionalExpense" name="recurring_exceptional_expense" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="exceptionalExpenseDetails">פירוט - הוצאה חריגה:</label>
                        <textarea id="exceptionalExpenseDetails" name="exceptional_expense_details" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="includeExceptionalInCalc" name="include_exceptional_in_calc" checked>
                            כלול הוצאה חריגה קבועה בחישוב סה"כ הוצאות של רשומה זו
                        </label>
                    </div>
                </div>

                <!-- מידע נוסף -->
                <div class="form-section">
                    <h4>מידע נוסף</h4>
                    <div class="form-group">
                        <label for="difficultyReason">פרט מה סיבת הקושי:</label>
                        <textarea id="difficultyReason" name="difficulty_reason" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">הערות:</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <!-- פרטי חשבון בנק -->
                <div class="form-section">
                    <h4>פרטי חשבון בנק</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="accountHolderName">שם בעל החשבון:</label>
                            <input type="text" id="accountHolderName" name="account_holder_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="bankName">בנק:</label>
                            <input type="text" id="bankName" name="bank_name" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="branchNumber">סניף:</label>
                            <input type="text" id="branchNumber" name="branch_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="accountNumber">מס' חשבון:</label>
                            <input type="text" id="accountNumber" name="account_number" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supportRequesterName">שם מבקש התמיכה:</label>
                            <input type="text" id="supportRequesterName" name="support_requester_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="transactionNumber">מספר עסקה:</label>
                            <input type="text" id="transactionNumber" name="transaction_number" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">שמור</button>
                    <button type="button" class="btn btn-secondary close-modal">ביטול</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Link Person -->
<div id="linkPersonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>שיוך אדם לרשומת תמיכה</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>נמצאו רשומות שאינן משויכות לאנשים מהרשימה:</p>
            <div id="linkPersonList"></div>
        </div>
    </div>
</div>

<!-- Modal: Import Results -->
<div id="importResultsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>תוצאות ייבוא</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="importResultsContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary close-modal">סגור</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/supports.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/supports.css') ?: time(); ?>">
<script src="../assets/js/supports.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/supports.js') ?: time(); ?>"></script>

<?php include '../templates/footer.php'; ?>