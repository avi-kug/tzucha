// Holiday Supports JavaScript

let currentTab = 'support';
let supportsData = [];
let calculationsData = [];
let formsData = [];
let approvedData = [];
let selectedSupports = [];
let donorsList = [];

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeModals();
    initializeEventListeners();
    loadDonorsList();
    loadData();
});

// Initialize Tabs
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.preventDefault();
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });

    window.addEventListener('hashchange', function() {
        const hash = window.location.hash.replace('#', '');
        if (['support', 'calculations', 'data', 'approved'].includes(hash) && hash !== currentTab) {
            switchTab(hash);
        }
    });
}

function switchTab(tabName) {
    window.history.pushState(null, '', '#' + tabName);
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    document.querySelectorAll('.tab-panel').forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });
    const activeTab = document.getElementById(`${tabName}-tab`);
    activeTab.style.display = 'block';
    activeTab.classList.add('active');
    
    currentTab = tabName;
    loadData();
}

// Initialize Modals
function initializeModals() {
    const closes = document.querySelectorAll('.close, .close-modal');
    
    closes.forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            closeModals();
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModals();
        }
    });
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Initialize Event Listeners
function initializeEventListeners() {
    // Add Holiday Support Button
    document.getElementById('addHolidaySupportBtn').addEventListener('click', function() {
        openHolidaySupportModal();
    });
    
    // Import JSON Button
    document.getElementById('importJsonBtn').addEventListener('click', function() {
        openModal('importJsonModal');
    });
    
    // Fetch JSON Button
    document.getElementById('fetchJsonBtn').addEventListener('click', function() {
        importFromJson();
    });
    
    // Export Excel Button
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        exportToExcel();
    });
    
    // Import Excel File
    document.getElementById('importExcelFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            importFromExcel(file);
        }
    });
    
    // Holiday Support Form Submit
    document.getElementById('holidaySupportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveHolidaySupport();
    });
    
    // Support Cost Input Change
    document.getElementById('supportCost').addEventListener('input', function() {
        const cost = parseFloat(this.value) || 0;
        document.getElementById('basicSupport').value = (cost * 0.6).toFixed(2);
    });
    
    // Load Donor Data Button
    document.getElementById('loadDonorDataBtn').addEventListener('click', function() {
        loadDonorDataToForm();
    });
    
    // Donor Select Change
    document.getElementById('donorSelect').addEventListener('change', function() {
        if (this.value) {
            loadDonorDataToForm();
        }
    });
    
    // Add Calculation Button
    document.getElementById('addCalculationBtn').addEventListener('click', function() {
        openCalculationModal();
    });
    
    // Calculation Form Submit
    document.getElementById('calculationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveCalculation();
    });
    
    // Calculation Form Checkboxes
    const calcForm = document.getElementById('calculationForm');
    calcForm.querySelector('[name="use_age"]').addEventListener('change', function() {
        calcForm.querySelector('.age-range').style.display = this.checked ? 'block' : 'none';
    });
    calcForm.querySelector('[name="use_city"]').addEventListener('change', function() {
        calcForm.querySelector('.city-input').style.display = this.checked ? 'block' : 'none';
    });
    calcForm.querySelector('[name="use_married"]').addEventListener('change', function() {
        calcForm.querySelector('.married-range').style.display = this.checked ? 'block' : 'none';
    });
    calcForm.querySelector('[name="use_kids_count"]').addEventListener('change', function() {
        calcForm.querySelector('.kids-range').style.display = this.checked ? 'block' : 'none';
    });
    
    // Approve Selected Button
    document.getElementById('approveSelectedBtn').addEventListener('click', function() {
        approveSelected();
    });
    
    // Select All Support Checkbox
    document.getElementById('selectAllSupport').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#supportTableBody input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedSupports();
    });
    
    // Donor Search
    document.getElementById('donorSearch').addEventListener('input', debounce(searchDonors, 300));
}

