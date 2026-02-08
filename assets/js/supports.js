// Supports JavaScript

let supportsData = [];
let peopleList = [];
let currentTab = 'summary';
let includeExceptionalExpense = true;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeModals();
    initializeEventListeners();
    loadPeopleList();
    // הצג כברירת מחדל את טאב הסיכום
    document.getElementById('summary-tab').style.display = '';
    document.getElementById('summary-tab').classList.add('active');
    document.getElementById('data-tab').style.display = 'none';
    document.getElementById('data-tab').classList.remove('active');
    loadSupportsData();
});

// Initialize Tabs
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.preventDefault(); // מונע רענון דף
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.tab-panel').forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });
    const activeTab = document.getElementById(`${tabName}-tab`);
    activeTab.style.display = '';
    activeTab.classList.add('active');
    
    currentTab = tabName;
    loadSupportsData();
}

// Initialize Modals
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
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

// Initialize Event Listeners
function initializeEventListeners() {
    // Add Support Button
    document.getElementById('addSupportBtn').addEventListener('click', function() {
        openSupportModal();
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
    
    // Support Form Submit
    document.getElementById('supportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSupportForm();
    });
    
    // Load Person Data Button
    document.getElementById('loadPersonDataBtn').addEventListener('click', function() {
        loadPersonDataToForm();
    });
}

// Load People List
async function loadPeopleList() {
    try {
        const response = await fetch('supports_api.php?action=get_people_list', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            peopleList = result.data;
            populatePeopleDropdown();
        }
    } catch (error) {
        console.error('Error loading people list:', error);
    }
}

function populatePeopleDropdown() {
    const select = document.getElementById('personSelect');
    select.innerHTML = '<option value="">-- הזנה ידנית --</option>';
    
    peopleList.forEach(person => {
        const option = document.createElement('option');
        option.value = person.id;
        option.textContent = `${person.family_name || ''} ${person.first_name || ''} (${person.donor_number || ''})`;
        option.dataset.person = JSON.stringify(person);
        select.appendChild(option);
    });
}

// Load Person Data to Form
function loadPersonDataToForm() {
    const select = document.getElementById('personSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption.value) {
        alert('נא לבחור אדם מהרשימה');
        return;
    }
    
    const person = JSON.parse(selectedOption.dataset.person);
    
    // Fill form with person data
    document.getElementById('firstName').value = person.first_name || '';
    document.getElementById('lastName').value = person.family_name || '';
    document.getElementById('idNumber').value = person.husband_id || person.wife_id || '';
    document.getElementById('city').value = person.city || '';
    document.getElementById('street').value = person.address || '';
    document.getElementById('phone').value = person.phone || person.husband_mobile || '';
}

// Load Supports Data
async function loadSupportsData() {
    try {
        const url = currentTab === 'summary' 
            ? 'supports_api.php?action=get_all'
            : 'supports_api.php?action=get_raw_data';
        
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            supportsData = result.data;
            
            if (currentTab === 'summary') {
                renderSummaryTable(supportsData);
            } else {
                renderDataTable(supportsData);
            }
        } else {
            showAlert('error', result.error || 'שגיאה בטעינת הנתונים');
        }
    } catch (error) {
        console.error('Error loading supports data:', error);
        showAlert('error', 'שגיאה בטעינת הנתונים');
    }
}

