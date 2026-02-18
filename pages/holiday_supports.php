<?php include '../templates/header.php'; ?>

<div class="holiday-supports-container">
    <div class="supports-header">
        <h2>מערכת תמיכות חגים</h2>
        <div class="actions-bar">
            <button id="addHolidaySupportBtn" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> הוסף תמיכה
            </button>
            <button id="importJsonBtn" class="btn btn-info">
                <i class="bi bi-cloud-download"></i> ייבוא מטופס JSON
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
        <a href="#support" class="tab-btn active" data-tab="support">תמיכה</a>
        <a href="#calculations" class="tab-btn" data-tab="calculations">חישובים</a>
        <a href="#data" class="tab-btn" data-tab="data">נתונים</a>
        <a href="#approved" class="tab-btn" data-tab="approved">תמיכות שאושרו</a>
    </div>

    <!-- Tab 1: תמיכה (Support with Calculations) -->
    <div id="support-tab" class="tab-panel active">
        <div class="table-container">
            <table id="supportTable" class="holiday-supports-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllSupport"></th>
                        <th>מס' תורם</th>
                        <th>שם</th>
                        <th>משפחה</th>
                        <th>עלות תמיכה</th>
                        <th>תמיכה בסיסית (60%)</th>
                        <th>תמיכה מלאה</th>
                        <th>סכום לאישור</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="supportTableBody">
                    <!-- Data will be loaded here -->
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="4"><strong>סה"כ:</strong></td>
                        <td id="totalSupportCost"><strong>0</strong></td>
                        <td id="totalBasicSupport"><strong>0</strong></td>
                        <td id="totalFullSupport"><strong>0</strong></td>
                        <td id="totalToApprove"><strong>0</strong></td>
                        <td>
                            <button id="approveSelectedBtn" class="btn btn-success btn-sm" style="display: none;">
                                <i class="bi bi-check-circle"></i> אשר נבחרים
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tab 2: חישובים (Calculations) -->
    <div id="calculations-tab" class="tab-panel">
        <div class="calculations-header">
            <h3>הגדרת חישובים</h3>
            <button id="addCalculationBtn" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> הוסף חישוב
            </button>
        </div>
        <div class="calculations-list" id="calculationsList">
            <!-- Calculations will be loaded here -->
        </div>
    </div>

    <!-- Tab 3: נתונים (Data) -->
    <div id="data-tab" class="tab-panel">
        <div class="data-header">
            <h3>נתוני טפסים</h3>
            <div class="data-stats">
                <span>סה"כ טפסים: <strong id="totalForms">0</strong></span>
                <span>טפסים משויכים: <strong id="linkedForms">0</strong></span>
                <span>טפסים לא משויכים: <strong id="unlinkedForms">0</strong></span>
            </div>
        </div>
        <div class="table-container">
            <table id="dataTable" class="holiday-supports-table data-table">
                <thead>
                    <tr>
                        <th>מזהה</th>
                        <th>תאריך יצירה</th>
                        <th>שם מלא</th>
                        <th>עיר</th>
                        <th>כתובת</th>
                        <th>נפשות</th>
                        <th>ילדים</th>
                        <th>נשואים</th>
                        <th>משכורת אב</th>
                        <th>משכורת אם</th>
                        <th>הכנסות נוספות</th>
                        <th>קצבאות</th>
                        <th>שכר לימוד</th>
                        <th>שכר דירה</th>
                        <th>הוצאה חריגה</th>
                        <th>פירוט הוצאה</th>
                        <th>סה"כ לנפש</th>
                        <th>מדוע זקוק לסיוע</th>
                        <th>נשואים 0-3</th>
                        <th>נשואים 3-9</th>
                        <th>נשואים 9+</th>
                        <th>בנק</th>
                        <th>חשבון</th>
                        <th>מס' תורם</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody">
                    <!-- Data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 4: תמיכות שאושרו (Approved Supports) -->
    <div id="approved-tab" class="tab-panel">
        <div class="table-container">
            <table id="approvedTable" class="holiday-supports-table">
                <thead>
                    <tr>
                        <th>מס' תורם</th>
                        <th>שם</th>
                        <th>משפחה</th>
                        <th>סכום</th>
                        <th>תאריך תמיכה</th>
                        <th>תאריך אישור</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="approvedTableBody">
                    <!-- Data will be loaded here -->
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="3"><strong>סה"כ:</strong></td>
                        <td id="totalApprovedAmount"><strong>0</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Import JSON -->