// Load Data
async function loadData() {
    try {
        const response = await fetch(`holiday_supports_api.php?action=get_data&tab=${currentTab}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            if (currentTab === 'support') {
                supportsData = result.data;
                renderSupportTable();
            } else if (currentTab === 'calculations') {
                calculationsData = result.data;
                renderCalculationsList();
            } else if (currentTab === 'data') {
                formsData = result.data;
                renderDataTable();
                loadStats();
            } else if (currentTab === 'approved') {
                approvedData = result.data;
                renderApprovedTable();
            }
        } else {
            showNotification('שגיאה בטעינת נתונים', 'error');
        }
    } catch (error) {
        console.error('Error loading data:', error);
        showNotification('שגיאה בטעינת נתונים', 'error');
    }
}

// Load Donors List
async function loadDonorsList() {
    try {
        const response = await fetch('holiday_supports_api.php?action=get_donors_list', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            donorsList = result.data;
            populateDonorsDropdown();
        }
    } catch (error) {
        console.error('Error loading donors list:', error);
    }
}

function populateDonorsDropdown() {
    const select = document.getElementById('donorSelect');
    select.innerHTML = '<option value="">-- הזנה ידנית --</option>';
    
    donorsList.forEach(donor => {
        const option = document.createElement('option');
        option.value = donor.donor_number;
        option.textContent = `${donor.donor_number} - ${donor.last_name} ${donor.first_name}`;
        option.dataset.firstName = donor.first_name;
        option.dataset.lastName = donor.last_name;
        select.appendChild(option);
    });
}

async function loadDonorDataToForm() {
    const select = document.getElementById('donorSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('donorNumber').value = selectedOption.value;
        document.getElementById('supportFirstName').value = selectedOption.dataset.firstName || '';
        document.getElementById('supportLastName').value = selectedOption.dataset.lastName || '';
        
        // Load extended form data if available
        await loadFormDataForSupport(selectedOption.value);
    }
}

// Load Stats
async function loadStats() {
    try {
        const response = await fetch('holiday_supports_api.php?action=get_stats', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalForms').textContent = result.data.total || 0;
            document.getElementById('linkedForms').textContent = result.data.linked || 0;
            document.getElementById('unlinkedForms').textContent = result.data.unlinked || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Render Support Table
function renderSupportTable() {
    const tbody = document.getElementById('supportTableBody');
    tbody.innerHTML = '';
    
    let totalCost = 0;
    let totalBasic = 0;
    let totalFull = 0;
    let totalToApprove = 0;
    
    supportsData.forEach(support => {
        const cost = parseFloat(support.support_cost) || 0;
        const basic = parseFloat(support.basic_support_calc) || 0;
        const full = parseFloat(support.full_support) || 0;
        
        totalCost += cost;
        totalBasic += basic;
        totalFull += full;
        totalToApprove += full;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="checkbox" class="support-checkbox" data-id="${support.id}"></td>
            <td>${support.donor_number || ''}</td>
            <td>${support.first_name || ''}</td>
            <td>${support.last_name || ''}</td>
            <td>${formatNumber(cost)}</td>
            <td>${formatNumber(basic)}</td>
            <td>${formatNumber(full)}</td>
            <td><input type="number" class="form-control" value="${formatNumber(full)}" data-id="${support.id}"></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editSupport(${support.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="approveSupport(${support.id})">
                    <i class="bi bi-check-circle"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteSupport(${support.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('totalSupportCost').textContent = formatNumber(totalCost);
    document.getElementById('totalBasicSupport').textContent = formatNumber(totalBasic);
    document.getElementById('totalFullSupport').textContent = formatNumber(totalFull);
    document.getElementById('totalToApprove').textContent = formatNumber(totalToApprove);
    
    // Add checkbox listeners
    document.querySelectorAll('.support-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedSupports);
    });
}

// Render Calculations List
function renderCalculationsList() {
    const list = document.getElementById('calculationsList');
    list.innerHTML = '';
    
    if (calculationsData.length === 0) {
        list.innerHTML = '<p class="no-data">לא נמצאו חישובים. הוסף חישוב חדש.</p>';
        return;
    }
    
    calculationsData.forEach(calc => {
        const conditions = JSON.parse(calc.conditions || '{}');
        const conditionsList = [];
        
        if (conditions.use_age) {
            conditionsList.push(`גיל: ${conditions.age_from}-${conditions.age_to}`);
        }
        if (conditions.use_city && conditions.city) {
            conditionsList.push(`עיר: ${conditions.city}`);
        }
        if (conditions.use_married) {
            conditionsList.push(`נשואים: ${conditions.married_years_from}-${conditions.married_years_to} שנים`);
        }
        if (conditions.use_kids_count) {
            conditionsList.push(`ילדים: ${conditions.kids_from}-${conditions.kids_to}`);
        }
        
        const card = document.createElement('div');
        card.className = 'calculation-card';
        card.innerHTML = `
            <div class="calculation-header">
                <h4>${calc.name}</h4>
                <div class="calculation-actions">
                    <button class="btn btn-sm btn-primary" onclick="editCalculation(${calc.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCalculation(${calc.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="calculation-details">
                <div><strong>תנאים:</strong> ${conditionsList.join(', ') || 'ללא תנאים'}</div>
                <div><strong>סכום:</strong> ₪${formatNumber(calc.amount)}</div>
            </div>
        `;
        list.appendChild(card);
    });
    
    // Add apply calculations button if there are calculations
    if (calculationsData.length > 0) {
        const applyBtn = document.createElement('button');
        applyBtn.className = 'btn btn-success';
        applyBtn.innerHTML = '<i class="bi bi-calculator"></i> החל חישובים על כל הטפסים';
        applyBtn.onclick = applyCalculations;
        list.insertBefore(applyBtn, list.firstChild);
    }
}

// Render Data Table
function renderDataTable() {
    const tbody = document.getElementById('dataTableBody');
    tbody.innerHTML = '';
    
    formsData.forEach(form => {
        const row = document.createElement('tr');
        row.className = form.donor_number ? 'linked' : 'unlinked';
        row.innerHTML = `
            <td>${form.form_id || ''}</td>
            <td>${form.created_date || ''}</td>
            <td>${form.full_name || ''}</td>
            <td>${form.city || ''}</td>
            <td>${form.street || ''}</td>
            <td>${form.sum_kids || 0}</td>
            <td>${form.sum_kids2 || 0}</td>
            <td>${form.num_kids || 0}</td>
            <td>${formatNumber(form.maskorte_av || 0)}</td>
            <td>${formatNumber(form.maskorte_am || 0)}</td>
            <td>${formatNumber(form.hachnasa || 0)}</td>
            <td>${formatNumber(form.kitzva || 0)}</td>
            <td>${formatNumber(form.hotzaot_limud || 0)}</td>
            <td>${formatNumber(form.hotzaot_dira || 0)}</td>
            <td>${formatNumber(form.hotzaot_chariga || 0)}</td>
            <td title="${form.hotzaot_chariga2 || ''}">${(form.hotzaot_chariga2 || '').substring(0, 20)}${(form.hotzaot_chariga2 || '').length > 20 ? '...' : ''}</td>
            <td>${formatNumber(form.sum_nefesh || 0)}</td>
            <td title="${form.help || ''}">${(form.help || '').substring(0, 20)}${(form.help || '').length > 20 ? '...' : ''}</td>
            <td>${form.sum_kids_m1 || 0}</td>
            <td>${form.sum_kids_m2 || 0}</td>
            <td>${form.sum_kids_m3 || 0}</td>
            <td>${form.bank || ''} ${form.snif || ''}</td>
            <td>${form.account || ''}</td>
            <td>${form.donor_number || '<span class="badge bg-warning">לא משויך</span>'}</td>
            <td>
                ${!form.donor_number ? `
                    <button class="btn btn-sm btn-primary" onclick="linkToDonor(${form.id})">
                        <i class="bi bi-link"></i> שייך
                    </button>
                ` : `
                    <button class="btn btn-sm btn-info" onclick="viewFormDetails(${form.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                `}
                <button class="btn btn-sm btn-danger" onclick="deleteFormData(${form.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Render Approved Table
function renderApprovedTable() {
    const tbody = document.getElementById('approvedTableBody');
    tbody.innerHTML = '';
    
    let total = 0;
    
    approvedData.forEach(support => {
        const amount = parseFloat(support.full_support) || 0;
        total += amount;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${support.donor_number || ''}</td>
            <td>${support.first_name || ''}</td>
            <td>${support.last_name || ''}</td>
            <td>${formatNumber(amount)}</td>
            <td>${support.support_date || ''}</td>
            <td>${support.approved_at || ''}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewApprovedDetails(${support.id})">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteApprovedSupport(${support.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('totalApprovedAmount').textContent = formatNumber(total);
}

// Import from JSON
async function importFromJson() {
    const lastId = document.getElementById('lastId')?.value || 0;
    const maxId = document.getElementById('maxId')?.value || 500;
    const statusDiv = document.getElementById('jsonImportStatus');
    
    statusDiv.innerHTML = '<div class="alert alert-info">מייבא נתונים...</div>';
    
    try {
        const formData = new FormData();
        formData.append('action', 'import_json');
        formData.append('last_id', lastId);
        formData.append('max_id', maxId);
        
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>הייבוא הושלם בהצלחה!</strong><br>
                    נוספו: ${result.imported}<br>
                    עודכנו: ${result.updated}<br>
                    ${result.errors.length > 0 ? `שגיאות: ${result.errors.length}` : ''}
                </div>
            `;
            
            if (result.errors.length > 0) {
                const errorsList = result.errors.map(e => `<li>${e}</li>`).join('');
                statusDiv.innerHTML += `<div class="alert alert-warning"><ul>${errorsList}</ul></div>`;
            }
            
            // Reload data
            setTimeout(() => {
                closeModals();
                if (currentTab === 'data') {
                    loadData();
                }
            }, 2000);
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger">שגיאה: ${result.error}</div>`;
        }
    } catch (error) {
        console.error('Error importing JSON:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger">שגיאה בייבוא הנתונים</div>';
    }
}

// Save Holiday Support
async function saveHolidaySupport() {
    const formData = new FormData(document.getElementById('holidaySupportForm'));
    formData.append('action', 'save_support');
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('התמיכה נשמרה בהצלחה', 'success');
            closeModals();
            loadData();
        } else {
            showNotification('שגיאה בשמירת התמיכה: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error saving support:', error);
        showNotification('שגיאה בשמירת התמיכה', 'error');
    }
}

// Save Calculation
async function saveCalculation() {
    const formData = new FormData(document.getElementById('calculationForm'));
    formData.append('action', 'save_calculation');
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('החישוב נשמר בהצלחה', 'success');
            closeModals();
            loadData();
        } else {
            showNotification('שגיאה בשמירת החישוב: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error saving calculation:', error);
        showNotification('שגיאה בשמירת החישוב', 'error');
    }
}

// Open Holiday Support Modal
function openHolidaySupportModal(supportId = null) {
    document.getElementById('supportModalTitle').textContent = supportId ? 'עריכת תמיכה' : 'הוספת תמיכה';
    document.getElementById('holidaySupportForm').reset();
    document.getElementById('supportId').value = supportId || '';
    document.getElementById('formId').value = '';
    
    // Reset donor select
    document.getElementById('donorSelect').value = '';
    
    // Clear all kids sections
    for (let i = 1; i <= 16; i++) {
        document.getElementById(`kidName${i}`).value = '';
        document.getElementById(`kidStatus${i}`).value = '';
        document.getElementById(`kidBd${i}`).value = '';
        document.getElementById(`age${i}`).value = '';
    }
    
    if (supportId) {
        const support = supportsData.find(s => s.id == supportId);
        if (support) {
            // Try to find donor in select
            const donorSelect = document.getElementById('donorSelect');
            const option = Array.from(donorSelect.options).find(opt => opt.value == support.donor_number);
            if (option) {
                donorSelect.value = support.donor_number;
            }
            
            // Basic fields
            document.getElementById('donorNumber').value = support.donor_number || '';
            document.getElementById('supportFirstName').value = support.first_name || '';
            document.getElementById('supportLastName').value = support.last_name || '';
            document.getElementById('supportCost').value = support.support_cost || 0;
            document.getElementById('basicSupport').value = support.basic_support || 0;
            document.getElementById('fullSupport').value = support.full_support || 0;
            document.getElementById('supportDate').value = support.support_date || '';
            document.getElementById('supportNotes').value = support.notes || '';
            
            // Load extended form data if available
            if (support.donor_number) {
                loadFormDataForSupport(support.donor_number);
            }
        }
    }
    
    openModal('holidaySupportModal');
}

// Load form data for a support record
async function loadFormDataForSupport(donorNumber) {
    try {
        const response = await fetch(`holiday_supports_api.php?action=get_form_by_donor&donor_number=${donorNumber}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success && result.data) {
            const form = result.data;
            
            // Populate extended fields
            document.getElementById('formId').value = form.id || '';
            document.getElementById('fullName').value = form.full_name || '';
            document.getElementById('createdDate').value = form.created_date ? form.created_date.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('masofId').value = form.masof_id || '';
            document.getElementById('emda').value = form.emda || '';
            document.getElementById('street').value = form.street || '';
            document.getElementById('city').value = form.city || '';
            
            // Nefashot and kids
            document.getElementById('sumKids').value = form.sum_kids || 0;
            document.getElementById('numKids').value = form.num_kids || 0;
            document.getElementById('sumKids2').value = form.sum_kids2 || 0;
            document.getElementById('sumKids3').value = form.sum_kids3 || 0;
            document.getElementById('sumKidsM1').value = form.sum_kids_m1 || 0;
            document.getElementById('sumKidsM2').value = form.sum_kids_m2 || 0;
            document.getElementById('sumKidsM3').value = form.sum_kids_m3 || 0;
            
            // Income
            document.getElementById('maskorteAv').value = form.maskorte_av || 0;
            document.getElementById('maskorteAm').value = form.maskorte_am || 0;
            document.getElementById('hachnasa').value = form.hachnasa || 0;
            document.getElementById('kitzva').value = form.kitzva || 0;
            
            // Expenses
            document.getElementById('hotzaotLimud').value = form.hotzaot_limud || 0;
            document.getElementById('hotzaotDira').value = form.hotzaot_dira || 0;
            document.getElementById('hotzaotChariga').value = form.hotzaot_chariga || 0;
            document.getElementById('hotzaotChariga2').value = form.hotzaot_chariga2 || '';
            document.getElementById('sumNefesh').value = form.sum_nefesh || 0;
            document.getElementById('help').value = form.help || '';
            
            // Bank details
            document.getElementById('bankName').value = form.bank_name || '';
            document.getElementById('bank').value = form.bank || '';
            document.getElementById('snif').value = form.snif || '';
            document.getElementById('account').value = form.account || '';
            document.getElementById('nameBakasha').value = form.name_bakasha || '';
            document.getElementById('transactionId').value = form.transaction_id || '';
            
            // Ishur fields
            document.getElementById('ishur1').value = form.ishur1 || '';
            document.getElementById('ishur1_').value = form.ishur1_ || 0;
            document.getElementById('ishur_1_').value = form.ishur_1_ || '';
            document.getElementById('ishur2').value = form.ishur2 || '';
            document.getElementById('ishur2_').value = form.ishur2_ || 0;
            document.getElementById('ishur_2_').value = form.ishur_2_ || '';
            document.getElementById('ishur3').value = form.ishur3 || '';
            document.getElementById('ishur3_').value = form.ishur3_ || 0;
            document.getElementById('ishur_3_').value = form.ishur_3_ || '';
            document.getElementById('ishur').value = form.ishur || '';
            
            // Kids data
            if (form.kids_data) {
                try {
                    const kidsData = typeof form.kids_data === 'string' ? JSON.parse(form.kids_data) : form.kids_data;
                    kidsData.forEach((kid, index) => {
                        if (index < 16) {
                            const i = index + 1;
                            document.getElementById(`kidName${i}`).value = kid.name || '';
                            document.getElementById(`kidStatus${i}`).value = kid.status || '';
                            document.getElementById(`kidBd${i}`).value = kid.birthdate || '';
                            document.getElementById(`age${i}`).value = kid.age || '';
                        }
                    });
                } catch (e) {
                    console.error('Error parsing kids data:', e);
                }
            }
        }
    } catch (error) {
        console.error('Error loading form data:', error);
    }
}

// Open Calculation Modal
function openCalculationModal(calcId = null) {
    document.getElementById('calculationModalTitle').textContent = calcId ? 'עריכת חישוב' : 'הוספת חישוב';
    document.getElementById('calculationForm').reset();
    document.getElementById('calculationId').value = calcId || '';
    
    // Hide all conditional inputs
    document.querySelectorAll('.age-range, .city-input, .married-range, .kids-range').forEach(el => {
        el.style.display = 'none';
    });
    
    if (calcId) {
        const calc = calculationsData.find(c => c.id == calcId);
        if (calc) {
            const conditions = JSON.parse(calc.conditions || '{}');
            document.getElementById('calculationName').value = calc.name;
            document.getElementById('calculationAmount').value = calc.amount;
            
            // Set conditions
            if (conditions.use_age) {
                document.querySelector('[name="use_age"]').checked = true;
                document.querySelector('.age-range').style.display = 'block';
                document.querySelector('[name="age_from"]').value = conditions.age_from || '';
                document.querySelector('[name="age_to"]').value = conditions.age_to || '';
            }
            if (conditions.use_city) {
                document.querySelector('[name="use_city"]').checked = true;
                document.querySelector('.city-input').style.display = 'block';
                document.querySelector('[name="city"]').value = conditions.city || '';
            }
            if (conditions.use_married) {
                document.querySelector('[name="use_married"]').checked = true;
                document.querySelector('.married-range').style.display = 'block';
                document.querySelector('[name="married_years_from"]').value = conditions.married_years_from || '';
                document.querySelector('[name="married_years_to"]').value = conditions.married_years_to || '';
            }
            if (conditions.use_kids_count) {
                document.querySelector('[name="use_kids_count"]').checked = true;
                document.querySelector('.kids-range').style.display = 'block';
                document.querySelector('[name="kids_from"]').value = conditions.kids_from || '';
                document.querySelector('[name="kids_to"]').value = conditions.kids_to || '';
            }
        }
    }
    
    openModal('calculationModal');
}

// Edit Support
function editSupport(id) {
    openHolidaySupportModal(id);
}

// Edit Calculation
function editCalculation(id) {
    openCalculationModal(id);
}

// Delete Support
async function deleteSupport(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק תמיכה זו?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_support');
    formData.append('id', id);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('התמיכה נמחקה בהצלחה', 'success');
            loadData();
        } else {
            showNotification('שגיאה במחיקת התמיכה', 'error');
        }
    } catch (error) {
        console.error('Error deleting support:', error);
        showNotification('שגיאה במחיקת התמיכה', 'error');
    }
}

// Delete Calculation
async function deleteCalculation(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק חישוב זה?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_calculation');
    formData.append('id', id);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('החישוב נמחק בהצלחה', 'success');
            loadData();
        } else {
            showNotification('שגיאה במחיקת החישוב', 'error');
        }
    } catch (error) {
        console.error('Error deleting calculation:', error);
        showNotification('שגיאה במחיקת החישוב', 'error');
    }
}

// Approve Support
async function approveSupport(id) {
    if (!confirm('האם אתה בטוח שברצונך לאשר תמיכה זו?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve_supports');
    formData.append('ids[]', id);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`אושרו ${result.approved} תמיכות`, 'success');
            loadData();
        } else {
            showNotification('שגיאה באישור התמיכה', 'error');
        }
    } catch (error) {
        console.error('Error approving support:', error);
        showNotification('שגיאה באישור התמיכה', 'error');
    }
}

// Update Selected Supports
function updateSelectedSupports() {
    selectedSupports = [];
    document.querySelectorAll('.support-checkbox:checked').forEach(cb => {
        selectedSupports.push(cb.dataset.id);
    });
    
    const approveBtn = document.getElementById('approveSelectedBtn');
    if (selectedSupports.length > 0) {
        approveBtn.style.display = 'inline-block';
        approveBtn.querySelector('span').textContent = `אשר ${selectedSupports.length} נבחרים`;
    } else {
        approveBtn.style.display = 'none';
    }
}

// Approve Selected
async function approveSelected() {
    if (selectedSupports.length === 0) {
        showNotification('לא נבחרו תמיכות לאישור', 'warning');
        return;
    }
    
    if (!confirm(`האם אתה בטוח שברצונך לאשר ${selectedSupports.length} תמיכות?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'approve_supports');
    selectedSupports.forEach(id => formData.append('ids[]', id));
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`אושרו ${result.approved} תמיכות`, 'success');
            selectedSupports = [];
            loadData();
        } else {
            showNotification('שגיאה באישור התמיכות', 'error');
        }
    } catch (error) {
        console.error('Error approving supports:', error);
        showNotification('שגיאה באישור התמיכות', 'error');
    }
}

// Apply Calculations
async function applyCalculations() {
    if (!confirm('האם אתה בטוח שברצונך להחיל את החישובים על כל הטפסים? פעולה זו תיצור/תעדכן תמיכות לפי הכללים שהוגדרו.')) return;
    
    const formData = new FormData();
    formData.append('action', 'apply_calculations');
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`החישובים הוחלו על ${result.applied} טפסים`, 'success');
            loadData();
        } else {
            showNotification('שגיאה בהחלת החישובים', 'error');
        }
    } catch (error) {
        console.error('Error applying calculations:', error);
        showNotification('שגיאה בהחלת החישובים', 'error');
    }
}