// Render Summary Table
function renderSummaryTable(data) {
    const tbody = document.getElementById('summaryTableBody');
    tbody.innerHTML = '';
    
    let totalIncome = 0;
    let totalExpenses = 0;
    let totalSupport = 0;
    
    data.forEach(support => {
        const row = document.createElement('tr');
        
        const address = `${support.street || ''}, ${support.city || ''}`.trim().replace(/^,\s*/, '');
        
        totalIncome += parseFloat(support.total_income || 0);
        totalExpenses += parseFloat(support.total_expenses || 0);
        totalSupport += parseFloat(support.support_amount || 0);
        
        row.innerHTML = `
            <td>${support.first_name || ''}</td>
            <td>${support.last_name || ''}</td>
            <td>${support.id_number || ''}</td>
            <td>${address}</td>
            <td>${support.city || ''}</td>
            <td>${formatCurrency(support.total_income)}</td>
            <td>${formatCurrency(support.total_expenses)}</td>
            <td style="text-align: center;">${support.include_exceptional_in_calc == 1 ? '✓' : '✗'}</td>
            <td>${formatCurrency(support.income_per_person)}</td>
            <td><strong>${formatCurrency(support.support_amount)}</strong></td>
            <td>
                <button class="btn btn-sm btn-primary edit-support-btn" data-id="${support.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-support-btn" data-id="${support.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to buttons
    tbody.querySelectorAll('.edit-support-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openSupportModal(id);
        });
    });
    
    tbody.querySelectorAll('.delete-support-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            await deleteSupport(id);
        });
    });
    
    // Update totals
    document.getElementById('totalIncome').innerHTML = `<strong>${formatCurrency(totalIncome)}</strong>`;
    document.getElementById('totalExpenses').innerHTML = `<strong>${formatCurrency(totalExpenses)}</strong>`;
    document.getElementById('totalSupport').innerHTML = `<strong>${formatCurrency(totalSupport)}</strong>`;
}

// Render Data Table
function renderDataTable(data) {
    const tbody = document.getElementById('dataTableBody');
    tbody.innerHTML = '';
    
    data.forEach(support => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${support.id || ''}</td>
            <td>${formatDate(support.created_at)}</td>
            <td>${formatDate(support.updated_at)}</td>
            <td>${support.position_name || ''}</td>
            <td>${support.first_name || ''}</td>
            <td>${support.last_name || ''}</td>
            <td>${support.id_number || ''}</td>
            <td>${support.city || ''}</td>
            <td>${support.street || ''}</td>
            <td>${support.phone || ''}</td>
            <td>${support.household_members || 0}</td>
            <td>${support.married_children || 0}</td>
            <td>${support.study_work_place_1 || ''}</td>
            <td>${formatCurrency(support.income_scholarship_1)}</td>
            <td>${support.study_work_place_2 || ''}</td>
            <td>${formatCurrency(support.income_scholarship_2)}</td>
            <td>${formatCurrency(support.child_allowance)}</td>
            <td>${formatCurrency(support.survivor_allowance)}</td>
            <td>${formatCurrency(support.disability_allowance)}</td>
            <td>${formatCurrency(support.income_guarantee)}</td>
            <td>${formatCurrency(support.income_supplement)}</td>
            <td>${formatCurrency(support.rent_assistance)}</td>
            <td>${support.other_allowance_source || ''}</td>
            <td>${formatCurrency(support.other_allowance_amount)}</td>
            <td>${formatCurrency(support.housing_expenses)}</td>
            <td>${formatCurrency(support.tuition_expenses)}</td>
            <td>${formatCurrency(support.recurring_exceptional_expense)}</td>
            <td style="text-align: center;">${support.include_exceptional_in_calc == 1 ? '✓' : '✗'}</td>
            <td>${support.exceptional_expense_details || ''}</td>
            <td>${support.difficulty_reason || ''}</td>
            <td>${support.notes || ''}</td>
            <td>${support.account_holder_name || ''}</td>
            <td>${support.bank_name || ''}</td>
            <td>${support.branch_number || ''}</td>
            <td>${support.account_number || ''}</td>
            <td>${support.support_requester_name || ''}</td>
            <td>${support.transaction_number || ''}</td>
            <td>
                <button class="btn btn-sm btn-primary edit-support-btn" data-id="${support.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-support-btn" data-id="${support.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to buttons
    tbody.querySelectorAll('.edit-support-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openSupportModal(id);
        });
    });
    
    tbody.querySelectorAll('.delete-support-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            await deleteSupport(id);
        });
    });
}

// Open Support Modal (Add/Edit)
function openSupportModal(supportId = null) {
    const modal = document.getElementById('supportModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('supportForm');
    const modalAlert = document.getElementById('modalAlert');
    
    form.reset();
    
    // Clear any previous alert
    if (modalAlert) {
        modalAlert.style.display = 'none';
        modalAlert.textContent = '';
        modalAlert.className = '';
    }
    
    if (supportId) {
        modalTitle.textContent = 'עריכת תמיכה';
        loadSupportToForm(supportId);
    } else {
        modalTitle.textContent = 'הוספת תמיכה';
        document.getElementById('supportId').value = '';
    }
    
    modal.style.display = 'block';
}

// Load Support Data to Form
async function loadSupportToForm(supportId) {
    try {
        const response = await fetch(`supports_api.php?action=get_one&id=${supportId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const result = await response.json();
        
        if (result.success) {
            const support = result.data;
            
            // Fill form fields
            document.getElementById('supportId').value = support.id;
            document.getElementById('personSelect').value = support.person_id || '';
            document.getElementById('positionName').value = support.position_name || '';
            document.getElementById('firstName').value = support.first_name || '';
            document.getElementById('lastName').value = support.last_name || '';
            document.getElementById('idNumber').value = support.id_number || '';
            document.getElementById('city').value = support.city || '';
            document.getElementById('street').value = support.street || '';
            document.getElementById('phone').value = support.phone || '';
            document.getElementById('householdMembers').value = support.household_members || 0;
            document.getElementById('marriedChildren').value = support.married_children || 0;
            document.getElementById('studyWorkPlace1').value = support.study_work_place_1 || '';
            document.getElementById('incomeScholarship1').value = support.income_scholarship_1 || 0;
            document.getElementById('studyWorkPlace2').value = support.study_work_place_2 || '';
            document.getElementById('incomeScholarship2').value = support.income_scholarship_2 || 0;
            document.getElementById('childAllowance').value = support.child_allowance || 0;
            document.getElementById('survivorAllowance').value = support.survivor_allowance || 0;
            document.getElementById('disabilityAllowance').value = support.disability_allowance || 0;
            document.getElementById('incomeGuarantee').value = support.income_guarantee || 0;
            document.getElementById('incomeSupplement').value = support.income_supplement || 0;
            document.getElementById('rentAssistance').value = support.rent_assistance || 0;
            document.getElementById('otherAllowanceSource').value = support.other_allowance_source || '';
            document.getElementById('otherAllowanceAmount').value = support.other_allowance_amount || 0;
            document.getElementById('housingExpenses').value = support.housing_expenses || 0;
            document.getElementById('tuitionExpenses').value = support.tuition_expenses || 0;
            document.getElementById('recurringExceptionalExpense').value = support.recurring_exceptional_expense || 0;
            document.getElementById('exceptionalExpenseDetails').value = support.exceptional_expense_details || '';
            document.getElementById('difficultyReason').value = support.difficulty_reason || '';
            document.getElementById('notes').value = support.notes || '';
            document.getElementById('accountHolderName').value = support.account_holder_name || '';
            document.getElementById('bankName').value = support.bank_name || '';
            document.getElementById('branchNumber').value = support.branch_number || '';
            document.getElementById('accountNumber').value = support.account_number || '';
            document.getElementById('supportRequesterName').value = support.support_requester_name || '';
            document.getElementById('transactionNumber').value = support.transaction_number || '';
            document.getElementById('includeExceptionalInCalc').checked = support.include_exceptional_in_calc == 1;
        }
    } catch (error) {
        console.error('Error loading support:', error);
    }
}

// Save Support Form
async function saveSupportForm() {
    const form = document.getElementById('supportForm');
    const formData = new FormData(form);
    
    const supportId = document.getElementById('supportId').value;
    const personId = document.getElementById('personSelect').value;
    
    formData.append('action', supportId ? 'update' : 'add');
    formData.append('person_id', personId || '');
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.set('csrf_token', csrfToken);
    }
    
    try {
        const response = await fetch('supports_api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModals();
            loadSupportsData();
        }
    } catch (error) {
        console.error('Error saving support:', error);
    }
}

// Delete Support (called by event delegation)
async function deleteSupport(supportId) {
    if (!confirm('האם אתה בטוח שברצונך למחוק רשומה זו?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', supportId);
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    try {
        const response = await fetch('supports_api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            loadSupportsData();
        } else {
            showAlert('error', result.error || 'שגיאה במחיקת הרשומה');
        }
    } catch (error) {
        console.error('Error deleting support:', error);
        showAlert('error', 'שגיאה במחיקת הרשומה');
    }
}

// Import from Excel
async function importFromExcel(file) {
    const formData = new FormData();
    formData.append('action', 'import_excel');
    formData.append('excel_file', file);
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    try {
        showAlert('info', 'מייבא נתונים...');
        
        const response = await fetch('supports_api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            
            // Show linking modal if needed
            if (result.needs_linking && result.needs_linking.length > 0) {
                showLinkPersonModal(result.needs_linking);
            }
            
            loadSupportsData();
        } else {
            showAlert('error', result.error || 'שגיאה בייבוא הקובץ');
        }
    } catch (error) {
        console.error('Error importing Excel:', error);
        showAlert('error', 'שגיאה בייבוא הקובץ');
    } finally {
        // Clear file input
        document.getElementById('importExcelFile').value = '';
    }
}

// Export to Excel
function exportToExcel() {
    window.location.href = 'supports_api.php?action=export_excel';
}

// Show Link Person Modal
function showLinkPersonModal(needsLinking) {
    const modal = document.getElementById('linkPersonModal');
    const list = document.getElementById('linkPersonList');
    
    list.innerHTML = '';
    
    needsLinking.forEach(item => {
        const div = document.createElement('div');
        div.className = 'link-person-item';
        
        const select = document.createElement('select');
        select.innerHTML = '<option value="">-- בחר/י אדם --</option>';
        peopleList.forEach(person => {
            const option = document.createElement('option');
            option.value = person.id;
            option.textContent = `${person.family_name} ${person.first_name} (${person.donor_number})`;
            select.appendChild(option);
        });
        
        div.innerHTML = `
            <h5>${item.first_name} ${item.last_name} (${item.id_number})</h5>
        `;
        div.appendChild(select);
        
        const linkBtn = document.createElement('button');
        linkBtn.className = 'btn btn-primary btn-sm';
        linkBtn.textContent = 'שייך';
        linkBtn.onclick = async function() {
            const personId = select.value;
            if (!personId) {
                alert('נא לבחור אדם מהרשימה');
                return;
            }
            
            // Find support by ID number
            const support = supportsData.find(s => s.id_number === item.id_number);
            if (support) {
                await linkPerson(support.id, personId);
                div.remove();
                if (list.children.length === 0) {
                    modal.style.display = 'none';
                }
            }
        };
        div.appendChild(linkBtn);
        
        list.appendChild(div);
    });
    
    modal.style.display = 'block';
}

// Link Person to Support
async function linkPerson(supportId, personId) {
    const formData = new FormData();
    formData.append('action', 'link_person');
    formData.append('support_id', supportId);
    formData.append('person_id', personId);
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    try {
        const response = await fetch('supports_api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'שויך בהצלחה');
            loadSupportsData();
        } else {
            showAlert('error', result.error || 'שגיאה בשיוך');
        }
    } catch (error) {
        console.error('Error linking person:', error);
        showAlert('error', 'שגיאה בשיוך');
    }
}

// Utility Functions
function formatCurrency(value) {
    const num = parseFloat(value) || 0;
    return num.toFixed(2) + ' ₪';
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('he-IL');
}

function showAlert(type, message) {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    alert.textContent = message;
    
    // Insert at top of container
    const container = document.querySelector('.supports-container');
    container.insertBefore(alert, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Show alert inside modal
function showModalAlert(type, message) {
    const alertContainer = document.getElementById('modalAlert');
    if (!alertContainer) return;
    
    alertContainer.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    alertContainer.textContent = message;
    alertContainer.style.display = 'block';
    
    // Scroll modal to top to show alert
    const modalBody = alertContainer.closest('.modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        alertContainer.style.display = 'none';
        alertContainer.textContent = '';
    }, 4000);
}