<div id="importJsonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>ייבוא נתונים מטופס JSON</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="jsonUrl">כתובת URL של הטופס:</label>
                <input type="text" id="jsonUrl" class="form-control" 
                    value="https://matara.pro/nedarimplus/Forms/Manage.aspx?Action=GetJson&MosadId=25&ApiPassword=uf269&TofesId=3360&LastId=0&MaxId=500" 
                    style="direction: ltr;">
            </div>
            <div class="form-group">
                <button id="fetchJsonBtn" class="btn btn-primary">
                    <i class="bi bi-cloud-download"></i> טען נתונים
                </button>
            </div>
            <div id="jsonImportStatus" class="import-status"></div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Calculation -->
<div id="calculationModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="calculationModalTitle">הוספת חישוב</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="calculationForm">
                <input type="hidden" id="calculationId" name="id">
                
                <div class="form-group">
                    <label for="calculationName">שם החישוב:</label>
                    <input type="text" id="calculationName" name="name" class="form-control" required>
                </div>

                <div class="form-section">
                    <h4>קטגוריות לחישוב</h4>
                    <p class="help-text">בחר קטגוריות ומאפיינים לחישוב סכום התמיכה</p>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="use_gender" value="1">
                            מין
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="use_age" value="1">
                            גיל
                        </label>
                        <div class="age-range" style="display: none; margin-right: 20px;">
                            <label>גיל מ: <input type="number" name="age_from" min="0" max="120"></label>
                            <label>גיל עד: <input type="number" name="age_to" min="0" max="120"></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="use_city" value="1">
                            עיר
                        </label>
                        <div class="city-input" style="display: none; margin-right: 20px;">
                            <input type="text" name="city" class="form-control" placeholder="שם העיר">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="use_married" value="1">
                            נשואים
                        </label>
                        <div class="married-range" style="display: none; margin-right: 20px;">
                            <label>שנים מהחתונה מ: <input type="number" name="married_years_from" min="0" max="50"></label>
                            <label>שנים מהחתונה עד: <input type="number" name="married_years_to" min="0" max="50"></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="use_kids_count" value="1">
                            מספר ילדים
                        </label>
                        <div class="kids-range" style="display: none; margin-right: 20px;">
                            <label>מספר ילדים מ: <input type="number" name="kids_from" min="0" max="20"></label>
                            <label>מספר ילדים עד: <input type="number" name="kids_to" min="0" max="20"></label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="calculationAmount">סכום תמיכה:</label>
                    <input type="number" id="calculationAmount" name="amount" class="form-control" step="0.01" min="0" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">שמור</button>
                    <button type="button" class="btn btn-secondary close-modal">ביטול</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Link to Donor -->
<div id="linkDonorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>שיוך לתורם</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="linkFormId">
            <div class="form-group">
                <label for="donorSearch">חפש תורם:</label>
                <input type="text" id="donorSearch" class="form-control" placeholder="הקלד שם או מספר תורם...">
            </div>
            <div id="donorSearchResults" class="search-results">
                <!-- Search results will appear here -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Holiday Support -->