// Link to Donor
function linkToDonor(formId) {
    document.getElementById('linkFormId').value = formId;
    openModal('linkDonorModal');
}

// Search Donors
async function searchDonors() {
    const search = document.getElementById('donorSearch').value;
    
    if (search.length < 2) {
        document.getElementById('donorSearchResults').innerHTML = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'search_donors');
    formData.append('search', search);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const resultsDiv = document.getElementById('donorSearchResults');
            resultsDiv.innerHTML = '';
            
            if (result.data.length === 0) {
                resultsDiv.innerHTML = '<p>לא נמצאו תוצאות</p>';
                return;
            }
            
            result.data.forEach(person => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.innerHTML = `
                    <span>${person.donor_number} - ${person.first_name} ${person.last_name}</span>
                    <button class="btn btn-sm btn-primary" onclick="selectDonor(${person.donor_number})">בחר</button>
                `;
                resultsDiv.appendChild(item);
            });
        }
    } catch (error) {
        console.error('Error searching donors:', error);
    }
}

// Select Donor
async function selectDonor(donorNumber) {
    const formId = document.getElementById('linkFormId').value;
    
    const formData = new FormData();
    formData.append('action', 'link_donor');
    formData.append('form_id', formId);
    formData.append('donor_number', donorNumber);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('הטופס שויך בהצלחה', 'success');
            closeModals();
            loadData();
        } else {
            showNotification('שגיאה בשיוך הטופס', 'error');
        }
    } catch (error) {
        console.error('Error linking donor:', error);
        showNotification('שגיאה בשיוך הטופס', 'error');
    }
}

// Export to Excel
function exportToExcel() {
    window.location.href = `holiday_supports_api.php?action=export_excel&tab=${currentTab}`;
}

// Import from Excel
async function importFromExcel(file) {
    // This would require implementation similar to supports.js
    showNotification('ייבוא מאקסל יתווסף בקרוב', 'info');
}

// Utility Functions
function formatNumber(num) {
    return new Intl.NumberFormat('he-IL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(num || 0);
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Delete Form Data
async function deleteFormData(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק טופס זה?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_form');
    formData.append('id', id);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('הטופס נמחק בהצלחה', 'success');
            loadData();
        } else {
            showNotification('שגיאה במחיקת הטופס', 'error');
        }
    } catch (error) {
        console.error('Error deleting form:', error);
        showNotification('שגיאה במחיקת הטופס', 'error');
    }
}

// Delete Approved Support
async function deleteApprovedSupport(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק תמיכה מאושרת זו?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_approved_support');
    formData.append('id', id);
    
    try {
        const response = await fetch('holiday_supports_api.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('התמיכה נמחקה בהצלחה', 'success');
            loadData();
        } else {
            showNotification('שגיאה במחיקת התמיכה', 'error');
        }
    } catch (error) {
        console.error('Error deleting approved support:', error);
        showNotification('שגיאה במחיקת התמיכה', 'error');
    }
}