<div id="holidaySupportModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="supportModalTitle">הוספת תמיכת חג</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
            <form id="holidaySupportForm">
                <input type="hidden" id="supportId" name="id">
                <input type="hidden" id="formId" name="form_id">
                
                <!-- בחירת תורם מרשימה -->
                <div class="form-section">
                    <h4>בחירת תורם</h4>
                    <div class="form-group">
                        <label for="donorSelect">בחר/י תורם מהרשימה:</label>
                        <select id="donorSelect" class="form-control">
                            <option value="">-- הזנה ידנית --</option>
                        </select>
                        <button type="button" id="loadDonorDataBtn" class="btn btn-secondary btn-sm" style="margin-top: 10px;">טען נתוני תורם</button>
                    </div>
                </div>

                <hr>

                <!-- פרטים בסיסיים -->
                <div class="form-section">
                    <h4>פרטים בסיסיים</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="donorNumber">מס' תורם:</label>
                            <input type="text" id="donorNumber" name="donor_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="supportFirstName">שם:</label>
                            <input type="text" id="supportFirstName" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="supportLastName">משפחה:</label>
                            <input type="text" id="supportLastName" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullName">שם מלא:</label>
                            <input type="text" id="fullName" name="full_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="createdDate">תאריך יצירה:</label>
                            <input type="datetime-local" id="createdDate" name="created_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="masofId">תאריך עדכון:</label>
                            <input type="text" id="masofId" name="masof_id" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="emda">שם עמדה:</label>
                            <input type="text" id="emda" name="emda" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="street">כתובת:</label>
                            <input type="text" id="street" name="street" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="city">עיר:</label>
                            <input type="text" id="city" name="city" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- נתוני נפשות וילדים -->
                <div class="form-section">
                    <h4>נתוני נפשות וילדים</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sumKids">מס' נפשות בבית (כולל ההורים):</label>
                            <input type="number" id="sumKids" name="sum_kids" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="numKids">מס' נפשות נשואים:</label>
                            <input type="number" id="numKids" name="num_kids" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="sumKids2">מס' הילדים בבית:</label>
                            <input type="number" id="sumKids2" name="sum_kids2" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sumKids3">מס' ילדים קודם:</label>
                            <input type="number" id="sumKids3" name="sum_kids3" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="sumKidsM1">מס' נשואים עד 3 שנים מהחתונה:</label>
                            <input type="number" id="sumKidsM1" name="sum_kids_m1" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="sumKidsM2">מס' נשואים בין 3 ל 9 שנים מהחתונה:</label>
                            <input type="number" id="sumKidsM2" name="sum_kids_m2" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sumKidsM3">מס' נשואים מעל 9 שנים מהחתונה:</label>
                            <input type="number" id="sumKidsM3" name="sum_kids_m3" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- הכנסות -->
                <div class="form-section">
                    <h4>הכנסות</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="maskorteAv">משכורת בעל:</label>
                            <input type="number" id="maskorteAv" name="maskorte_av" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="maskorteAm">משכורת אישה:</label>
                            <input type="number" id="maskorteAm" name="maskorte_am" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="hachnasa">הכנסות נוספות (מלגות / שכירות וכדו'):</label>
                            <input type="number" id="hachnasa" name="hachnasa" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="kitzva">קצבאות (ביטוח לאומי וכדו'):</label>
                            <input type="number" id="kitzva" name="kitzva" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>

                <!-- הוצאות -->
                <div class="form-section">
                    <h4>הוצאות</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hotzaotLimud">שכר לימוד:</label>
                            <input type="number" id="hotzaotLimud" name="hotzaot_limud" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="hotzaotDira">שכר דירה:</label>
                            <input type="number" id="hotzaotDira" name="hotzaot_dira" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="hotzaotChariga">הוצאה חריגה:</label>
                            <input type="number" id="hotzaotChariga" name="hotzaot_chariga" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="hotzaotChariga2">פירוט הוצאה חריגה:</label>
                        <textarea id="hotzaotChariga2" name="hotzaot_chariga2" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="sumNefesh">סה"כ לנפש:</label>
                        <input type="number" id="sumNefesh" name="sum_nefesh" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="help">מדוע הינך זקוק לסיוע?</label>
                        <textarea id="help" name="help" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <!-- פרטי ילדים -->
                <div class="form-section">
                    <h4>פרטי הילדים בבית</h4>
                    <div id="kidsDataContainer">
                        <?php for ($i = 1; $i <= 16; $i++): ?>
                        <div class="kid-section" id="kidSection<?php echo $i; ?>" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                            <h5>ילד/ה <?php echo $i; ?></h5>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="kidName<?php echo $i; ?>">שם:</label>
                                    <input type="text" id="kidName<?php echo $i; ?>" name="kid_name_<?php echo $i; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="kidStatus<?php echo $i; ?>">סטטוס:</label>
                                    <input type="text" id="kidStatus<?php echo $i; ?>" name="kid_status_<?php echo $i; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="kidBd<?php echo $i; ?>">תאריך לידה:</label>
                                    <input type="date" id="kidBd<?php echo $i; ?>" name="kid_bd_<?php echo $i; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="age<?php echo $i; ?>">גיל:</label>
                                    <input type="number" id="age<?php echo $i; ?>" name="age_<?php echo $i; ?>" class="form-control" min="0" max="120">
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- בקשה לתמיכה מורחבת -->
                <div class="form-section">
                    <h4>בקשה לתמיכה מורחבת</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ishur1">כפוטא:</label>
                            <input type="text" id="ishur1" name="ishur1" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ishur1_">כמות (כפוטא):</label>
                            <input type="number" id="ishur1_" name="ishur1_" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="ishur_1_">עבור מי (כפוטא):</label>
                            <input type="text" id="ishur_1_" name="ishur_1_" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ishur2">כובע רגיל:</label>
                            <input type="text" id="ishur2" name="ishur2" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ishur2_">כמות (כובע רגיל):</label>
                            <input type="number" id="ishur2_" name="ishur2_" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="ishur_2_">עבור מי (כובע רגיל):</label>
                            <input type="text" id="ishur_2_" name="ishur_2_" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ishur3">כובע חסידי / ירושלמי:</label>
                            <input type="text" id="ishur3" name="ishur3" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ishur3_">כמות (כובע חסידי):</label>
                            <input type="number" id="ishur3_" name="ishur3_" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="ishur_3_">עבור מי (כובע חסידי):</label>
                            <input type="text" id="ishur_3_" name="ishur_3_" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ishur">בקשה לתמיכה מורחבת:</label>
                        <textarea id="ishur" name="ishur" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <!-- פרטי בנק -->
                <div class="form-section">
                    <h4>פרטי בנק</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bankName">שם בעל החשבון:</label>
                            <input type="text" id="bankName" name="bank_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="bank">מספר בנק:</label>
                            <input type="text" id="bank" name="bank" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="snif">מספר סניף:</label>
                            <input type="text" id="snif" name="snif" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="account">מספר חשבון:</label>
                            <input type="text" id="account" name="account" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="nameBakasha">שם מבקש הבקשה:</label>
                            <input type="text" id="nameBakasha" name="name_bakasha" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="transactionId">מספר עסקה:</label>
                            <input type="text" id="transactionId" name="transaction_id" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- סכומי תמיכה -->
                <div class="form-section">
                    <h4>סכומי תמיכה</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supportCost">עלות תמיכה:</label>
                            <input type="number" id="supportCost" name="support_cost" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="basicSupport">תמיכה בסיסית (60%):</label>
                            <input type="number" id="basicSupport" name="basic_support" class="form-control" step="0.01" min="0" value="0" readonly>
                        </div>
                        <div class="form-group">
                            <label for="fullSupport">תמיכה מלאה:</label>
                            <input type="number" id="fullSupport" name="full_support" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="supportDate">תאריך תמיכה:</label>
                        <input type="date" id="supportDate" name="support_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- הערות -->
                <div class="form-section">
                    <h4>הערות</h4>
                    <div class="form-group">
                        <label for="supportNotes">הערות:</label>
                        <textarea id="supportNotes" name="notes" class="form-control" rows="3"></textarea>
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

<link rel="stylesheet" href="../assets/css/holiday_supports.css?v=<?php echo time(); ?>">
<script src="../assets/js/holiday_supports.js?v=<?php echo time(); ?>"></script>

<?php include '../templates/footer.php'; ?>