// View Form Details
function viewFormDetails(id) {
    const form = formsData.find(f => f.id == id);
    if (!form) return;
    
    const kidsData = form.kids_data ? JSON.parse(form.kids_data) : [];
    let kidsHtml = '<h5>פרטי ילדים:</h5><ul>';
    kidsData.forEach(kid => {
        kidsHtml += `<li>${kid.name || ''} - גיל ${kid.age || 0} - ${kid.status || ''}</li>`;
    });
    kidsHtml += '</ul>';
    
    const details = `
        <div class="form-details">
            <h4>פרטי טופס ${form.form_id}</h4>
            <p><strong>שם מלא:</strong> ${form.full_name || ''}</p>
            <p><strong>עיר:</strong> ${form.city || ''}</p>
            <p><strong>כתובת:</strong> ${form.street || ''}</p>
            <p><strong>נפשות בבית:</strong> ${form.sum_kids || 0}</p>
            <p><strong>מספר ילדים:</strong> ${form.sum_kids2 || 0}</p>
            <p><strong>נשואים:</strong> ${form.num_kids || 0}</p>
            <hr>
            <h5>הכנסות:</h5>
            <p><strong>משכורת אב:</strong> ₪${formatNumber(form.maskorte_av || 0)}</p>
            <p><strong>משכורת אם:</strong> ₪${formatNumber(form.maskorte_am || 0)}</p>
            <p><strong>הכנסות נוספות:</strong> ₪${formatNumber(form.hachnasa || 0)}</p>
            <p><strong>קצבאות:</strong> ₪${formatNumber(form.kitzva || 0)}</p>
            <hr>
            <h5>הוצאות:</h5>
            <p><strong>שכר לימוד:</strong> ₪${formatNumber(form.hotzaot_limud || 0)}</p>
            <p><strong>שכר דירה:</strong> ₪${formatNumber(form.hotzaot_dira || 0)}</p>
            <p><strong>הוצאה חריגה:</strong> ₪${formatNumber(form.hotzaot_chariga || 0)}</p>
            <p><strong>פירוט הוצאה חריגה:</strong> ${form.hotzaot_chariga2 || ''}</p>
            <hr>
            <p><strong>מדוע זקוק לסיוע:</strong> ${form.help || ''}</p>
            <hr>
            ${kidsHtml}
            <hr>
            <h5>פרטי בנק:</h5>
            <p><strong>בנק:</strong> ${form.bank || ''} - ${form.snif || ''}</p>
            <p><strong>חשבון:</strong> ${form.account || ''}</p>
            <p><strong>בעל החשבון:</strong> ${form.bank_name || ''}</p>
        </div>
    `;
    
    // Create a simple modal for displaying details
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>פרטי טופס</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                ${details}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.querySelector('.close').addEventListener('click', () => {
        modal.remove();
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// View Approved Details
function viewApprovedDetails(id) {
    const support = approvedData.find(s => s.id == id);
    if (!support) return;
    
    const details = `
        <div class="form-details">
            <h4>תמיכה מאושרת</h4>
            <p><strong>מס' תורם:</strong> ${support.donor_number || ''}</p>
            <p><strong>שם:</strong> ${support.first_name || ''} ${support.last_name || ''}</p>
            <p><strong>סכום תמיכה:</strong> ₪${formatNumber(support.full_support || 0)}</p>
            <p><strong>תאריך תמיכה:</strong> ${support.support_date || ''}</p>
            <p><strong>תאריך אישור:</strong> ${support.approved_at || ''}</p>
            <p><strong>הערות:</strong> ${support.notes || 'אין הערות'}</p>
        </div>
    `;
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>פרטי תמיכה</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                ${details}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.querySelector('.close').addEventListener('click', () => {
        modal.remove();
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
